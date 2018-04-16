<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Onion;
use AppBundle\Entity\Resource;

class ResourceRepository extends \Doctrine\ORM\EntityRepository
{
	public function findOneByUrl($url) {
		return $this->createQueryBuilder("r")
			->select("r, o")
			->leftJoin("r.onion", "o")
			->where("r.hashUrl = :hash")
			->setParameter("hash", hash('sha512', $url))
			->getQuery()->getOneOrNullResult();
	}

	public function findForUrls($urls) {
		$resources = [];

		while(!empty($urls)) {
			$chunkHashUrls = [];

			$i = 0;
			while($i < 500 && !empty($urls)) {
				$chunkHashUrls[] = hash("sha512", array_shift($urls));
				$i++;
			}

			$chunkResources = $this->createQueryBuilder("r")
				->where("r.hashUrl IN (:hashUrls)")
				->setParameter("hashUrls", $chunkHashUrls)
				->getQuery()->getResult();
			foreach($chunkResources as $r) {
				$resources[] = $r;
			}
		}

		return $resources;
	}

	public function findRelatedForResource(Resource $resource, $nb = 10) {
		return $this->createQueryBuilder("r")
			->select("r, o")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $resource->getOnion()->getId())
			->andWhere("r.id != :resourceId")->setParameter("resourceId", $resource->getId())
			->orderBy("r.url", "ASC")
			->setMaxResults($nb)
			->getQuery()->getResult();
	}

	public function findForOnion(Onion $onion, $limit = 0) {
		$qb = $this->createQueryBuilder("r")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->orderBy("r.url", "ASC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function countForOnion(Onion $onion) {
		return $this->createQueryBuilder("r")
			->select("COUNT(r)")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->getQuery()->getSingleScalarResult();
	}
}
