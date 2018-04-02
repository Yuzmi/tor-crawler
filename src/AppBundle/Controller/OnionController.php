<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class OnionController extends BaseController {
    public function indexAction(Request $request) {
    	$qb = $this->getRepo("Onion")->createQueryBuilder("o")
    		->select("o, r")
    		->leftJoin("o.resource", 'r');

    	$type = $request->query->get("type", "all");
    	if($type == "seen") {
    		$qb->andWhere("r.dateFirstSeen IS NOT NULL");
    	} elseif($type == "unseen") {
    		$qb->andWhere("r.dateFirstSeen IS NULL");
    	} else {
    		$type = "all";
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
