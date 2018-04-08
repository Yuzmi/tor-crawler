<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Onion;

class OnionWordRepository extends \Doctrine\ORM\EntityRepository
{
	public function findForOnionPerWordId(Onion $onion) {
		$ows = $this->createQueryBuilder("ow")
			->select("ow, w")
			->innerjoin("ow.word", "w")
			->innerJoin("ow.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->getQuery()->getResult();

		$onionWords = [];

		foreach($ows as $ow) {
			$onionWords[$ow->getWord()->getId()] = $ow;
		}

		return $onionWords;
	}

	public function findCurrentForOnion(Onion $onion, $limit = 0) {
		$qb = $this->createQueryBuilder("ow")
			->select("ow, w")
			->innerJoin("ow.onion", "o")
			->innerJoin("ow.word", "w")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->andWhere("ow.count > 0")
			->orderBy("ow.countResources", "DESC")
			->addOrderBy("ow.count", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function countCurrentForOnion(Onion $onion) {
		return $this->createQueryBuilder("ow")
			->select("COUNT(ow)")
			->innerJoin("ow.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->andWhere("ow.count > 0")
			->getQuery()->getSingleScalarResult();
	}
}
