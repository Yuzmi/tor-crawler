<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Onion;
use AppBundle\Entity\Word;

class WordRepository extends \Doctrine\ORM\EntityRepository
{
	public function findForStrings($strings) {
		$words = [];
		$foundStrings = [];

		$countStrings = count($strings);
		if($countStrings > 0) {
			$iChunk = 0;
			while($iChunk <= $countStrings) {
				$chunkWords = $this->createQueryBuilder("w")
					->where("w.string IN (:strings)")
					->setParameter("strings", array_slice($strings, $iChunk*200))
					->getQuery()->getResult();

				foreach($chunkWords as $word) {
					if(!in_array($word->getString(), $foundStrings)) {
						$words[] = $word;
						$foundStrings[] = $word->getString();
					}
				}

				$iChunk++;
			}
		}

		return $words;
	}

	public function findForStringsPerString($strings) {
		$words = [];

		$countStrings = count($strings);
		if($countStrings > 0) {
			$iChunk = 0;
			while($iChunk <= $countStrings) {
				$chunkWords = $this->createQueryBuilder("w")
					->where("w.string IN (:strings)")
					->setParameter("strings", array_slice($strings, $iChunk*200))
					->getQuery()->getResult();

				foreach($chunkWords as $word) {
					$words[$word->getString()] = $word;
				}

				$iChunk++;
			}
		}

		return $words;
	}

	public function findRelatedForWord(Word $word, $nb = 10) {
		return $this->createQueryBuilder("w")
			->where("w.string LIKE :string")->setParameter("string", "%".$word->getString()."%")
			->andWhere("w.id != :wordId")->setParameter("wordId", $word->getId())
			->orderBy("w.string", "ASC")
			->setMaxResults($nb)
			->getQuery()->getResult();
	}

	public function findForOnion(Onion $onion) {
		return $this->createQueryBuilder("w")
			->innerJoin("w.resourceWords", "rw")
			->innerJoin("rw.resource", "r")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->getQuery()->getResult();
	}

	public function countResourcesForOnionPerId(Onion $onion) {
		$results = $this->createQueryBuilder("w")
			->select("w.id AS wordId, COUNT(rw.id) AS countResources")
			->innerJoin("w.resourceWords", "rw")
			->innerJoin("rw.resource", "r")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->groupBy("wordId")
			->getQuery()->getResult();

		$counts = [];
		foreach($results as $result) {
			$counts[$result["wordId"]] = $result["countResources"];
		}

		return $counts;
	}

	public function sumCountsForOnionPerId(Onion $onion) {
		$results = $this->createQueryBuilder("w")
			->select("w.id AS wordId, SUM(rw.count) AS sumCount")
			->innerJoin("w.resourceWords", "rw")
			->innerJoin("rw.resource", "r")
			->innerJoin("r.onion", "o")
			->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
			->groupBy("wordId")
			->getQuery()->getResult();

		$sumCounts = [];
		foreach($results as $result) {
			$sumCounts[$result["wordId"]] = $result["sumCount"];
		}

		return $sumCounts;
	}

	public function sumCountsPerId() {
		$results = $this->createQueryBuilder("w")
			->select("w.id AS wordId, SUM(rw.count) AS sumCount")
			->innerJoin("w.resourceWords", "rw")
			->innerJoin("rw.resource", "r")
			->innerJoin("r.onion", "o")
			->groupBy("wordId")
			->getQuery()->getResult();

		$sumCounts = [];
		foreach($results as $result) {
			$sumCounts[$result["wordId"]] = $result["sumCount"];
		}

		return $sumCounts;
	}
}
