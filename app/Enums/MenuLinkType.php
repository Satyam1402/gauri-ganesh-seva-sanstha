<?php

namespace App\Enums;

enum MenuLinkType: string
{
    case Route = 'route';
    case Path = 'path';
    case External = 'url';

    public function label(): string
    {
        return match ($this) {
            self::Route => 'Site page',
            self::Path => 'Internal path',
            self::External => 'External link',
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
