<?php

namespace App\Service;

interface PosSelectionServiceInterface
{
    /**
     * Belirtilen kriterlere göre en uygun POS'u seçer.
     *
     * @param float $amount
     * @param int $installment
     * @param string $currency
     * @param string|null $cardType
     * @param string|null $cardBrand
     * @return array
     */
    public function selectBestPos(
        float $amount,
        int $installment,
        string $currency,
        ?string $cardType = null,
        ?string $cardBrand = null
    ): array;

}
