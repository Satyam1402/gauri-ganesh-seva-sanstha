<?php

namespace App\Support\Backups;

/**
 * Strips every configured secret out of a message before it is stored as
 * a failure reason or shown in the admin panel. Command-line tools such as
 * mysqldump happily echo connection strings back in their errors.
 */
class SecretScrubber
{
    public function scrub(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        $secrets = array_filter([
            config('database.connections.'.config('database.default').'.password'),
            config('backup.backup.password'),
            config('filesystems.disks.backups_s3.secret'),
            config('filesystems.disks.backups_s3.key'),
            config('filesystems.disks.s3.secret'),
            config('app.key'),
        ], fn ($secret) => is_string($secret) && strlen($secret) >= 4);

        foreach ($secrets as $secret) {
            $message = str_replace($secret, '***', $message);
        }

        // Generic "--password=xyz" / "password: xyz" shapes from CLI tools.
        $message = preg_replace('/(--?password[=\s:]+)\S+/i', '$1***', $message) ?? $message;

        return mb_substr($message, 0, 2000);
    }
}
