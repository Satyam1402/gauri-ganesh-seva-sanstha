<?php

namespace App\Support\Backups;

final class ArchiveInfo
{
    /**
     * @param  list<string>  $dumpEntries  Zip entries holding SQL dumps.
     */
    public function __construct(
        public readonly bool $encrypted,
        public readonly array $dumpEntries,
        public readonly int $fileEntries,
        public readonly int $totalEntries,
    ) {}

    public function hasDatabase(): bool
    {
        return $this->dumpEntries !== [];
    }

    public function hasFiles(): bool
    {
        return $this->fileEntries > 0;
    }
}
