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
}
