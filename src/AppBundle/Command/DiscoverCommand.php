<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AppBundle\Services\Parser;

class DiscoverCommand extends ContainerAwareCommand {
	private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:discover')
            ->setDescription('Discover onions')
            ->addArgument('url', InputArgument::OPTIONAL, 'URL to begin from')
            ->addOption('daniel', 'd', InputOption::VALUE_NONE, 'Use the Daniel listing')
            ->addOption('only-valid', null, InputOption::VALUE_NONE, 'Skip invalid onions')
            ->addOption('only-new', null, InputOption::VALUE_NONE, 'Skip known onions')
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
                $result = $this->parser->getUrlContent($listUrl);
                if(!$result["success"]) {
                    if(isset($result["error"])) {
                        $output->writeln($result["error"]);
                    }
                    return;
                }

                $hashes = $this->parser->getOnionHashesFromContent($result["content"]);
            } else {
                $output->writeln("URL invalide"); return;
            }
        } else {
            $hashes = array();

            $dbOnions = $em->getRepository("AppBundle:Onion")->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->orderBy("r.dateChecked", "ASC")
                ->getQuery()->getResult();

            foreach($dbOnions as $o) {
                $hashes[] = $o->getHash();
            }
        }

        $countHashes = count($hashes);
        if($countHashes == 0) {
            $output->writeln("No hash found"); return;
        }

        $i = 0;
        while(list($key, $value) = each($hashes)) {
            $hash = $value;
            $i++;
            $output->write($i."/".$countHashes." : ");

            $onion = $this->parser->getOnionForHash($hash);
            if(!$onion) {
                $output->writeln("KO : ".$hash);
                continue;
            }

            if($input->getOption("only-valid")) {
                $res = $onion->getResource();
                if($res && !$res->getDateSeen() && $res->getCountErrors() >= 3) {
                    $output->writeln("Skip : ".$hash);
                    continue;
                }
            }

            if($input->getOption("only-new")) {
                $res = $onion->getResource();
                if(!$res || !$res->getDateChecked()) {
                    $output->writeln("Skip : ".$hash);
                    continue;
                }
            }
                
            $result = $this->parser->parseOnion($onion);
            if(!$result["success"]) {
                $output->writeln("KO : ".$hash." : ".round($result["duration"], 3)."s : ".$result["error"]);
                continue;
            }
                
            foreach($result["onion-hashes"] as $h) {
                if(!in_array($h, $hashes)) {
                    $hashes[] = $h;
                    $countHashes++;
                }
            }

            $output->writeln("OK : ".$hash." : ".$result["duration"]."s : ".$result["title"]);
        }
    }
}