<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveResourcesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:remove:resources')
            ->setDescription('Remove resources')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $output->writeln("Removing resources... ");

        do {
            $resources = $em->getRepository("AppBundle:Resource")
                ->createQueryBuilder("r")
                ->leftJoin("r.onion", "o")
                ->leftJoin("o.resource", "ro")
                ->where("r.id != ro.id")
                ->setMaxResults(200)
                ->getQuery()->getResult();

            $count = count($resources);
            if($count > 0) {
                foreach($resources as $r) {
                    $em->remove($r);
                }
                $em->flush();
                $em->clear();
                $output->write(".");
            } else {
                $output->writeln("");
            }
        } while($count > 0);

        $output->writeln("Done.");
    }
}
