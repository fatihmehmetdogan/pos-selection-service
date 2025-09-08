<?php

namespace App\MessageHandler;

use App\Message\UpdatePosRatiosMessage;
use App\Repository\PosRatioRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * UpdatePosRatiosMessage mesajını işler ve POS oranlarını güncelleme işlemini başlatır.
 */
#[AsMessageHandler]
final class UpdatePosRatiosMessageHandler
{
    private PosRatioRepositoryInterface $posRatioRepository;

    public function __construct(PosRatioRepositoryInterface $posRatioRepository)
    {
        $this->posRatioRepository = $posRatioRepository;
    }

    /**
     * @param UpdatePosRatiosMessage $message
     * @return void
     */
    public function __invoke(UpdatePosRatiosMessage $message): void
    {
        // Asıl işi yapması için Repository'yi çağırır.
        $this->posRatioRepository->updateRatios();
    }
}