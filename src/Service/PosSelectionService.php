<?php

namespace App\Service;

use App\config\AppConfig;
use App\Model\PosRatio;
use App\Repository\PosRatioRepositoryInterface;
use Psr\Log\LoggerInterface;

class PosSelectionService implements PosSelectionServiceInterface
{
    /**
     * @var PosRatioRepositoryInterface
     */
    private PosRatioRepositoryInterface $repository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param PosRatioRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(PosRatioRepositoryInterface $repository, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * @param float $amount
     * @param int $installment
     * @param string $currency
     * @param string|null $cardType
     * @param string|null $cardBrand
     * @return array
     */
    public function selectBestPos(float $amount, int $installment, string $currency, ?string $cardType = null, ?string $cardBrand = null): array
    {
        $this->logger->info('Selecting best POS', [
            'amount' => $amount,
            'installment' => $installment,
            'currency' => $currency,
            'card_type' => $cardType,
            'card_brand' => $cardBrand
        ]);

        // Repository'ye gönderilecek filtreler
        $filters = [
            'amount' => $amount,
            'installment' => $installment,
            'currency' => $currency
        ];

        if ($cardType) {
            $filters['card_type'] = $cardType;
        }

        if ($cardBrand) {
            $filters['card_brand'] = $cardBrand;
        }

        // Uygun POS oranlarını Repository'den al.
        $possibleRatios = $this->repository->filterRatios($filters);

        if (empty($possibleRatios)) {
            $this->logger->warning('No matching POS ratios found for the given criteria', $filters);
            return [
                'filters' => [
                    'amount' => $amount,
                    'installment' => $installment,
                    'currency' => $currency,
                    'card_type' => $cardType,
                    'card_brand' => $cardBrand
                ],
                'error' => 'No matching POS found for the given criteria'
            ];
        }

        // Bulunan her bir oran için maliyeti hesapla ve sıralama için nesnenin üzerine geçici olarak yaz.
        foreach ($possibleRatios as $ratio) {
            $price = $this->calculateCost($ratio, $amount);
            $ratio->setCalculatedPrice($price);
            $ratio->setPayableTotal($amount + $price);
        }

        // Oranları kurallara göre sırala
        usort($possibleRatios, [$this, 'compareRatios']);

        // Sıralanmış listenin en başındaki en iyi oranı al
        $bestRatio = $possibleRatios[0];

        //Sonucu istenen formatta API yanıtı olarak hazırla
        return [
            'filters' => [
                'amount' => $amount,
                'installment' => $installment,
                'currency' => $currency,
                'card_type' => $cardType,
                'card_brand' => $cardBrand
            ],
            'overall_min' => $bestRatio->toArray()
        ];
    }

    /**
     * Verilen oran ve tutar için işlem maliyetini hesaplar.
     *
     * @param PosRatio $ratio
     * @param float $amount
     * @return float
     */
    private function calculateCost(PosRatio $ratio, float $amount): float
    {
        $currencyMultiplier = AppConfig::CURRENCY_MULTIPLIERS[$ratio->getCurrency()];

        $calculatedCommission = $amount * $ratio->getCommissionRate() * $currencyMultiplier;
        $cost = max($calculatedCommission, $ratio->getMinFee());

        return round($cost, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * İki PosRatio nesnesini, vaka analizindeki kurallara göre karşılaştıran sıralama fonksiyonu.
     * usort() tarafından kullanılır.
     *
     * @param PosRatio $a Karşılaştırılacak ilk oran.
     * @param PosRatio $b Karşılaştırılacak ikinci oran.
     * @return int Karşılaştırma sonucunu (-1, 0 veya 1) döndürür.
     */
    private function compareRatios(PosRatio $a, PosRatio $b): int
    {
        // Hesaplanmış maliyete göre karşılaştır
        // <=> operatörü, $a < $b ise -1, $a == $b ise 0, $a > $b ise 1 döndürür.
        $costComparison = $a->getCalculatedPrice() <=> $b->getCalculatedPrice();
        if ($costComparison !== 0) {
            return $costComparison;
        }

        // Önceliğe göre karşılaştır büyükten küçüğe
        $priorityComparison = $b->getPriority() <=> $a->getPriority();
        if ($priorityComparison !== 0) {
            return $priorityComparison;
        }

        // Komisyon oranına göre karşılaştır
        $rateComparison = $a->getCommissionRate() <=> $b->getCommissionRate();
        if ($rateComparison !== 0) {
            return $rateComparison;
        }

        // POS ismine göre karşılaştır (alfabetik).
        return $a->getPosName() <=> $b->getPosName();
    }
}
