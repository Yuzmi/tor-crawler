<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OnionController extends BaseController {
    public function indexAction(Request $request) {
    	$qb = $this->getRepo("Onion")->createQueryBuilder("o")
    		->select("o, r")
    		->leftJoin("o.resource", 'r');

    	$type = $request->query->get("type", "active");
    	if($type == "seen") {
    		$qb->andWhere("r.dateFirstSeen IS NOT NULL");
    	} elseif($type == "unseen") {
    		$qb->andWhere("r.dateFirstSeen IS NULL");
    	} else {
    		$type = "active";
            $qb->andWhere("r.dateLastSeen >= :sevenDaysAgo");
            $qb->setParameter("sevenDaysAgo", new \DateTime("7 days ago"));
    	}

        $q = trim($request->query->get("q"));
        $qf = $request->query->get("qf", "any");
        if($q != '') {
            if($qf == "onion") {
                $qb->andWhere("o.hash LIKE :q");
            } elseif($qf == "title") {
                $qb->andWhere("r.title LIKE :q");
            } else {
                $qb->andWhere("o.hash LIKE :q OR r.title LIKE :q");
            }

            $qb->setParameter("q", "%".$q."%");
        }

        if(!$request->query->get("sort")) {
            $qb->orderBy("o.dateCreated", "DESC");
        }

    	$onions = $this->get('knp_paginator')->paginate(
    		$qb->getQuery(),
    		$request->query->get("page", 1),
    		40
    	);

        return $this->render("@App/Onion/index.html.twig", array(
        	"onions" => $onions,
        	"type" => $type,
            "q" => $q,
            "qf" => $qf
        ));
    }

    public function dumpAction(Request $request) {
        $qb = $this->getRepo("Onion")->createQueryBuilder("o")
            ->select("o.hash")
            ->leftJoin("o.resource", "r")
            ->orderBy("o.hash", "ASC");

        $type = $request->query->get("type", "active");
        if($type == "active") {
            $qb->andWhere("r.dateLastSeen >= :sevenDaysAgo");
            $qb->setParameter("sevenDaysAgo", new \DateTime("7 days ago"));
        } elseif($type == "seen") {
            $qb->andWhere("r.dateFirstSeen IS NOT NULL");
        } elseif($type == "unseen") {
            $qb->andWhere("r.dateFirstSeen IS NULL");
        } elseif($type == "unchecked") {
            $qb->andWhere("r.dateChecked IS NULL");
        }

        $onions = $qb->getQuery()->getArrayResult();

        $hashes = array_column($onions, "hash");

        $format = $request->query->get("format");
        if($format == "json") {
            return new JsonResponse($hashes);
        } elseif($format == "text" || $format == "txt") {
            $text = implode("\n", $hashes);
            $response = new Response($text);
            $response->headers->set('Content-Type', 'text/plain');
            return $response;
        } else {
            return $this->render("@App/Onion/dump.html.twig", array(
                "onions" => $onions
            ));
        }
    }

    public function showAction($hash) {
        $onion = $this->getRepo("Onion")->findOneByHash($hash);
        if(!$onion) {
            return $this->redirectToRoute("onion_index");
        }

        $onionWords = $this->getRepo("OnionWord")->findCurrentForOnion($onion, 200);
        $countOnionWords = $this->getRepo("OnionWord")->countCurrentForOnion($onion);
        $resources = $this->getRepo("Resource")->findForOnion($onion, 10);
        $countResources = $this->getRepo("Resource")->countForOnion($onion);

        return $this->render("@App/Onion/show.html.twig", [
            "onion" => $onion,
            "onionWords" => $onionWords,
            "countOnionWords" => $countOnionWords,
            "resources" => $resources,
            "countResources" => $countResources
        ]);
    }

    public function resourcesAction(Request $request, $hash) {
        $onion = $this->getRepo("Onion")->findOneByHash($hash);
        if(!$onion) {
            return $this->redirectToRoute("onion_index");
        }

        $qb = $this->getRepo("Resource")->createQueryBuilder("r")
            ->innerJoin("r.onion", "o")
            ->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
            ->orderBy("r.url", "ASC");

        $resources = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            40
        );

        return $this->render("@App/Onion/resources.html.twig", array(
            "onion" => $onion,
            "resources" => $resources
        ));
    }

    public function wordsAction(Request $request, $hash) {
        $onion = $this->getRepo("Onion")->findOneByHash($hash);
        if(!$onion) {
            return $this->redirectToRoute("onion_index");
        }

        $qb = $this->getRepo("OnionWord")->createQueryBuilder("ow")
            ->select("ow, w")
            ->innerJoin("ow.onion", "o")
            ->innerJoin("ow.word", "w")
            ->where("o.id = :onionId")->setParameter("onionId", $onion->getId())
            ->andWhere("ow.count > 0")
            ->orderBy("ow.count", "DESC")
            ->addOrderBy("w.string", "ASC");

        $onionWords = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            400
        );

        return $this->render("@App/Onion/words.html.twig", array(
            "onion" => $onion,
            "onionWords" => $onionWords
        ));
    }

    public function checkAction(Request $request, $hash) {
    	$onion = $this->get("parser")->getOnionForHash($hash);
    	if($onion) {
    		$result = $this->get("parser")->parseOnion($onion);

    		if($result["success"]) {
    			$this->addFm("Onion \"".$onion->getHash()."\" checked", "success");
    		} else {
    			$this->addFm("Couldn't check onion \"".$onion->getHash()."\"", "danger");
    		}
    	} else {
    		$this->addFm("Error", "danger");
    	}

        $referer = $request->headers->get('referer');
        if($referer) {
            $this->redirect($referer);
        }

    	return $this->redirectToRoute("onion_index");
    }
}
