<?php

namespace App\Model;

class PosRatio
{
    /**
     * @var string
     */
    private string $posName;

    /**
     * @var string
     */
    private string $cardType;

    /**
     * @var string
     */
    private string $cardBrand;

    /**
     * @var int
     */
    private int $installment;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var float
     */
    private float $commissionRate;

    /**
     * @var float
     */
    private float $minFee;

    /**
     * @var int
     */
    private int $priority;

    /**
     * @var float|null
     */
    private ?float $calculatedPrice = null;

    /**
     * @var float|null
     */
    private ?float $payableTotal = null;

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $posName = $data['pos_name'] ?? '';
        $cardType = $data['card_type'] ?? '';
        $cardBrand = $data['card_brand'] ?? '';
        $installment = (int)($data['installment'] ?? 0);
        $currency = $data['currency'] ?? '';
        $commissionRate = (float)($data['commission_rate'] ?? 0);
        $minFee = (float)($data['min_fee'] ?? 0);
        $priority = (int)($data['priority'] ?? 0);

        return new self($posName, $cardType, $cardBrand, $installment, $currency, $commissionRate, $minFee, $priority);
    }

    /**
     * @param string $posName
     * @param string $cardType
     * @param string $cardBrand
     * @param int $installment
     * @param string $currency
     * @param float $commissionRate
     * @param float $minFee
     * @param int $priority
     */
    public function __construct(string $posName, string $cardType, string $cardBrand, int $installment, string $currency, float $commissionRate, float $minFee, int $priority)
    {
        $this->posName = $posName;
        $this->cardType = $cardType;
        $this->cardBrand = $cardBrand;
        $this->installment = $installment;
        $this->currency = $currency;
        $this->commissionRate = $commissionRate;
        $this->minFee = $minFee;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getPosName(): string
    {
        return $this->posName;
    }

    /**
     * @return string
     */
    public function getCardType(): string
    {
        return $this->cardType;
    }

    /**
     * @return string
     */
    public function getCardBrand(): string
    {
        return $this->cardBrand;
    }

    /**
     * @return int
     */
    public function getInstallment(): int
    {
        return $this->installment;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getCommissionRate(): float
    {
        return $this->commissionRate;
    }

    /**
     * @return float
     */
    public function getMinFee(): float
    {
        return $this->minFee;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return float|null
     */
    public function getCalculatedPrice(): ?float
    {
        return $this->calculatedPrice;
    }

    /**
     * @return float|null
     */
    public function getPayableTotal(): ?float
    {
        return $this->payableTotal;
    }

    /**
     * @param float $calculatedPrice
     * @return void
     */
    public function setCalculatedPrice(float $calculatedPrice): void
    {
        $this->calculatedPrice = $calculatedPrice;
    }

    /**
     * @param float $payableTotal
     * @return void
     */
    public function setPayableTotal(float $payableTotal): void
    {
        $this->payableTotal = $payableTotal;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'pos_name' => $this->posName,
            'card_type' => $this->cardType,
            'card_brand' => $this->cardBrand,
            'installment' => $this->installment,
            'currency' => $this->currency,
            'commission_rate' => number_format($this->commissionRate, 4, '.', ''),
            'price' => $this->calculatedPrice,
            'payable_total' => $this->payableTotal
        ];
    }
}
