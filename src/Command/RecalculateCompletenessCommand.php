<?php

namespace App\Command;

use App\Repository\PlantRepository;
use App\Service\PlantFillService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-completeness',
    description: 'Recalculate completeness scores for all plants',
)]
class RecalculateCompletenessCommand extends Command
{
    public function __construct(
        private PlantRepository $plants,
        private PlantFillService $fillService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $this->plants->findAll();
        $io->progressStart(count($all));

        foreach ($all as $plant) {
            $this->fillService->updateCompleteness($plant);
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();
        $io->success(sprintf('Updated %d plants.', count($all)));

        return Command::SUCCESS;
    }
}
