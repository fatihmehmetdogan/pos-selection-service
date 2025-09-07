<?php

namespace App\Repository;

use App\Model\PosRatio;


interface PosRatioRepositoryInterface
{
    /**
     * Tüm POS oranlarını getirir.
     *
     * @return PosRatio[]
     */
    public function getAllRatios(): array;

    /**
     * API'den oranları günceller ve  kaydeder.
     *
     * @return bool İşlemin başarılı olup olmadığını döndürür.
     */
    public function updateRatios(): bool;

    /**
     * Verilen kriterlere göre oranları filtreler.
     *
     * @param array $filters
     * @return PosRatio[]
     */
    public function filterRatios(array $filters): array;
}
