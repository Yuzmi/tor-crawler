<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;

use AppBundle\Services\Parser;

class ParseFilesCommand extends ContainerAwareCommand {
    private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:parse:files')
            ->setDescription('Parse files')
            ->addArgument("file", InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dir = realpath(__DIR__."/../../../var/files");

        $file = trim($input->getArgument("file"));
        if($file) {
            $files = [$file];
        } else {
            $files = scandir($dir);
        }
        
        foreach($files as $file) {
            if(preg_match("#^[0-9a-f]{32,40}\.json$#i", $file)) {
                $path = $dir."/".$file;
                if(file_exists($path)) {
                    $json = file_get_contents($path);
                    if($json !== false) {
                        $result = json_decode($json, true);
                        if(is_array($result)) {
                            $timestamp = strtotime($result["dateUTC"]);
                            $result["date"] = $timestamp !== false ? new \DateTime("@".$timestamp) : null;
                            if($result["date"]) {
                                date_timezone_set($result["date"], new \DateTimeZone(date_default_timezone_get()));

                                $resource = null;
                                if(isset($result["onion"])) {
                                    $onion = $this->parser->getOnionForHash($result["onion"]);
                                    if($onion) {
                                        $resource = $this->parser->getResourceForOnion($onion);
                                    }
                                }

                                if(!$resource && isset($result["url"])) {
                                    $resource = $this->parser->getResourceForUrl($result["url"]);
                                }

                                if($resource && $result["date"] >= $resource->getDateChecked()) {
                                    $this->parser->parseResource($resource, [
                                        "result" => $result
                                    ]);
                                }
                            }
                        }
                    }

                    @unlink($path);
                }
            }
        }
    }
}
