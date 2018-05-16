<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Entity\OnionWord;

class DailyCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:daily')
            ->setDescription('Update things')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        // --- Update Onions --- //

        $output->writeln("Updating Onions...");

        $iOnion = 0;
        $onionIds = $em->getRepository("AppBundle:Onion")->findIds();
        $countOnions = count($onionIds);
        $countReferedPerOnion = $em->getRepository("AppBundle:Onion")->countReferedOnionsPerId();
        $countRefererPerOnion = $em->getRepository("AppBundle:Onion")->countRefererOnionsPerId();

        foreach($onionIds as $onionId) {
            $onion = $em->getRepository("AppBundle:Onion")->find($onionId);
            if($onion) {
                $onion->setCountReferedOnions($countReferedPerOnion[$onion->getId()]);
                $onion->setCountRefererOnions($countRefererPerOnion[$onion->getId()]);
                $em->persist($onion);
            }

            $iOnion++;
            if($iOnion%100 == 0 || $iOnion == $countOnions) {
                $em->flush();
                $em->clear();
                $output->write(".");
            }
        }

        // --- Update OnionWords --- //

        $output->writeln("");
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

            if($iOnion%100 == 0) {
                $output->write(".");
            }
            $iOnion++;
        }

        // --- Update Words --- //

        $output->writeln("");
        $output->writeln("Updating Words...");

        $iWord = 0;
        $sumCounts = $em->getRepository("AppBundle:Word")->sumCountsPerId();
        $words = $em->getRepository("AppBundle:Word")->findAll();
        $countWords = count($words);

        foreach($words as $w) {
            $word = $em->getRepository("AppBundle:Word")->find($w->getId());

            $count = 0;
            if(isset($sumCounts[$word->getId()])) {
                $count = $sumCounts[$word->getId()];
            }
            $word->setCount($count);

            $em->persist($word);

            if($iWord%1000 == 0) {
                $output->write(".");
            }
            $iWord++;
            if($iWord%1000 == 0 || $iWord == $countWords) {
                $em->flush();
                $em->clear();
            }
        }

        $output->writeln("");
        $output->writeln("End.");
    }
}
