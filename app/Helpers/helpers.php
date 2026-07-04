<?php

if (! function_exists('format_inr')) {
    /**
     * Format a numeric amount using Indian digit grouping (e.g. 1,00,000 rather than 100,000).
     * Avoids a hard dependency on the intl extension, which isn't guaranteed on every VPS.
     */
    function format_inr(float|int $amount, bool $withSymbol = true): string
    {
        $isNegative = $amount < 0;
        [$whole, $decimal] = array_pad(explode('.', number_format(abs($amount), 2, '.', '')), 2, '00');

        $lastThree = substr($whole, -3);
        $remaining = substr($whole, 0, -3);

        if ($remaining !== '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $lastThree = ','.$lastThree;
        }

        $formatted = $remaining.$lastThree;
        $formatted = ($decimal === '00') ? $formatted : "{$formatted}.{$decimal}";

        return ($isNegative ? '-' : '').($withSymbol ? '₹' : '').$formatted;
    }
}
