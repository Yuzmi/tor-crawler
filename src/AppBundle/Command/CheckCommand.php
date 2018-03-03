<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AppBundle\Services\Parser;

class CheckCommand extends ContainerAwareCommand {
	private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:check')
            ->setDescription('Check onion urls')
            ->addArgument('what', InputArgument::OPTIONAL, 'What do you want to parse ? onions/urls')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'URL to begin from')
            ->addOption('daniel', 'd', InputOption::VALUE_NONE, 'Use the Daniel listing')
            ->addOption('filter', null, InputOption::VALUE_NONE, 'Which onions do you want to parse ? all/seen/unseen/unchecked')
            ->addOption('shuffle', null, InputOption::VALUE_NONE, 'Shuffle URLs at the beginning')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Parsing mode')
            ->addOption('follow', 'f', InputOption::VALUE_REQUIRED, 'Follow links (depth)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $followDepth = intval($input->getOption("follow"));

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

        $listUrl = $input->getOption("url");
        if(!$listUrl && $input->getOption("daniel")) {
            $listUrl = "http://onionsnjajzkhm5g.onion/onions.php?format=text";
        }

        if($listUrl) {
            if(filter_var($listUrl, FILTER_VALIDATE_URL) !== false) {
                $result = $this->parser->parseUrl($listUrl);
                if(!$result["success"]) {
                    if(isset($result["error"])) {
                        $output->writeln($result["error"]);
                    }
                    return;
                }

                foreach($result["onion-hashes"] as $hash) {
                    $hashes[] = $hash;

                    $onion = $this->parser->getOnionForHash($hash);
                    if($onion) {
                        $urls[] = $onion->getUrl();
                        $parseUrls[] = [
                            "url" => $onion->getUrl(),
                            "depth" => 0
                        ];
                    }
                }

                if($followDepth > 0) {
                    foreach($result["onion-urls"] as $url) {
                        if(!in_array($url, $urls)) {
                            $urls[] = $url;
                            $parseUrls[] = [
                                "url" => $url,
                                "depth" => 0
                            ];
                        }
                    }
                }
            } else {
                $output->writeln("Invalid URL"); return;
            }
        } else {
            // What to parse
            $whatParam = $input->getArgument("what");
            if(in_array($whatParam, ["resource", "resources", "r", "urls", "u"])) {
                $what = "resources";
            } elseif(in_array($whatParam, ["onion", "onions", "o"]) || $whatParam === null) {
                $what = "onions";
            } else {
                $output->writeln("Unknown value for argument \"what\"");
                return;
            }

            if($what == "resources") {
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
            } else {
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
            }
        }

        if(count($parseUrls) == 0) {
            if($followDepth > 0) {
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

            foreach($result["onion-hashes"] as $hash) {
                if(!in_array($hash, $hashes)) {
                    $hashes[] = $hash;

                    $onion = $this->parser->getOnionForHash($hash);
                    if($onion) {
                        $onionUrl = $onion->getUrl();
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
            }

            $newDepth = $dataUrl["depth"] + 1;
            if($newDepth <= $followDepth) {
                foreach($result["onion-urls"] as $foundUrl) {
                    if(!in_array($foundUrl, $urls)) {
                        $urls[] = $foundUrl;

                        if($mode == "deep") {
                            array_unshift($parseUrls, [
                                "url" => $foundUrl,
                                "depth" => $newDepth
                            ]);
                        } else {
                            $parseUrls[] = [
                                "url" => $foundUrl,
                                "depth" => $newDepth
                            ];
                        }

                        $this->parser->getResourceForUrl($foundUrl);
                    }
                }
            }

            $output->writeln(" : OK : ".round($result["duration"])."s".($result["title"] ? " : ".$result["title"] : ""));
        }
    }

    // Blacklist for later...
    private function getBlacklist() {
        return [
            "blockchainbdgpzk.onion",
            "xvwhmrw3sgwwmkko.onion/index.php?a=search&q=",
        ];
    }
}