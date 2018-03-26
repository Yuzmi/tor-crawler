<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFilesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:parse:files')
            ->setDescription('Parse files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $dir = realpath(__DIR__."/../../../var/onions");
        $files = scandir($dir);
        
        $parser = $this->getContainer()->get("parser");
        foreach($files as $file) {
            if(preg_match("#^[a-z2-7]{16,56}\.json$#i", $file)) {
                $path = $dir."/".$file;
                $json = file_get_contents($path);

                $result = json_decode($json, true);
                if(is_array($result)) {
                    $onion = $parser->getOnionForHash($result["onion"]);
                    
                    $timestamp = strtotime($result["date"]);
                    $result["date"] = $timestamp !== false ? new \DateTime("@".$timestamp) : null;

                    if($onion && $result["date"] && (!$onion->getResource() || $result["date"] >= $onion->getResource()->getDateChecked())) {
                        $parser->parseOnion($onion, [
                            "result" => $result
                        ]);
                    }
                }

                unlink($path);
            }
        }
    }
}