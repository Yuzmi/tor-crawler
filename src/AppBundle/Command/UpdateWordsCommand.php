<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Entity\OnionWord;

class UpdateWordsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:update:words')
            ->setDescription('Update Word and OnionWord entries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $output->writeln("Updating OnionWords...");

        $iOnion = 0;
        $onionIds = $em->getRepository("AppBundle:Onion")->findIds();

        foreach($onionIds as $onionId) {
            $onion = $em->getRepository("AppBundle:Onion")->find($onionId);
            $onionWords = $em->getRepository("AppBundle:OnionWord")->findForOnionPerWordId($onion);

            $wordsForOnion = $em->getRepository("AppBundle:Word")->findForOnion($onion);
            $sumCountsForOnionPerWord = $em->getRepository("AppBundle:Word")->sumCountsForOnionPerId($onion);
            $countResourcesForOnionPerWord = $em->getRepository("AppBundle:Word")->countResourcesForOnionPerId($onion);

            foreach($wordsForOnion as &$word) {
                if(isset($onionWords[$word->getId()])) {
                    $onionWord = $onionWords[$word->getId()];
                } else {
                    $onionWord = new OnionWord();
                    $onionWord->setOnion($onion);
                    $onionWord->setWord($word);
                }

                $onionWord->setDateUpdated(new \DateTime());

                $count = 0;
                if(isset($sumCountsForOnionPerWord[$word->getId()])) {
                    $count = $sumCountsForOnionPerWord[$word->getId()];
                }
                $onionWord->setCount($count);

                $countResources = 0;
                if(isset($countResourcesForOnionPerWord[$word->getId()])) {
                    $countResources = $countResourcesForOnionPerWord[$word->getId()];
                }
                $onionWord->setCountResources($countResources);

                $average = 0;
                if($countResources > 0) {
                    $average = round($count / $countResources, 3);
                }
                $onionWord->setAverage($average);

                $em->persist($onionWord);
            }

            $em->flush();
            $em->clear();

            if($iOnion%10 == 0) {
                $output->write(".");
            }
            $iOnion++;
        }

        $output->writeln("");
        $output->writeln("Updating Words...");

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

            if($iWord%100 == 0) {
                $output->write(".");
            }
            $iWord++;
            if($iWord%100 == 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();

        $output->writeln("");
        $output->writeln("End.");
    }
}
