<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AppBundle\Entity\Onion;
use AppBundle\Entity\Resource;
use AppBundle\Services\Parser;

class ParseCommand extends ContainerAwareCommand {
	private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName("app:parse")
            ->setDescription("Parse onion urls")
            ->addArgument("what", InputArgument::OPTIONAL, "What do you want to parse ? onions/urls/url")
            ->addOption("mode", "m", InputOption::VALUE_REQUIRED, "Parsing mode")
            ->addOption("depth", "d", InputOption::VALUE_REQUIRED, "Depth to follow links")
            ->addOption("filter", "f", InputOption::VALUE_REQUIRED, "Which onions do you want to parse ? all/seen/unseen/unchecked")
            ->addOption("order", "o", InputOption::VALUE_REQUIRED, "How do you sort what you parse ? name/unchecked")
            ->addOption("no-discover", null, InputOption::VALUE_NONE, "Don't parse other onions")
            ->addOption("shuffle", null, InputOption::VALUE_NONE, "Shuffle URLs at the beginning")
            ->addOption("smart", "s", InputOption::VALUE_NONE, "Let's (try to) be smart")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $discover = $input->getOption("no-discover") ? false : true;
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
            $output->writeln("Unknown value for option \"filter\"");
            return;
        }

        // Mode
        $modeParam = $input->getOption("mode");
        if(in_array($modeParam, ["deep", "d"])) {
            $mode = "deep";
        } elseif(in_array($modeParam, ["random", "r"])) {
            $mode = "random";
        } elseif(in_array($modeParam, ["wide", "w", null, false])) {
            $mode = "wide";
        } else {
            $output->writeln("Unknown value for option \"mode\"");
            return;
        }

        // Order
        $orderParam = $input->getOption("order");
        if($orderParam == "unchecked") {
            $order = "unchecked";
        } elseif(in_array($orderParam, ["name", null, false])) {
            $order = "name";
        } else {
            $output->writeln("Unknown value for option \"order\"");
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
                ->leftJoin("r.onion", "o");

            if($filter == "seen") {
                $qb->where("r.dateFirstSeen IS NOT NULL");
            } elseif($filter == "unseen") {
                $qb->where("r.dateFirstSeen IS NULL");
            } elseif($filter == "unchecked") {
                $qb->where("r.dateChecked IS NULL");
            }

            if($order == "unchecked") {
                $qb->orderBy("r.dateChecked", "ASC");
            } else {
                $qb->orderBy("r.url", "ASC");
            }

            $dbResources = $qb->getQuery()->getResult();

            foreach($dbResources as $resource) {
                $urls[] = $resource->getUrl();

                if(!$input->getOption("smart") || $this->shouldBeParsed($resource)) {
                    $parseUrls[] = [
                        "url" => $resource->getUrl(),
                        "depth" => 0
                    ];
                }
            }
        } elseif(in_array($what, ["onion", "onions", "o", "", null])) {
            $qb = $em->getRepository("AppBundle:Onion")->createQueryBuilder("o")
                ->leftJoin("o.resource", "r");

            if($filter == "seen") {
                $qb->where("r.dateFirstSeen IS NOT NULL");
            } elseif($filter == "unseen") {
                $qb->where("r.dateFirstSeen IS NULL");
            } elseif($filter == "unchecked") {
                $qb->where("r.dateChecked IS NULL");
            }

            if($order == "unchecked") {
                $qb->orderBy("r.dateChecked", "ASC");
            } else {
                $qb->orderBy("o.hash", "ASC");
            }
            
            $dbOnions = $qb->getQuery()->getResult();

            foreach($dbOnions as $onion) {
                $hashes[] = $onion->getHash();
                $urls[] = $onion->getUrl();
                
                if(!$input->getOption("smart") || $this->shouldBeParsed($onion)) {
                    $parseUrls[] = [
                        "url" => $onion->getUrl(),
                        "depth" => 0
                    ];
                }
            }
        } else {
            $output->writeln("Invalid parameter");
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

                        if($discover) {
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

    private function shouldBeParsed($element) {
        $now = new \DateTime();

        if($element instanceof Onion) {
            $resource = $element->getResource();
        } elseif($element instanceof Resource) {
            $resource = $element;
        } else {
            return false;
        }

        if(!$resource || !$resource->getDateChecked()) {
            return true;
        }

        if($resource->getCountErrors() < 5) {
            return true;
        }

        if($resource->getDateLastSeen() > new \DateTime("7 days ago")) {
            return true;
        }

        $sevenDaysOld = clone $resource->getDateCreated();
        $sevenDaysOld->add(date_interval_create_from_date_string('7 days'));
        if($now < $sevenDaysOld) {
            return true;
        }

        return false;
    }
}