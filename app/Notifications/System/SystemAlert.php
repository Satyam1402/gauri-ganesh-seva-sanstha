<?php

namespace App\Notifications\System;

use App\Enums\NotificationCategory;
use App\Notifications\AdminNotification;

/**
 * Important operational alerts for users who manage settings (e.g. a
 * queued job that exhausted its retries). Sent synchronously and to the
 * database only — an alert about failing delivery must not itself depend
 * on the queue or the mailer.
 */
class SystemAlert extends AdminNotification
{
    public function __construct(
        private string $alertTitle,
        private string $alertMessage,
        private ?string $url = null,
    ) {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::System;
    }

    /**
     * @return list<string>
     */
    public function supportedChannels(): array
    {
        return ['database'];
    }

    public function title(): string
    {
        return $this->alertTitle;
    }

    public function message(): string
    {
        return $this->alertMessage;
    }

    public function actionUrl(): ?string
    {
        return $this->url;
    }
}
