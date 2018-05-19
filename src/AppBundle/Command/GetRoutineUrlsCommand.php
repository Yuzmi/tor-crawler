<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GetRoutineUrlsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:get:routine-urls')
            ->setDescription('Get URLs')
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Quantity')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $quantity = intval($input->getArgument("quantity"));
        if($quantity <= 0) {
            $quantity = 100;
        }

        $urls = [];

        $uncheckedOnions = $em->getRepository("AppBundle:Onion")
            ->createQueryBuilder("o")
            ->leftJoin("o.resource", "r")
            ->where("r.dateChecked IS NULL")
            ->setMaxResults($quantity)
            ->getQuery()->getResult();

        foreach($uncheckedOnions as $o) {
            $urls[] = $o->getUrl();
            $quantity--;
        }

        $quantityActive = ceil($quantity*0.95);
        if($quantityActive > 0) {
            $activeOnions = $em->getRepository("AppBundle:Onion")
                ->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->where("r.dateChecked IS NOT NULL AND r.dateChecked < :oneHourAgo")
                ->setParameter("oneHourAgo", new \DateTime("1 hour ago"))
                ->andWhere("r.dateLastSeen >= :sevenDaysAgo")
                ->setParameter("sevenDaysAgo", new \DateTime("7 days ago"))
                ->orderBy("r.dateChecked", "ASC")
                ->setMaxResults($quantityActive)
                ->getQuery()->getResult();

            foreach($activeOnions as $o) {
                $urls[] = $o->getUrl();
            }
        }

        $quantityInactive = floor($quantity*0.05);
        if($quantityInactive > 0) {
            $inactiveOnions = $em->getRepository("AppBundle:Onion")
                ->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->where("r.dateChecked IS NOT NULL AND r.dateChecked < :oneHourAgo")
                ->setParameter("oneHourAgo", new \DateTime("1 hour ago"))
                ->andWhere("r.dateLastSeen IS NULL OR r.dateLastSeen < :sevenDaysAgo")
                ->setParameter("sevenDaysAgo", new \DateTime("7 days ago"))
                ->orderBy("r.dateChecked", "ASC")
                ->setMaxResults($quantityInactive)
                ->getQuery()->getResult();

            foreach($inactiveOnions as $o) {
                $urls[] = $o->getUrl();
            }
        }

        $jsonUrls = json_encode($urls);

        $output->writeln($jsonUrls);
    }
}
