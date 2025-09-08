<?php

namespace App\Repository;

use App\config\AppConfig;
use App\Model\PosRatio;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Repository'nin ihtiyaç duyduğu tüm servisleri ve parametreleri constructor üzerinden alıyoruz.
 *
 */
class PosRatioRepository implements PosRatioRepositoryInterface
{
    private string $storageFilePath;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $ratiosApiUrl;
    private CacheInterface $cache;

    /**
     * PosRatioRepository constructor.
     *
     * @param LoggerInterface $logger
     * @param HttpClientInterface $httpClient
     * @param string $ratiosApiUrl
     * @param CacheInterface $cache
     */
    public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient, string $ratiosApiUrl, CacheInterface $cache)
    {
        $this->storageFilePath = AppConfig::POS_RATIOS_STORAGE_PATH;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->ratiosApiUrl = $ratiosApiUrl;
        $this->cache = $cache;
    }

    /**
     * Tüm POS oranlarını getirir.
     * Veriyi önce Redis cache'inden okumaya çalışır, bulamazsa dosyadan okur ve cache'i günceller.
     *
     * @return PosRatio[]
     */
    public function getAllRatios(): array
    {
        return $this->cache->get('pos_ratios_cache_key', function (ItemInterface $item) {
            $this->logger->info('Cache miss for POS ratios. Reading from storage file.');

            $item->expiresAfter(86400); // 86400 saniye = 24 saat

            if (!file_exists($this->storageFilePath)) {
                $this->updateRatios();
            }
            $rawData = json_decode(file_get_contents($this->storageFilePath), true);

            if (!is_array($rawData)) {
                $this->logger->warning('Storage file is corrupt or empty, trying to update from API.');
                $this->updateRatios();
                $rawData = json_decode(file_get_contents($this->storageFilePath), true);
            }

            if (!is_array($rawData)) {
                $this->logger->error('Failed to fetch or decode ratios after retry.');
                return [];
            }

            $ratios = [];
            foreach ($rawData as $itemData) {
                $ratios[] = PosRatio::fromArray($itemData);
            }

            return $ratios;
        });
    }

    /**
     * API'den oranları günceller, dosyaya yazar ve eski cache'i temizler.
     *
     * @return bool
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
            $this->logger->info('POS ratios updated successfully', ['count' => count(is_array($data) ? $data : [])]);

            // Veri güncellendiği için, eski cache'i geçersiz kıl (sil).
            $this->cache->delete('pos_ratios_cache_key');
            $this->logger->info('POS ratios cache has been invalidated.');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error during updateRatios: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritDoc}
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