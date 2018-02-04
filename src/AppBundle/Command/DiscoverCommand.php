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
            ->addOption('seen', null, InputOption::VALUE_NONE, 'Only seen onions')
            ->addOption('unseen', null, InputOption::VALUE_NONE, 'Only unseen onions')
            ->addOption('unchecked', null, InputOption::VALUE_NONE, 'Only unchecked onions')
            ->addOption('shuffle', null, InputOption::VALUE_NONE, 'Changer the order')
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
                $output->writeln("Invalid URL"); return;
            }
        } else {
            $hashes = array();

            $qb = $em->getRepository("AppBundle:Onion")->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->orderBy("o.hash", "ASC");

            if($input->getOption("seen")) {
                $qb->where("r.dateFirstSeen IS NOT NULL");
            } elseif($input->getOption("unseen")) {
                $qb->where("r.dateFirstSeen IS NULL");
            } elseif($input->getOption("unchecked")) {
                $qb->where("r.dateChecked IS NULL");
            }
            
            $dbOnions = $qb->getQuery()->getResult();

            foreach($dbOnions as $o) {
                $hashes[] = $o->getHash();
            }
        }

        $countHashes = count($hashes);
        if($countHashes == 0) {
            $output->writeln("No hash found"); return;
        }

        if($input->getOption("shuffle")) {
            shuffle($hashes);
        }

        $i = 0;
        while(list($key, $value) = each($hashes)) {
            $hash = $value;
            $i++;
            $output->write($i."/".$countHashes." : ".$hash);

            $onion = $this->parser->getOnionForHash($hash);
            if(!$onion) {
                $output->writeln(" : KO");
                continue;
            }
                
            $result = $this->parser->parseOnion($onion);
            if(!$result["success"]) {
                $output->writeln(" : KO : ".round($result["duration"])."s".($result["error"] ? " : ".$result["error"] : ""));
                continue;
            }
                
            foreach($result["onion-hashes"] as $h) {
                if(!in_array($h, $hashes)) {
                    $hashes[] = $h;
                    $countHashes++;
                }
            }

            $output->writeln(" : OK : ".round($result["duration"])."s".($result["title"] ? " : ".$result["title"] : ""));
        }
    }
}