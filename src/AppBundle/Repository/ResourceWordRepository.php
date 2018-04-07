<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Resource;

class ResourceWordRepository extends \Doctrine\ORM\EntityRepository
{
	public function findForResourcePerString(Resource $resource, $strings) {
		$resourceWords = $this->createQueryBuilder("rw")
			->select("rw, w")
			->innerJoin("rw.resource", "r")
			->innerJoin("rw.word", "w")
			->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
			->getQuery()->getResult();

		$rWords = [];
		foreach($resourceWords as $rw) {
			$rWords[$rw->getWord()->getString()] = $rw;
		}

		return $rWords;
	}

	public function findForResourceAndStrings(Resource $resource, $strings) {
		$rWords = [];

		$countStrings = count($strings);
		if($countStrings > 0) {
			$iChunk = 0;
			while($iChunk <= $countStrings) {
				$chunkWords = $this->createQueryBuilder("rw")
					->select("rw, w")
					->innerJoin("rw.resource", "r")
					->innerJoin("rw.word", "w")
					->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
					->andWhere("w.string IN (:strings)")->setParameter("strings", array_slice($strings, $iChunk*200))
					->getQuery()->getResult();

				foreach($chunkWords as $rWord) {
					$rWords[] = $rWord;
				}

				$iChunk++;
			}
		}

		return $rWords;
	}

	public function findForResourceAndStringsPerString(Resource $resource, $strings) {
		$rWords = [];

		$countStrings = count($strings);
		if($countStrings > 0) {
			$iChunk = 0;
			while($iChunk <= $countStrings) {
				$chunkWords = $this->createQueryBuilder("rw")
					->select("rw, w")
					->innerJoin("rw.resource", "r")
					->innerJoin("rw.word", "w")
					->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
					->andWhere("w.string IN (:strings)")->setParameter("strings", array_slice($strings, $iChunk*200))
					->getQuery()->getResult();

				foreach($chunkWords as $rWord) {
					$rWords[$rWord->getWord()->getString()] = $rWord;
				}

				$iChunk++;
			}
		}

		return $rWords;
	}

	public function findForResource(Resource $resource) {
		return $this->createQueryBuilder("rw")
			->select("rw, w")
			->innerJoin("rw.resource", "r")
			->innerJoin("rw.word", "w")
			->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
			->orderBy("rw.count", "DESC")
			->addOrderBy("w.string", "ASC")
			->getQuery()->getResult();
	}

	public function findCurrentForResource(Resource $resource, $limit = 0) {
		$qb = $this->createQueryBuilder("rw")
			->select("rw, w")
			->innerJoin("rw.resource", "r")
			->innerJoin("rw.word", "w")
			->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
			->andWhere("rw.count > 0")
			->orderBy("rw.count", "DESC")
			->addOrderBy("w.string", "ASC");

		if($limit > 0) {
			$qb->setMaxResults($limit);
		}

		return $qb->getQuery()->getResult();
	}

	public function countCurrentForResource(Resource $resource) {
		return $this->createQueryBuilder("rw")
			->select("COUNT(rw)")
			->innerJoin("rw.resource", "r")
			->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
			->andWhere("rw.count > 0")
			->getQuery()->getSingleScalarResult();
	}
}
