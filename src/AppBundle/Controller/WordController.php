<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class WordController extends BaseController {
    public function indexAction(Request $request) {
    	$qb = $this->getRepo("Word")->createQueryBuilder("w")
            ->where("w.count > 0")
            ->orderBy("w.count", "DESC");

        $q = trim($request->query->get("q"));
        if($q != '') {
            $qb->andWhere("w.string LIKE :q");
            $qb->setParameter("q", "%".$q."%");
        }

        $words = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            400
        );

        return $this->render("@App/Word/index.html.twig", [
        	"words" => $words,
            "q" => $q
        ]);
    }

    public function showAction(Request $request, $id) {
        $word = $this->getRepo("Word")->find($id);
        if(!$word) {
            return $this->redirectToRoute("word_index");
        }

        $qb = $this->getRepo("ResourceWord")->createQueryBuilder("rw")
            ->select("rw, r, w")
            ->innerJoin("rw.resource", "r")
            ->innerJoin("rw.word", "w")
            ->where("w.id = :wordId")->setParameter("wordId", $id)
            ->andWhere("rw.count > 0");

        if(!$request->query->get("sort")) {
            $qb->orderBy("rw.count", "DESC");
        }

        $resourceWords = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->query->get("page", 1),
            40
        );

        return $this->render("@App/Word/show.html.twig", [
            "word" => $word,
            "resourceWords" => $resourceWords
        ]);
    }
}
