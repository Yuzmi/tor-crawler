<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController {
    public function indexAction(Request $request) {
    	$qb = $this->getRepo("Onion")->createQueryBuilder("o")
    		->select("o, r")
    		->innerJoin("o.resource", 'r')
    		->orderBy("o.dateCreated", "DESC");

    	$type = $request->query->get("type");
    	if($type == "valid") {
    		$qb->andWhere("r.dateSeen IS NOT NULL");
            $qb->andWhere("r.countErrors < 3");
    	} elseif($type == "invalid") {
    		$qb->andWhere("r.dateSeen IS NOT NULL");
            $qb->andWhere("r.countErrors >= 3");
    	} elseif($type == "unseen") {
    		$qb->andWhere("r.dateSeen IS NULL");
    	} else {
    		$type = "all";
    	}

        $q = trim($request->query->get("q"));
        $qf = $request->query->get("qf", "any");
        if($q != '') {
            if($qf == "onion") {
                $qb->andWhere("o.hash LIKE :q ");
            } elseif($qf == "title") {
                $qb->andWhere("r.title LIKE :q");
            } else {
                $qb->andWhere("o.hash LIKE :q OR r.title LIKE :q");
            }

            $qb->setParameter("q", "%".$q."%");
        }

    	$query = $qb->getQuery();

    	$paginator  = $this->get('knp_paginator');
    	$pagination = $paginator->paginate(
    		$query,
    		$request->query->get("page", 1),
    		40
    	);

        return $this->render("@App/Default/index.html.twig", array(
        	"pagination" => $pagination,
        	"type" => $type,
            "q" => $q,
            "qf" => $qf
        ));
    }

    public function checkOnionAction(Request $request, $hash) {
    	$onion = $this->get("parser")->getOnionForHash($hash);
    	if($onion) {
    		$result = $this->get("parser")->parseOnion($onion);

    		if($result["success"]) {
    			$this->addFm("Onion \"".$onion->getHash()."\" checked and updated", "success");
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

    	return $this->redirectToRoute("homepage");
    }
}
