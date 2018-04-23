<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\SearchFormType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController {
    public function homepageAction(Request $request) {
        $addedOnions = $this->getRepo("Onion")->findLastAdded(10);
        $checkedOnions = $this->getRepo("Onion")->findLastChecked(10);
        $seenOnions = $this->getRepo("Onion")->findLastSeen(10);

        $popularOnions = [];
        $mostReferedOnions = $this->getRepo("Onion")->findMostReferedAndActive(40);
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

        $searchForm = $this->createSearchForm();

        return $this->render("@App/Default/homepage.html.twig", array(
        	"addedOnions" => $addedOnions,
            "checkedOnions" => $checkedOnions,
            "seenOnions" => $seenOnions,
            "popularOnions" => $popularOnions,
            "searchForm" => $searchForm->createView()
        ));
    }

    private function createSearchForm() {
        return $this->createForm(SearchFormType::class, null, [
            "action" => $this->generateUrl("search")
        ]);
    }

    public function searchAction(Request $request) {
        $form = $this->createSearchForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $query = trim($form->get("query")->getData());

            // URL ?
            if(filter_var($query, FILTER_VALIDATE_URL) !== false) {
                $resource = $this->getRepo("Resource")->findOneByUrl($query);
                if($resource) {
                    return $this->redirecToRoute("resource_show", [
                        "id" => $resource->getId()
                    ]);
                }

                $hash = $this->get("parser")->isOnionUrl($query, true);
                if($hash) {
                    $onion = $this->getRepo("Onion")->findOneByHash($hash);
                    if($onion) {
                        return $this->redirectToRoute("onion_show", [
                            "hash" => $onion->getHash()
                        ]);
                    }
                }
            }

            // Hash ?
            $onion = $this->getRepo("Onion")->findOneByHash($query);
            if($onion) {
                return $this->redirectToRoute("onion_show", [
                    "hash" => $onion->getHash()
                ]);
            }

            if(!empty($query)) {
                return $this->redirectToRoute("onion_index", [
                    "q" => $query
                ]);
            }
        }

        return $this->redirectToRoute("homepage");
    }
}
