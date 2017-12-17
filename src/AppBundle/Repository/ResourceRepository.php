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
}
