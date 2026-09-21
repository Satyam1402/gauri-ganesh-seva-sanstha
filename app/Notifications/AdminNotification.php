<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Base for internal notifications sent to authorised admin users.
 *
 * Every admin notification is stored in the notifications table (bell +
 * notification centre) and, by default, also emailed. The database payload
 * is deliberately minimal — a title, a short line, a link into the admin
 * panel and the category — so no PII beyond what the admin list pages
 * already show is duplicated into the notifications table.
 */
abstract class AdminNotification extends BaseNotification
{
    /**
     * Category is static so the catalogue can map a stored class name back
     * to its permission without instantiating the notification.
     */
    abstract public static function category(): NotificationCategory;

    abstract public function title(): string;

    abstract public function message(): string;

    /**
     * Admin-panel URL to open from the notification, if any.
     */
    public function actionUrl(): ?string
    {
        return null;
    }

    public function actionLabel(): string
    {
        return 'View in Admin Panel';
    }

    /**
     * @return list<string>
     */
    public function supportedChannels(): array
    {
        return ['database', 'mail'];
    }

    /**
     * Guards against a recipient slipping past the resolver (e.g. a user
     * suspended between dispatch and delivery, or one who lost the
     * permission while the job sat in the queue).
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($notifiable instanceof User) {
            return $notifiable->status === 'active'
                && $notifiable->can(static::category()->permission()->value);
        }

        return $channel === 'mail';
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => static::category()->value,
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->actionUrl(),
        ];
    }

    /**
     * Generic admin email; subclasses with richer detail (donations,
     * registrations…) override this with their own template.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // "message" is reserved by the mailer (it injects Illuminate\Mail\Message
        // into every mail view), so the text goes in as "body".
        return $this->mailFromView($this->title(), 'emails.admin.alert', [
            'title' => $this->title(),
            'body' => $this->message(),
            'url' => $this->actionUrl(),
            'actionLabel' => $this->actionLabel(),
            'category' => static::category(),
        ]);
    }
}
