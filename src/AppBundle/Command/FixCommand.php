<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Services\Parser;

class FixCommand extends ContainerAwareCommand {
    private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('app:fix')
            ->setDescription('Fix things')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();

        $i = 0;
        $onions = $em->getRepository("AppBundle:Onion")->findAll();
        foreach($onions as $o) {
            if($o->getResource() && $o->getResource()->getUrl() != $o->getUrl()) {
                $o->setResource(null);
                $em->persist($o);

                $i++;
                if($i == 500) {
                    $em->flush();
                }
            }
        }

        $em->flush();
    }
}