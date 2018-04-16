<?php

namespace AppBundle\Repository;

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
			->where("o.hash = :hash")->setParameter("hash", $hash)
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
				->where("o.hash IN (:hashes)")->setParameter("hashes", $chunkHashes)
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

	public function findLastAdded($limit = 0) {
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

	public function findPopular($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
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

	public function findListings($limit = 0) {
		$qb = $this->createQueryBuilder("o")
			->select("o, r")
			->leftJoin("o.resource", "r")
			->orderBy("o.countReferedOnions", "DESC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		$resultOnions = $qb->getQuery()->getResult();

		$onions = [];
		foreach($resultOnions as $o) {
			$onions[] = $o[0];
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
}
