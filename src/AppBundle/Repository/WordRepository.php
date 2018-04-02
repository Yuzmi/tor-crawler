<?php

namespace AppBundle\Repository;

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
}
