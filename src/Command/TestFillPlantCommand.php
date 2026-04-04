<?php

namespace App\Command;

use App\MessageHandler\FillPlantHandler;
use App\Message\FillPlantMessage;
use App\Repository\PlantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:fill-plant', description: 'Run AI fill for a plant directly (synchronous)')]
class TestFillPlantCommand extends Command
{
    public function __construct(
        private FillPlantHandler $handler,
        private PlantRepository $plants,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('plant_id', InputArgument::REQUIRED, 'Plant ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id    = (int) $input->getArgument('plant_id');
        $plant = $this->plants->find($id);

        if (!$plant) {
            $output->writeln("<error>Plant #$id not found</error>");
            return Command::FAILURE;
        }

        $output->writeln("Filling <info>{$plant->getCanonicalName()}</info> (GBIF #{$plant->getGbifKey()})…");

        ($this->handler)(new FillPlantMessage($plant->getId(), $plant->getGbifKey(), $plant->getCanonicalName()));

        $output->writeln("Done. Grade: <info>{$plant->getQualityGrade()}</info>");
        return Command::SUCCESS;
    }
}
