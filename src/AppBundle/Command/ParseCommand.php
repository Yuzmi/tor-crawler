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
            ->setName("app:parse")
            ->setDescription("Parse onion urls")
            ->addArgument("what", InputArgument::OPTIONAL, "What do you want to parse ? onions/urls/url")
            ->addOption("mode", "m", InputOption::VALUE_REQUIRED, "Parsing mode")
            ->addOption("depth", "d", InputOption::VALUE_REQUIRED, "Depth to follow links")
            ->addOption("filter", "f", InputOption::VALUE_REQUIRED, "Which onions do you want to parse ? all/seen/unseen/unchecked")
            ->addOption("order", "o", InputOption::VALUE_REQUIRED, "How do you sort what you parse ? name/unchecked")
            ->addOption("discover", null, InputOption::VALUE_NONE, "Parse found onions")
            ->addOption("shuffle", null, InputOption::VALUE_NONE, "Shuffle URLs at the beginning")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $discover = $input->getOption("discover") ? true : false;
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

        $urls = []; // Known urls
        $parseUrls = []; // Urls to parse

        // What to parse
        $what = trim($input->getArgument("what"));
        if($what == "daniel") {
            $danielUrl = "http://onionsnjajzkhm5g.onion/onions.php?format=text";

            $urls[] = $danielUrl;
            $parseUrls[] = [
                "url" => $danielUrl,
                "depth" => -1
            ];
        } elseif(filter_var($what, FILTER_VALIDATE_URL) !== false) {
            $urls[] = $what;
            $parseUrls[] = [
                "url" => $what,
                "depth" => 0
            ];
        } elseif($this->parser->isOnionHash($what)) {
            $onion = $this->parser->getOnionForHash($what);
            if($onion) {
                $urls[] = $onion->getUrl();
                $parseUrls[] = [
                    "url" => $onion->getUrl(),
                    "depth" => 0
                ];
            } else {
                $output->writeln("Invalid onion hash");
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
                $parseUrls[] = [
                    "url" => $resource->getUrl(),
                    "depth" => 0
                ];
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
                $urls[] = $onion->getUrl();
                $parseUrls[] = [
                    "url" => $onion->getUrl(),
                    "depth" => 0
                ];
            }
        } else {
            $output->writeln("Invalid parameter");
            return;
        }

        if(count($parseUrls) == 0) {
            $output->writeln("Nothing to parse");
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
            $urlDomain = parse_url($url, PHP_URL_HOST);
            
            $i++;
            $output->write($i."/".count($urls)." : ".$url);

            $data = $this->parser->parseUrl($url);
            if(!$data["success"]) {
                $output->writeln(" : KO : ".round($data["duration"])."s".($data["error"] ? " : ".$data["error"] : ""));
                continue;
            }

            if($discover) {
                foreach($data["onions"] as $onion) {
                    if(!in_array($onion->getUrl(), $urls)) {
                        $urls[] = $onion->getUrl();
                        if($mode == "deep") {
                            array_unshift($parseUrls, [
                                "url" => $onion->getUrl(),
                                "depth" => 0
                            ]);
                        } else {
                            $parseUrls[] = [
                                "url" => $onion->getUrl(),
                                "depth" => 0
                            ];
                        }
                    }
                }
            }

            $newDepth = $dataUrl["depth"] + 1;
            if($newDepth <= $maxDepth) {
                foreach($data["resources"] as $resource) {
                    $resourceUrl = $resource->getUrl();

                    if(!$discover && parse_url($resourceUrl, PHP_URL_HOST) != $urlDomain) {
                        continue;
                    }

                    if(!in_array($resourceUrl, $urls)) {
                        $urls[] = $resourceUrl;
                        if($mode == "deep") {
                            array_unshift($parseUrls, [
                                "url" => $resourceUrl,
                                "depth" => $newDepth
                            ]);
                        } else {
                            $parseUrls[] = [
                                "url" => $resourceUrl,
                                "depth" => $newDepth
                            ];
                        }
                    }
                }
            }

            $output->writeln(" : OK : ".round($data["duration"])."s".($data["title"] ? " : ".$data["title"] : ""));
        }
    }
}
