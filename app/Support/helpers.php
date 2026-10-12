<?php

if (! function_exists('rupiah')) {
    /**
     * Format an amount as Indonesian Rupiah, e.g. 15000 -> "Rp 15.000".
     */
    function rupiah(int|float|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }
}

if (! function_exists('percent')) {
    /**
     * Format a percentage with Indonesian separators, e.g. 12.5 -> "12,5%".
     */
    function percent(float|int|null $n, int $decimals = 1): string
    {
        $n = (float) $n;
        $formatted = number_format($n, $decimals, ',', '.');

        // Drop a trailing ",0" so whole numbers read naturally ("10%").
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted.'%';
    }
}
