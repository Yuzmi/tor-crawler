<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AppBundle\Services\Parser;

class ParseCommand extends ContainerAwareCommand {
	private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:parse')
            ->setDescription('Parse onion urls')
            ->addArgument('what', InputArgument::OPTIONAL, 'What do you want to parse ? onions/urls/url')
            ->addOption('shuffle', 's', InputOption::VALUE_NONE, 'Shuffle URLs at the beginning')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Parsing mode')
            ->addOption('depth', 'd', InputOption::VALUE_REQUIRED, 'Depth to follow links')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Which onions do you want to parse ? all/seen/unseen/unchecked')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $maxDepth = max(intval($input->getOption("depth")), 0);

        // Filter
        $filterParam = $input->getOption("filter");
        if($filterParam == "seen") {
            $filter = "seen";
        } elseif($filterParam == "unseen") {
            $filter = "unseen";
        } elseif($filterParam == "unchecked") {
            $filter = "unchecked";
        } elseif(in_array($filterParam, ["all", null, false])) {
            $filter = "all";
        } else {
            $output->writeln("Unknown value for parameter \"filter\"");
            return;
        }

        // Parsing mode
        $modeParam = $input->getOption("mode");
        if(in_array($modeParam, ["deep", "d"])) {
            $mode = "deep";
        } elseif(in_array($modeParam, ["random", "r"])) {
            $mode = "random";
        } elseif(in_array($modeParam, ["wide", "w", null, false])) {
            $mode = "wide";
        } else {
            $output->writeln("Unknown value for parameter \"mode\"");
            return;
        }

        $hashes = []; // Known hashes
        $urls = []; // Known urls
        $parseUrls = []; // Urls to parse

        // What to parse
        $what = trim($input->getArgument("what"));
        if($what == "daniel") {
            $danielUrl = "http://onionsnjajzkhm5g.onion/onions.php?format=text";

            $resource = $this->parser->getResourceForUrl($danielUrl);
            if($resource) {
                $urls[] = $resource->getUrl();
                $parseUrls[] = [
                    "url" => $resource->getUrl(),
                    "depth" => -1
                ];
            } else {
                $output->writeln("Problem with Daniel URL");
                return;
            }
        } elseif(filter_var($what, FILTER_VALIDATE_URL) !== false) {
            $resource = $this->parser->getResourceForUrl($what);
            if($resource) {
                $urls[] = $resource->getUrl();
                $parseUrls[] = [
                    "url" => $resource->getUrl(),
                    "depth" => 0
                ];
            } else {
                $output->writeln("Invalid onion URL");
                return;
            }
        } elseif(in_array($what, ["resource", "resources", "r", "urls", "u"])) {
            $qb = $em->getRepository("AppBundle:Resource")->createQueryBuilder("r")
                ->leftJoin("r.onion", "o")
                ->orderBy("r.url", "ASC");

            if($filter == "seen") {
                $qb->where("r.dateFirstSeen IS NOT NULL");
            } elseif($filter == "unseen") {
                $qb->where("r.dateFirstSeen IS NULL");
            } elseif($filter == "unchecked") {
                $qb->where("r.dateChecked IS NULL");
            }

            $dbResources = $qb->getQuery()->getResult();

            foreach($dbResources as $resource) {
                $urls[] = $resource->getUrl();
                $parseUrls[] = [
                    "url" => $resource->getUrl(),
                    "depth" => 0
                ];
                if($resource->getOnion()) {
                    $onionHash = $resource->getOnion()->getHash();
                    if(!in_array($onionHash, $hashes)) {
                        $hashes[] = $onionHash;
                    }
                }
            }
        } elseif(in_array($what, ["onion", "onions", "o", "", null])) {
            $qb = $em->getRepository("AppBundle:Onion")->createQueryBuilder("o")
                ->leftJoin("o.resource", "r")
                ->orderBy("o.hash", "ASC");

            if($filter == "seen") {
                $qb->where("r.dateFirstSeen IS NOT NULL");
            } elseif($filter == "unseen") {
                $qb->where("r.dateFirstSeen IS NULL");
            } elseif($filter == "unchecked") {
                $qb->where("r.dateChecked IS NULL");
            }
            
            $dbOnions = $qb->getQuery()->getResult();

            foreach($dbOnions as $onion) {
                $hashes[] = $onion->getHash();
                $urls[] = $onion->getUrl();
                $parseUrls[] = [
                    "url" => $onion->getUrl(),
                    "depth" => 0
                ];
            }
        } else {
            $output->writeln("Unknown value for argument \"what\"");
            return;
        }

        if(count($parseUrls) == 0) {
            if($maxDepth > 0) {
                $output->writeln("No hash or url found");
            } else {
                $output->writeln("No hash found");
            }
            return;
        }

        if($input->getOption("shuffle")) {
            shuffle($parseUrls);
        }

        $i = 0;
        while(!empty($parseUrls)) {
            if($mode == "random") {
                $index = array_rand($parseUrls);
                $dataUrl = $parseUrls[$index];
                array_splice($parseUrls, $index, 1);
            } else {
                $dataUrl = array_shift($parseUrls);
            }

            $url = $dataUrl["url"];

            $i++;
            $output->write($i."/".count($urls)." : ".$url);

            $result = $this->parser->parseUrl($url);
            if(!$result["success"]) {
                $output->writeln(" : KO : ".round($result["duration"])."s".($result["error"] ? " : ".$result["error"] : ""));
                continue;
            }

            $newHashes = [];
            foreach($result["onion-hashes"] as $hash) {
                if(!in_array($hash, $hashes)) {
                    $hashes[] = $hash;
                    $newHashes[] = $hash;
                }
            }

            if(!empty($newHashes)) {
                $onions = $this->parser->getOnionsForHashes($newHashes);
                foreach($onions as $o) {
                    $onionUrl = $o->getUrl();
                    if(!in_array($onionUrl, $urls)) {
                        $urls[] = $onionUrl;

                        if($mode == "deep") {
                            array_unshift($parseUrls, [
                                "url" => $onionUrl,
                                "depth" => 0
                            ]);
                        } else {
                            $parseUrls[] = [
                                "url" => $onionUrl,
                                "depth" => 0
                            ];
                        }
                    }
                }
            }

            $newDepth = $dataUrl["depth"] + 1;
            if($newDepth <= $maxDepth) {
                $newUrls = [];
                foreach($result["onion-urls"] as $url) {
                    if(!in_array($url, $urls)) {
                        $urls[] = $url;
                        $newUrls[] = $url;
                    }
                }

                if(!empty($newUrls)) {
                    $resources = $this->parser->getResourcesForUrls($newUrls);
                    foreach($resources as $r) {
                        if($mode == "deep") {
                            array_unshift($parseUrls, [
                                "url" => $r->getUrl(),
                                "depth" => $newDepth
                            ]);
                        } else {
                            $parseUrls[] = [
                                "url" => $r->getUrl(),
                                "depth" => $newDepth
                            ];
                        }
                    }
                }
            }

            $output->writeln(" : OK : ".round($result["duration"])."s".($result["title"] ? " : ".$result["title"] : ""));
        }
    }
}