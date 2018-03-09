<?php

namespace AppBundle\Repository;

class OnionRepository extends \Doctrine\ORM\EntityRepository
{
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
}
