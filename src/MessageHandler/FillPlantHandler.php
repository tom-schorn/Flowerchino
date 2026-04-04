<?php

namespace App\MessageHandler;

use App\Message\FillPlantMessage;
use App\Repository\PlantRepository;
use App\Service\PlantFillService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FillPlantHandler
{
    public function __construct(
        private PlantRepository $plants,
        private PlantFillService $fillService,
    ) {}

    public function __invoke(FillPlantMessage $message): void
    {
        $plant = $this->plants->find($message->plantId);
        if (!$plant || $plant->getQualityGrade() !== 'pending') {
            return;
        }

        $this->fillService->fill($plant);
    }
}
