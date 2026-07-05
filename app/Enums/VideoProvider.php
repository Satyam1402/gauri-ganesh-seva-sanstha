<?php

namespace App\Enums;

enum VideoProvider: string
{
    case Youtube = 'youtube';
    case Vimeo = 'vimeo';
    case SelfHosted = 'self_hosted';

    public function label(): string
    {
        return match ($this) {
            self::Youtube => 'YouTube',
            self::Vimeo => 'Vimeo',
            self::SelfHosted => 'Self Hosted',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
