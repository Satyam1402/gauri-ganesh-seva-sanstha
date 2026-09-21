<?php

namespace App\Providers;

use App\Interfaces\NotificationRepositoryInterface;
use App\Policies\NotificationPolicy;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use App\Support\Notifications\AdminRecipients;
use App\Support\Notifications\ChannelResolver;
use App\Support\Notifications\EmailBranding;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Wires the notification & communication layer:
 * repository/resolver bindings, the notification policy, the shared email
 * layout's branding composer, the branded password-reset mail, and the
 * failed-job → system alert hook.
 *
 * Future channels (SMS, WhatsApp) register their channel driver here via
 * Notification::extend() and flip their flag in config/notifications.php.
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->singleton(ChannelResolver::class);
        $this->app->singleton(AdminRecipients::class);
        $this->app->singleton(EmailBranding::class);
    }

    public function boot(): void
    {
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);

        // Every email — including framework ones — shares one layout that
        // reads the organisation's identity from Site Settings. Bound to
        // every emails.* view (not just the layout) because Blade captures
        // a child template's sections before the layout is rendered.
        View::composer('emails.*', function (\Illuminate\View\View $view): void {
            $view->with('brand', app(EmailBranding::class)->toArray());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $expires = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('Reset Your Password — '.setting('general.site_name', config('app.name')))
                ->view('emails.auth.reset-password', [
                    'url' => url(route('password.reset', [
                        'token' => $token,
                        'email' => $notifiable->getEmailForPasswordReset(),
                    ], false)),
                    'expires' => $expires,
                ]);
        });

        // A job that exhausted its retries (a notification, a media
        // conversion…) is surfaced to settings managers in-app. Guarded so
        // an alert about a failure can never cause a second failure.
        Queue::failing(function (JobFailed $event): void {
            try {
                $name = $event->job->resolveName();

                Log::error('Queued job failed permanently.', [
                    'job' => $name,
                    'connection' => $event->connectionName,
                    'error' => $event->exception->getMessage(),
                ]);

                app(NotificationService::class)->systemAlert(
                    'Background job failed: '.class_basename($name),
                    'The job exhausted its retries and was moved to the failed-jobs list. Run `php artisan queue:failed` on the server to inspect and `queue:retry` to resend.',
                );
            } catch (Throwable $e) {
                Log::error('Could not record the failed-job system alert.', ['error' => $e->getMessage()]);
            }
        });
    }
}
