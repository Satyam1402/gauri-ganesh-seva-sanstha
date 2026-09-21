<?php

namespace App\Notifications;

use App\Support\Notifications\ChannelResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Common base for every outgoing notification.
 *
 * - Queued by default (visitors never wait on SMTP); retries and back-off
 *   come from config/notifications.php and exhausted jobs land in
 *   failed_jobs for `queue:retry`.
 * - Channels are resolved centrally (ChannelResolver) so a new channel such
 *   as SMS is enabled in one place, not per class.
 * - Mail templates extend the shared `emails.layout`, which reads the
 *   organisation's name, logo and contact details from Site Settings.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries;

    public function __construct()
    {
        $this->tries = (int) config('notifications.tries', 3);
        $this->onQueue((string) config('notifications.queue', 'default'));
    }

    /**
     * Channels this notification is able to use, before site-wide toggles
     * and recipient preferences are applied. Public-facing notifications
     * default to mail only; admin ones add the database channel.
     *
     * @return list<string>
     */
    public function supportedChannels(): array
    {
        return ['mail'];
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(ChannelResolver::class)->channelsFor($notifiable, $this);
    }

    /**
     * Seconds to wait before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return (array) config('notifications.backoff', [60, 300, 900]);
    }

    /**
     * Called by the queue once every retry is exhausted. The underlying
     * business record was already saved — only delivery failed — so we
     * log with enough context to resend and never rethrow.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Notification delivery failed.', [
            'notification' => static::class,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Build a mail message from one of the Blade templates under
     * resources/views/emails. Templates receive the given data plus the
     * branding variables injected by the layout's view composer.
     *
     * @param  array<string, mixed>  $data
     */
    protected function mailFromView(string $subject, string $view, array $data = []): MailMessage
    {
        return (new MailMessage)
            ->subject($subject.' — '.setting('general.site_name', config('app.name')))
            ->view($view, $data);
    }
}
