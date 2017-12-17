<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DiscoverCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('app:discover')
            ->setDescription('Discover onions')
            ->addArgument('url', InputArgument::OPTIONAL, 'URL to begin from')
            ->addOption('daniel', 'd', InputOption::VALUE_NONE, 'Use the Daniel listing')
            ->addOption('skip-errors', null, InputOption::VALUE_NONE, 'Skip onions with errors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $listUrl = $input->getArgument("url");
        if(!$listUrl && $input->getOption("daniel")) {
            $listUrl = "http://onionsnjajzkhm5g.onion/onions.php?format=text";
        }

        if($listUrl) {
            if(filter_var($listUrl, FILTER_VALIDATE_URL) !== false) {
                $result = $this->getContainer()->get("parser")->getUrlContent($listUrl);
                if(!$result["success"]) {
                    $output->writeln($result["error"]); return;
                }

                $hashes = $this->getContainer()->get("parser")->getOnionHashesFromContent($result["content"]);
            } else {
                $output->writeln("URL invalide"); return;
            }
        } else {
            $hashes = array();

            $dbOnions = $em->getRepository("AppBundle:Onion")->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->orderBy("r.dateChecked", "DESC")
                ->getQuery()->getResult();

            foreach($dbOnions as $o) {
                $hashes[] = $o->getHash();
            }
        }

        $countHashes = count($hashes);
        if($countHashes == 0) {
            $output->writeln("No hash found"); return;
        }

        $allHashes = $hashes;
        $todoHashes = $hashes;

        $i = 0;
        while($hash = array_pop($todoHashes)) { // while(list($key, $value) = each($allHashes))
            $i++;
            $output->write($i."/".$countHashes." : ");

            $onion = $this->getContainer()->get("parser")->getOnionForHash($hash);
            if(!$onion) {
                $output->writeln("KO : ".$hash); continue;
            }

            if($input->getOption("skip-errors")) {
                $res = $onion->getResource();
                if($res && !$res->getDateSeen() && $res->getCountErrors() >= 3) {
                    $output->writeln("Skip : ".$hash); continue;
                }
            }
                
            $result = $this->getContainer()->get("parser")->parseOnion($onion);
            if(!$result["success"]) {
                $output->writeln("KO : ".$hash); continue;
            }
                
            foreach($result["onion-hashes"] as $h) {
                if(!in_array($h, $allHashes)) {
                    $allHashes[] = $h;
                    $todoHashes[] = $h;
                    $countHashes++;
                }
            }

            $output->writeln("OK : ".$hash." : ".$result["title"]);
        }
    }
}