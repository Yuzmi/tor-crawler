<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Services\RelevanceManager;

class UpdateRelevanceCommand extends ContainerAwareCommand {
    private $rm;

    public function __construct(RelevanceManager $rm) {
        $this->rm = $rm;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:update:relevance')
            ->setDescription('Update relevance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $output->write("Updating relevance... ");

        $this->rm->updateAll();

        $output->writeln("Done.");
    }
}
