<?php

namespace App\Repository;

use App\config\AppConfig;
use App\Model\PosRatio;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PosRatioRepository implements PosRatioRepositoryInterface
{
    private string $storageFilePath;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $ratiosApiUrl;
    /**
     * Repository'nin ihtiyaç duyduğu tüm servisleri ve parametreleri constructor üzerinden alıyoruz.
     *
     * @param LoggerInterface $logger
     * @param HttpClientInterface $httpClient
     * @param string $ratiosApiUrl
     */
    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        string $ratiosApiUrl
    ) {
        $this->storageFilePath = AppConfig::POS_RATIOS_STORAGE_PATH;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->ratiosApiUrl = $ratiosApiUrl;
    }

    /**
     * @inheritDoc
     */
    public function getAllRatios(): array
    {
        if (!file_exists($this->storageFilePath)) {
            $this->updateRatios();
        }

        $rawData = json_decode(file_get_contents($this->storageFilePath), true);
        if (!is_array($rawData)) {
            $this->logger->warning('Failed to decode ratios from storage, trying to update from API');
            $this->updateRatios();
            $rawData = json_decode(file_get_contents($this->storageFilePath), true);
        }

        // Eğer ikinci deneme de başarısız olduysa, hata vermemesi için boş dizi döndür.
        if (!is_array($rawData)) {
            $this->logger->error('Failed to fetch or decode ratios after retry.');
            return [];
        }

        $ratios = [];
        foreach ($rawData as $item) {
            $ratios[] = PosRatio::fromArray($item);
        }

        return $ratios;
    }

    /**
     * @inheritDoc
     */
    public function updateRatios(): bool
    {
        try {
            $this->logger->info('Updating POS ratios from external API');

            $response = $this->httpClient->request('GET', $this->ratiosApiUrl);
            $data = json_decode($response->getContent(), true);

            if (!is_array($data)) {
                $this->logger->error('Invalid data received from ratios API');
                return false;
            }

            // Dosyaya yazmadan önce dizinin var olup olmadığını kontrol ediyoruz
            $storageDir = dirname($this->storageFilePath);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            file_put_contents($this->storageFilePath, json_encode($data, JSON_PRETTY_PRINT));
            $this->logger->info('POS ratios updated successfully', ['count' => count($data)]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error fetching ratios from API: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function filterRatios(array $filters): array
    {
        $allRatios = $this->getAllRatios();
        $filteredRatios = [];

        $installment = $filters['installment'] ?? 0;
        $currency = $filters['currency'] ?? '';
        $cardType = $filters['card_type'] ?? null;
        $cardBrand = $filters['card_brand'] ?? null;

        // Zorunlu filtreler kontrolü
        if ($installment <= 0 || empty($currency)) {
            $this->logger->warning('Invalid required filter parameters', $filters);
            return [];
        }

        foreach ($allRatios as $ratio) {
            if ($ratio->getInstallment() !== $installment || $ratio->getCurrency() !== $currency) {
                continue;
            }
            if ($cardType !== null && $ratio->getCardType() !== $cardType) {
                continue;
            }
            if ($cardBrand !== null && $ratio->getCardBrand() !== $cardBrand) {
                continue;
            }
            $filteredRatios[] = $ratio;
        }

        $this->logger->info('Filtered ratios', [
            'total' => count($allRatios),
            'filtered' => count($filteredRatios),
            'filters' => $filters
        ]);

        return $filteredRatios;
    }
}