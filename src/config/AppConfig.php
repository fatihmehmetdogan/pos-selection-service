<?php

namespace App\config;

class AppConfig
{
    /**
     * Geçerli para birimleri
     */
    public const SUPPORTED_CURRENCIES = ['TRY', 'USD'];

    /**
     * Para birimi çarpanları
     */
    public const CURRENCY_MULTIPLIERS = [
        'TRY' => 1.00,
        'USD' => 1.01
    ];

    /**
     * Geçerli kart tipleri
     */
    public const CARD_TYPES = ['credit', 'debit'];

    /**
     * Oranların kaydedileceği dosya yolu
     */
    public const POS_RATIOS_STORAGE_PATH = '/var/www/storage/pos_ratios.json';

}
