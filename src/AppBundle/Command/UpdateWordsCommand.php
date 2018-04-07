<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateWordsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:update:words')
            ->setDescription('Update Word entries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();
        $iWord = 0;

        $sumCounts = $em->getRepository("AppBundle:Word")->sumCountsPerId();

        $words = $em->getRepository("AppBundle:Word")->findAll();
        foreach($words as $w) {
            $word = $em->getRepository("AppBundle:Word")->find($w->getId());

            $count = 0;
            if(isset($sumCounts[$word->getId()])) {
                $count = $sumCounts[$word->getId()];
            }
            $word->setCount($count);

            $em->persist($word);

            $iWord++;
            if($iWord%100 == 0) {
                $em->flush();
                $em->clear();
                $output->write(".");
            }
        }

        $em->flush();

        $output->writeln(" End.");
    }
}
