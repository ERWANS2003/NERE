<?php

namespace App\Support;

class Fcfa
{
    public static function format(?float $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ');
    }

    public static function rate(?float $rate): string
    {
        return $rate === null ? '' : rtrim(rtrim(number_format($rate, 4, ',', ''), '0'), ',').' %';
    }
}
