<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Onion;

class OnionRepository extends \Doctrine\ORM\EntityRepository
{
	public function findAll() {
		return $this->createQueryBuilder("o")
			->select("o,r")
			->leftJoin("o.resource", "r")
			->getQuery()->getResult();
	}

	public function findOneByHash($hash) {
		return $this->createQueryBuilder("o")
			->select("o,r")
			->leftJoin("o.resource", "r")
			->where("o.hash = :hash")
			->setParameter("hash", $hash)
			->getQuery()->getOneOrNullResult();
	}

	public function findForHashes($hashes) {
		$onions = [];

		while(!empty($hashes)) {
			$chunkHashes = [];

			$i = 0;
			while($i < 500 && !empty($hashes)) {
				$chunkHashes[] = array_shift($hashes);
				$i++;
			}

			$chunkOnions = $this->createQueryBuilder("o")
				->select("o,r")
				->leftJoin("o.resource", "r")
				->where("o.hash IN (:hashes)")
				->setParameter("hashes", $chunkHashes)
				->getQuery()->getResult();
			foreach($chunkOnions as $o) {
				$onions[] = $o;
			}
		}

		return $onions;
	}

	public function findIds() {
		return $this->createQueryBuilder("o")
			->select("o.id")
			->getQuery()->getScalarResult();
	}

	public function findNew($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->orderBy("o.dateCreated", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function findLastChecked($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->where("r.dateChecked IS NOT NULL")
			->orderBy("r.dateChecked", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function findLastSeen($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->where("r.dateLastSeen IS NOT NULL")
			->orderBy("r.dateLastSeen", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function findMostReferedAndActive($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->where("r.dateLastSeen > :sevenDaysAgo")
			->setParameter("sevenDaysAgo", new \DateTime("7 days ago"))
			->orderBy("o.countRefererOnions", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		$resultOnions = $qb->getQuery()->getResult();

		$onions = [];
		foreach($resultOnions as $o) {
			$onions[] = $o;
		}

		return $onions;
	}

	public function findMostRefererAndActive($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->where("r.dateLastSeen > :sevenDaysAgo")
			->setParameter("sevenDaysAgo", new \DateTime("7 days ago"))
			->orderBy("o.countReferedOnions", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		$resultOnions = $qb->getQuery()->getResult();

		$onions = [];
		foreach($resultOnions as $o) {
			$onions[] = $o;
		}

		return $onions;
	}

	public function countReferedOnionsPerId() {
		$result = $this->createQueryBuilder("o")
			->select("o.id AS id, COUNT(ro.id) AS countRefered")
			->leftJoin("o.referedOnions", "ro")
			->groupBy("o.id")
			->getQuery()->getResult();

		$counts = [];
		foreach($result as $row) {
			$counts[$row["id"]] = $row["countRefered"];
		}

		return $counts;
	}

	public function countRefererOnionsPerId() {
		$result = $this->createQueryBuilder("o")
			->select("o.id AS id, COUNT(ro.id) AS countReferer")
			->leftJoin("o.refererOnions", "ro")
			->groupBy("o.id")
			->getQuery()->getResult();

		$counts = [];
		foreach($result as $row) {
			$counts[$row["id"]] = $row["countReferer"];
		}

		return $counts;
	}

	public function countRefererOnionsForOnion(Onion $onion) {
		return $this->createQueryBuilder("o")
			->select("COUNT(ro)")
			->leftJoin("o.refererOnions", "ro")
			->where("o.id = :onionId")
			->setParameter("onionId", $onion->getId())
			->getQuery()->getSingleScalarResult();
	}
}
