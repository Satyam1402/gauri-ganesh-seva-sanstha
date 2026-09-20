<?php

namespace App\Enums;

enum MenuLocation: string
{
    case Header = 'header';
    case FooterExplore = 'footer_explore';
    case FooterInvolved = 'footer_involved';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Header Navigation',
            self::FooterExplore => 'Footer — Explore',
            self::FooterInvolved => 'Footer — Get Involved',
        };
    }

    /**
     * Only the header supports one level of dropdown children.
     */
    public function supportsChildren(): bool
    {
        return $this === self::Header;
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
