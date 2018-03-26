<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GetOnionsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:get:onions')
            ->setDescription('Get onions')
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Quantity')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset')
            ->addOption("smart", "s", InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parser = $this->getContainer()->get("parser");

        $qb = $em->getRepository("AppBundle:Onion")
            ->createQueryBuilder("o")
            ->orderBy("o.dateCreated", "ASC");

        $quantity = intval($input->getArgument("quantity"));
        if($quantity > 0) {
            $qb->setMaxResults($quantity);

            $offset = intval($input->getArgument("offset"));
            if($offset > 0) {
                $qb->setFirstResult($offset);
            }
        }
        
        $onions = $qb->getQuery()->getResult();

        $listOnions = [];
        foreach($onions as $o) {
            if(!$input->getOption("smart") || $parser->shouldBeParsed($o)) {
                $listOnions[] = $o->getHash();
            }
        }

        $jsonOnions = json_encode($listOnions);

        $output->writeln($jsonOnions);
    }
}