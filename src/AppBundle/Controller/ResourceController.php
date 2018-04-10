<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class ResourceController extends BaseController {
    public function indexAction(Request $request) {
        $qb = $this->getRepo("Resource")->createQueryBuilder("r")
            ->select("r");

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

        return $this->render("@App/Resource/index.html.twig", [
            "resources" => $resources,
            "q" => $q,
            "qf" => $qf
        ]);
    }

    public function showAction($id) {
        $resource = $this->getRepo("Resource")->find($id);
        if(!$resource) {
            return $this->redirectToRoute("resource_index");
        }

        $resourceWords = $this->getRepo("ResourceWord")->findCurrentForResource($resource, 200);
        $countResourceWords = $this->getRepo("ResourceWord")->countCurrentForResource($resource);

        $relatedResources = $this->getRepo("Resource")->findRelatedForResource($resource, 10);

        return $this->render("@App/Resource/show.html.twig", [
            "resource" => $resource,
            "resourceWords" => $resourceWords,
            "countResourceWords" => $countResourceWords,
            "relatedResources" => $relatedResources
        ]);
    }

    public function wordsAction(Request $request, $id) {
        $resource = $this->getRepo("Resource")->find($id);
        if(!$resource) {
            return $this->redirectToRoute("resource_index");
        }

        $qb = $this->getRepo("ResourceWord")->createQueryBuilder("rw")
            ->select("rw, w")
            ->innerJoin("rw.resource", "r")
            ->innerJoin("rw.word", "w")
            ->where("r.id = :resourceId")->setParameter("resourceId", $resource->getId())
            ->andWhere("rw.count > 0")
            ->orderBy("rw.count", "DESC")
            ->addOrderBy("w.string", "ASC");

        $resourceWords = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            400
        );

        return $this->render("@App/Resource/words.html.twig", [
            "resource" => $resource,
            "resourceWords" => $resourceWords
        ]);
    }

    public function checkAction(Request $request, $id) {
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

        return $this->redirectToRoute("resource_index");
    }
}
