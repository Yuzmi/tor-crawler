<?php

namespace AppBundle\Repository;

class OnionRepository extends \Doctrine\ORM\EntityRepository
{
	public function findOneByHash($hash) {
		return $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->where("o.hash = :hash")->setParameter("hash", $hash)
			->getQuery()->getOneOrNullResult();
	}
}
