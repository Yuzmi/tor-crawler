<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Resource;

class RelevanceManager {
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

	public function updateAll() {
        $chunkSize = 200;

        $i = 0;
        do {
            $resources = $this->em->getRepository("AppBundle:Resource")
                ->createQueryBuilder("r")
                ->select("r, o")
                ->leftJoin("r.onion", "o")
                ->orderBy("r.id", "ASC")
                ->setMaxResults($chunkSize)
                ->setFirstResult($i*$chunkSize)
                ->getQuery()->getResult();
            $countResources = count($resources);

            foreach($resources as $r) {
                $relevance = $this->getRelevanceForResource($r);
                $r->setRelevance($relevance);
                $this->em->persist($r);
            }

            $this->em->flush();
            $this->em->clear();

            $i++;
        } while($chunkSize == $countResources);
    }

    public function getRelevanceForResource(Resource $resource) {
        $relevance = 0;

        if(!$resource->getDateLastSeen()) {
            if($resource->getDateChecked()) {
                return -100;
            } else {
                return -50;
            }
        }

        if(!$resource->getHttpCode() || substr($resource->getHttpCode(), 0, 1) != "2") {
            $relevance -= 20;
        }

        if($resource->getDateLastSeen() != $resource->getDateChecked()) {
            if($resource->getCountErrors() > 10) {
                $relevance -= 20;
            } else {
                $relevance -= 5;
            }
        }

        if($resource->getTitle()) {
            $relevance += 10;
        }

        if($resource->getDescription()) {
            $relevance += 5;
        }

        if($resource->getOnion() 
        && $resource->getOnion()->getUrl() == $resource->getUrl()) {
            $relevance += 1;
        }

        if($resource->getLastLength() > 0) {
            $relevance += 1;
        }

        if($resource->getContentType() == "text/html") {
            $relevance += 1;
        }

        return $relevance;
    }
}
