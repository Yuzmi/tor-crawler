<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GetUrlsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:get:urls')
            ->setDescription('Get URLs')
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Quantity')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset')
            ->addOption("filter", "f", InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $qb = $em->getRepository("AppBundle:Onion")
            ->createQueryBuilder("o")
            ->leftJoin("o.resource", "r")
            ->orderBy("o.dateCreated", "ASC");

        $filter = $input->getOption("filter");
        if($filter == "unchecked") {
            $qb->where("r.dateChecked IS NULL");
        } elseif($filter == "seen") {
            $qb->where("r.dateLastSeen IS NOT NULL");
        } elseif($filter == "longchecked") {
            $qb->orderBy("r.dateChecked", "ASC");
        } elseif($filter == "valid") {
            $qb->where("r.dateLastSeen = r.dateChecked");
        }

        $quantity = intval($input->getArgument("quantity"));
        if($quantity > 0) {
            $qb->setMaxResults($quantity);

            $offset = intval($input->getArgument("offset"));
            if($offset > 0) {
                $qb->setFirstResult($offset);
            }
        }
        
        $onions = $qb->getQuery()->getResult();

        $urls = [];
        foreach($onions as $o) {
            $urls[] = $o->getUrl();
        }

        $jsonUrls = json_encode($urls);

        $output->writeln($jsonUrls);
    }
}
