<?php

namespace App\Controller;

use App\config\AppConfig;
use App\Service\PosSelectionServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PosSelectionController
 * @package App\Controller
 */
class PosSelectionController extends AbstractController
{
    /**
     * @var PosSelectionServiceInterface
     */
    private $posSelectionService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PosSelectionServiceInterface $posSelectionService
     * @param LoggerInterface $logger
     */
    public function __construct(PosSelectionServiceInterface $posSelectionService, LoggerInterface $logger)
    {
        $this->posSelectionService = $posSelectionService;
        $this->logger = $logger;
    }

    #[Route('/api/pos/select', name: 'pos_select', methods: ['POST'])]
    public function selectPos(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $params = json_decode($content, true);

            if (!$params || !is_array($params)) {
                return new JsonResponse(['error' => 'Invalid request format'], Response::HTTP_BAD_REQUEST);
            }

            // Zorunlu alanların (amount, installment, currency) kontrolü
            $requiredFields = ['amount', 'installment', 'currency'];
            foreach ($requiredFields as $field) {
                if (!isset($params[$field])) {
                    return new JsonResponse(['error' => "Missing required field: {$field}"], Response::HTTP_BAD_REQUEST);
                }
            }

            if (!is_numeric($params['amount']) || $params['amount'] <= 0) {
                return new JsonResponse(['error' => "Invalid amount value"], Response::HTTP_BAD_REQUEST);
            }

            if (!is_numeric($params['installment']) || $params['installment'] <= 0) {
                return new JsonResponse(['error' => "Invalid installment value"], Response::HTTP_BAD_REQUEST);
            }

            if (!in_array(strtoupper($params['currency']), AppConfig::SUPPORTED_CURRENCIES)) {
                return new JsonResponse([
                    'error' => "Invalid currency value. Supported: " . implode(', ', AppConfig::SUPPORTED_CURRENCIES)
                ], Response::HTTP_BAD_REQUEST);
            }

            $cardType = $params['card_type'] ?? null;
            $cardBrand = $params['card_brand'] ?? null;

            // Eğer kart tipi belirtilmişse  onu da doğrula.
            if ($cardType !== null && !in_array($cardType, AppConfig::CARD_TYPES)) {
                return new JsonResponse([
                    'error' => "Invalid card type. Supported: " . implode(', ', AppConfig::CARD_TYPES)
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->posSelectionService->selectBestPos(
                (float) $params['amount'],
                (int) $params['installment'],
                $params['currency'],
                $cardType,
                $cardBrand
            );

            return new JsonResponse($result, Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Error selecting POS: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse(
                ['error' => 'An error occurred while processing the request'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
