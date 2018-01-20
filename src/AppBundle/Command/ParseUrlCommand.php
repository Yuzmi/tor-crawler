<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use AppBundle\Services\Parser;

class ParseUrlCommand extends ContainerAwareCommand {
	private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:parse:url')
            ->setDescription('Parse a URL')
            ->addArgument('url', InputArgument::REQUIRED, 'URL to parse')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$url = trim($input->getArgument("url"));
        if($url && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $resource = $this->parser->getResourceForUrl($url);
            if($resource) {
                $result = $this->parser->parseResource($resource);
                if($result["success"]) {
                    $output->writeln("OK : ".$url." : ".$result["title"]);
                } else {
                    $output->writeln("KO : ".$url." : ".$result["error"]);
                }
            } else {
                $output->writeln("Error : ".$url." : Problem with resource");
            }
        } else {
            $output->writeln("Error : ".$url." : Problem with URL");
        }
    }
}