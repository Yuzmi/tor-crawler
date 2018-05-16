<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController {
    public function homepageAction(Request $request) {
        $newOnions = $this->getRepo("Onion")->findNewAndSeen(10);
        $checkedOnions = $this->getRepo("Onion")->findLastChecked(10);

        $popularOnions = [];
        $mostReferedOnions = $this->getRepo("Onion")->findMostReferedAndActive(200);
        foreach($mostReferedOnions as $onion) {
            $title = $onion->getResource() ? $onion->getResource()->getTitle() : null;
            if($title) {
                if(!isset($popularOnions[$title])) {
                    $popularOnions[$title] = [
                        "title" => $title,
                        "url" => $this->generateUrl("onion_show", [
                            "hash" => $onion->getHash()
                        ]),
                        "onions" => [$onion]
                    ];
                } else {
                    $popularOnions[$title]["url"] = $this->generateUrl("onion_index", [
                        "q" => $title,
                        "sort" => "o.countRefererOnions",
                        "direction" => "DESC"
                    ]);
                    $popularOnions[$title]["onions"][] = $onion;
                }
            }
        }

        $listingOnions = [];
        $mostRefererOnions = $this->getRepo("Onion")->findMostRefererAndActive(200);
        foreach($mostRefererOnions as $onion) {
            $title = $onion->getResource() ? $onion->getResource()->getTitle() : null;
            if($title) {
                if(!isset($listingOnions[$title])) {
                    $listingOnions[$title] = [
                        "title" => $title,
                        "url" => $this->generateUrl("onion_show", [
                            "hash" => $onion->getHash()
                        ]),
                        "onions" => [$onion]
                    ];
                } else {
                    $listingOnions[$title]["url"] = $this->generateUrl("onion_index", [
                        "q" => $title,
                        "sort" => "o.countReferedOnions",
                        "direction" => "DESC"
                    ]);
                    $listingOnions[$title]["onions"][] = $onion;
                }
            }
        }

        return $this->render("@App/Default/homepage.html.twig", array(
        	"newOnions" => $newOnions,
            "checkedOnions" => $checkedOnions,
            "popularOnions" => $popularOnions,
            "listingOnions" => $listingOnions
        ));
    }

    public function searchAction(Request $request) {
        $query = $request->query->get("q");
        $queryExplode = preg_split("/\s+/", $query);

        $queryTerms = [];
        foreach($queryExplode as $term) {
            $term = trim($term);
            if($term != "") {
                $queryTerms[] = $term;
            }
        }

        $resources = null;

        $countTerms = count($queryTerms);
        if(count($queryTerms) > 0) {
            $qb = $this->getRepo("Resource")->createQueryBuilder("r")
                ->select("r")
                ->where("r.dateLastSeen > :sevenDaysAgo")
                ->setParameter("sevenDaysAgo", new \DateTime("7 days ago"))
                ->orderBy("r.relevance", "DESC");

            for($i=0;$i<$countTerms;$i++) {
                $term = $queryTerms[$i];
                $qb->andWhere("r.url LIKE :q".$i." OR r.title LIKE :q".$i);
                $qb->setParameter("q".$i, "%".$term."%");
            }

            $resources = $this->get('knp_paginator')->paginate(
                $qb->getQuery(),
                $request->query->get("page", 1),
                50
            );
        }

        return $this->render("@App/Default/search.html.twig", array(
            "q" => $query,
            "resources" => $resources
        ));
    }
}
