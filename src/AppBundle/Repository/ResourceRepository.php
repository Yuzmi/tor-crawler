<?php

namespace AppBundle\Repository;

class ResourceRepository extends \Doctrine\ORM\EntityRepository
{
	public function findOneByUrl($url) {
		return $this->createQueryBuilder("r")
			->where("r.hashUrl = :hash")
			->setParameter("hash", hash('sha512', $url))
			->setMaxResults(1)
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
}
