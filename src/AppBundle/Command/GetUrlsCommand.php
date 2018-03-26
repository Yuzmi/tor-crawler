<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;

class GetUrlsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:get:urls')
            ->setDescription('Get URLs')
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Quantity')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

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

        $urls = [];
        foreach($onions as $o) {
            $urls[] = $o->getUrl();
        }

        $json_urls = json_encode($urls);

        $output->writeln($json_urls);
    }
}
