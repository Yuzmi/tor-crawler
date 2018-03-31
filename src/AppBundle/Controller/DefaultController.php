<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController {
    public function onionsAction(Request $request) {
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

        return $this->render("@App/Default/onions.html.twig", array(
        	"onions" => $onions,
        	"type" => $type,
            "q" => $q,
            "qf" => $qf
        ));
    }

    public function resourcesAction(Request $request) {
        $qb = $this->getRepo("Resource")->createQueryBuilder("r")
            ->select("r");

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
            if($qf == "url") {
                $qb->andWhere("r.url LIKE :q");
            } elseif($qf == "title") {
                $qb->andWhere("r.title LIKE :q");
            } else {
                $qb->andWhere("r.url LIKE :q OR r.title LIKE :q");
            }

            $qb->setParameter("q", "%".$q."%");
        }

        if(!$request->query->get("sort")) {
            $qb->orderBy("r.dateCreated", "DESC");
        }

        $resources = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            40
        );

        return $this->render("@App/Default/resources.html.twig", array(
            "resources" => $resources,
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

    	return $this->redirectToRoute("onions");
    }

    public function checkResourceAction(Request $request, $id) {
        $resource = $this->getRepo("Resource")->find($id);
        if($resource) {
            $result = $this->get("parser")->parseResource($resource);

            if($result["success"]) {
                $this->addFm("URL \"".$resource->getUrl()."\" checked", "success");
            } else {
                $this->addFm("Couldn't check URL \"".$resource->getUrl()."\"", "danger");
            }
        } else {
            $this->addFm("Error", "danger");
        }

        $referer = $request->headers->get('referer');
        if($referer) {
            $this->redirect($referer);
        }

        return $this->redirectToRoute("resources");
    }
}
