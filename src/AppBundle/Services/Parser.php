<?php

namespace AppBundle\Services;

use AppBundle\Services\HtmlParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Onion;
use AppBundle\Entity\Resource;
use AppBundle\Entity\ResourceError;
use AppBundle\Entity\ResourceWord;
use AppBundle\Entity\Word;

class Parser {
    private $em;
    private $htmlParser;

    public function __construct(EntityManagerInterface $em, HtmlParser $htmlParser) {
        $this->em = $em;
        $this->htmlParser = $htmlParser;
    }

    private $userAgents = [
        "Mozilla/5.0 (Windows NT 6.2; rv:20.0) Gecko/20121202 Firefox/20.0",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.12",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20130330 Firefox/21.0",
        "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0"
    ];

    // https://stackoverflow.com/questions/15445285
    public function getUrlData($url, $options = []) {
        $response = array("success" => false);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]
        ));

        curl_setopt_array($ch, array(
            CURLOPT_PROXY => "http://127.0.0.1:9050/",
            CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
        ));

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 
            function($curl, $header) use(&$headers) {
                $headers[] = trim($header);
                return strlen($header);
            }
        );

        $time_start = microtime(true);
        $content = curl_exec($ch);
        $time_end = microtime(true);

        $response["duration"] = $time_end - $time_start;

        if($content === false) {
            $response["error"] = curl_error($ch);
        } else {
            $response["headers"] = $headers;
            $response["content"] = $content;
            $response["success"] = true;
        }
        curl_close($ch);

        return $response;
    }

    public function parseUrl($url, $options = []) {
        if(isset($options["data"])) {
            $data = $options["data"];
        } else {
            $data = $this->getUrlData($url, $options);
        }

        // Get resource for URL
        $resource = null;
        if(isset($options["resource"])) {
            $resource = $options["resource"];
        } else {
            $resource = $this->getResourceForUrl($url);
        }
        $data["resource"] = $resource;

        // Get onion for URL
        $onion = null;
        if(isset($options["onion"])) {
            $onion = $options["onion"];
        } elseif($resource && $resource->getOnion()) {
            $onion = $resource->getOnion();
        } else {
            $onion = $this->getOnionForUrl($url);
        }
        $data["onion"] = $onion;

        if($data["success"]) {
            // Title
            $data["title"] = $this->htmlParser->getTitleFromHtml($data["content"]);

            // Length
            $data["length"] = mb_strlen($data["content"]);

            // Get onions from content
            $data["onion-hashes"] = $this->htmlParser->getOnionHashesFromContent($data["content"]);
            $data["onions"] = $this->getOnionsForHashes($data["onion-hashes"]);

            // Get resources from content
            $data["onion-urls"] = $this->htmlParser->getOnionUrlsFromHtml($data["content"]);
            $data["resources"] = $this->getResourcesForUrls($data["onion-urls"]);
        }

        if($resource) {
            $this->saveDataForResource($data, $resource);
        }

        return $data;
    }

    public function parseResource(Resource $resource, $options = []) {
        $options["resource"] = $resource;

        $data = $this->parseUrl($resource->getUrl(), $options);

        return $data;
    }

    public function parseOnion(Onion $onion, $options = []) {
        $options["onion"] = $onion;

        $resource = $this->getResourceForOnion($onion);
        if($resource) {
            $data = $this->parseResource($resource, $options);
        } else {
            $data = null;
        }

        return $data;
    }

    public function parseHash($hash) {
        $data = null;

        $onion = $this->getOnionForHash($hash);
        if($onion) {
            $data = $this->parseOnion($onion);
        }

        return $data;
    }

    public function saveDataForResource($data, Resource $resource) {
        if(isset($data["date"]) && $data["date"]) {
            $date = $data["date"];
        } else {
            $date = new \DateTime();
        }

        if($date < $resource->getDateChecked()) {
            return;
        }

        $resource->setDateChecked($date);

        if($data["success"]) {
            // Title
            $title = mb_substr($data["title"], 0, 191);
            if(!empty($title)) {
                $resource->setTitle($title);
            }

            // Content length
            if($data["length"] > 0) {
                $resource->setLastLength($data["length"]);
            }

            // Date seen
            $resource->setDateSeen($date);

            // Successes and errors
            $resource->setTotalSuccess($resource->getTotalSuccess() + 1);
            $resource->setCountErrors(0);

            // --- Onions --- //

            $onions = $data["onions"];

            // Add new refered onions
            foreach($onions as $o) {
                if($resource->getOnion()->getId() != $o->getId()
                && !$resource->getOnion()->getReferedOnions()->contains($o)) {
                    $resource->getOnion()->addReferedOnion($o);
                }
            }

            // --- Resources --- //

            $resources = $data["resources"];

            $resourceIds = [];
            foreach($resources as $r) {
                $resourceIds[] = $r->getId();
            }

            // Remove obsolete refered resources
            $referedResources = $resource->getReferedResources();
            foreach($referedResources as $r) {
                if(!in_array($r->getId(), $resourceIds)) {
                    $resource->removeReferedResource($r);
                }
            }

            // Add new refered resources
            foreach($resources as $r) {
                if($resource->getId() != $r->getId()
                && !$resource->getReferedResources()->contains($r)) {
                    $resource->addReferedResource($r);
                }
            }

            // --- Words --- //

            // Words in content
            $wordsInContent = $this->htmlParser->getWordsFromHtml($data["content"]);
            $countWordsPerString = array_count_values($wordsInContent);

            // Get/create words
            $words = $this->getWordsForStrings(array_unique($wordsInContent));
            
            $existingWordIds = [];
            $resourceWords = $this->em->getRepository("AppBundle:ResourceWord")->findForResource($resource);
            foreach($resourceWords as $resourceWord) {
                if($words->contains($resourceWord->getWord())) {
                    // Update the word
                    $resourceWord->setCount($countWordsPerString[$resourceWord->getWord()->getString()]);
                    $resourceWord->setDateSeen($date);
                } else {
                    // Obsolete word
                    $resourceWord->setCount(0);
                }
                $this->em->persist($resourceWord);
                $existingWordIds[] = $resourceWord->getWord()->getId();
            }

            // Create new words for the resource
            foreach($words as $word) {
                if(!in_array($word->getId(), $existingWordIds)) {
                    $resourceWord = new ResourceWord();
                    $resourceWord->setResource($resource);
                    $resourceWord->setWord($word);
                    $resourceWord->setCount($countWordsPerString[$word->getString()]);
                    $resourceWord->setDateSeen($date);
                    $this->em->persist($resourceWord);
                }
            }
        } else {
            // Error
            $error = isset($data["error"]) ? mb_substr($data["error"], 0, 191) : null;
            $resource->setLastError($error);

            if($resource->getDateError() < $date) {
                $resource->setDateError($date);
            }

            $resource->setCountErrors($resource->getCountErrors() + 1);

            // ResourceError
            $rError = $this->em->getRepository("AppBundle:ResourceError")->findOneBy([
                "label" => $error,
                "resource" => $resource
            ]);

            $newError = false;
            if(!$rError) {
                $rError = new ResourceError();
                $rError->setLabel($error);
                $rError->setResource($resource);
                $resource->addError($rError);
                $newError = true;
            }

            $rError->setCount($rError->getCount() + 1);
            $rError->setDateLastSeen(new \DateTime());

            if(!$newError) {
                $this->em->persist($rError);
            }
        }

        // Enregistrement
        $this->em->persist($resource);
        $this->em->flush();
    }

    public function isOnionHash($hash) {
        return preg_match("#^[a-z2-7]{16}|[a-z2-7]{56}$#i", $hash);
    }

    public function isOnionUrl($url, $returnHash = false) {
        $hostname = parse_url($url, PHP_URL_HOST);

        if($hostname !== false && preg_match('#([a-z2-7]{16}|[a-z2-7]{56})\.onion$#i', $hostname, $match)) {
            return $returnHash ? $match[1] : true;
        }

        return false;
    }

    public function getOnionForUrl($url) {
        $onion = null;

        if($hash = $this->isOnionUrl($url, true)) {
            $onion = $this->getOnionForHash($hash);
        }

        return $onion;
    }

    public function getOnionForHash($hash) {
        $onion = $this->em->getRepository("AppBundle:Onion")->findOneByHash($hash);
        if(!$onion) {
            if(!$this->isOnionHash($hash)) {
                return null;
            }

            $onion = new Onion();
            $onion->setHash($hash);
            $this->em->persist($onion);
            $this->em->flush();
        }
        return $onion;
    }

    public function getOnionsForHashes($hashes) {
        $onions = [];

        $dbOnions = $this->em->getRepository("AppBundle:Onion")->findForHashes($hashes);
        $dbHashes = [];
        foreach($dbOnions as $o) {
            $onions[$o->getHash()] = $o;
            $dbHashes[] = $o->getHash();
        }

        $unknownHashes = array_diff($hashes, $dbHashes);
        $newOnions = $this->createOnionsForHashes($unknownHashes);
        foreach($newOnions as $o) {
            $onions[$o->getHash()] = $o;
        }

        return $onions;
    }

    public function createOnionsForHashes($hashes) {
        $onions = [];

        $i = 0;
        foreach($hashes as $hash) {
            if($this->isOnionHash($hash)) {
                $onion = new Onion();
                $onion->setHash($hash);

                $onions[] = $onion;
                $this->em->persist($onion);

                $i++;
                if($i%100 == 0) {
                    $this->em->flush();
                }
            }
        }

        $this->em->flush();

        return $onions;
    }

    public function getResourceForOnion(Onion $onion) {
        $resource = $onion->getResource();
        
        if(!$resource || $resource->getUrl() != $onion->getUrl()) {
            $resource = $this->getResourceForUrl($onion->getUrl());
            $onion->setResource($resource);

            $this->em->persist($onion);
            $this->em->flush();
        }

        return $resource;
    }

    public function getResourceForUrl($url) {
        $save = false;

        $resource = $this->em->getRepository("AppBundle:Resource")->findOneByUrl($url);
        if(!$resource) {
            if(!$this->isOnionUrl($url)) {
                return null;
            }

            $resource = new Resource($url);
            $save = true;
        }

        $onion = $resource->getOnion();
        if(!$onion) {
            $onion = $this->getOnionForUrl($url);
            if($onion) {
                $resource->setOnion($onion);
                $save = true;
            }
        }

        if($onion) {
            if(!$onion->getResource() && $onion->getUrl() == $resource->getUrl()) {
                $onion->setResource($resource);
                $this->em->persist($onion);
                $save = true;
            }
        }

        if($save) {
            $this->em->persist($resource);
            $this->em->flush();
        }

        return $resource;
    }

    public function getResourcesForUrls($urls) {
        $resources = [];

        $dbResources = $this->em->getRepository("AppBundle:Resource")->findForUrls($urls);
        $dbUrls = [];
        foreach($dbResources as $r) {
            $resources[] = $r;
            $dbUrls[] = $r->getUrl();
        }

        $unknownUrls = array_diff($urls, $dbUrls);
        $newResources = $this->createResourcesForUrls($unknownUrls);
        foreach($newResources as $r) {
            $resources[] = $r;
        }

        return $resources;
    }

    public function createResourcesForUrls($urls) {
        $hashes = [];
        $urlsPerHash = [];

        foreach($urls as $url) {
            $hash = $this->isOnionUrl($url, true);
            if($hash !== false) {
                $hashes[] = $hash;
                $urlsPerHash[$hash] = $url;
            }
        }

        $onions = $this->getOnionsForHashes($hashes);
        $resources = [];

        $i = 0;
        foreach($urlsPerHash as $hash => $url) {
            if(isset($onions[$hash])) {
                $resource = new Resource($url);
                $resource->setOnion($onions[$hash]);

                $resources[] = $resource;
                $this->em->persist($resource);

                $i++;
                if($i%100 == 0) {
                    $this->em->flush();
                }
            }
        }

        $this->em->flush();

        return $resources;
    }

    public function getWordsForStrings($strings) {
        $words = $this->em->getRepository("AppBundle:Word")->findForStrings($strings);
        
        $existingStrings = [];
        foreach($words as $word) {
            $existingStrings[] = $word->getString();
        }

        $addedStrings = [];
        foreach($strings as $string) {
            if(!in_array($string, $existingStrings, true) 
            && !in_array($string, $addedStrings, true)) {
                $word = new Word($string);
                $this->em->persist($word);
                $words[] = $word;
                $addedStrings[] = $string;
            }
        }
        if(count($addedStrings) > 0) {
            $this->em->flush();
        }

        return new ArrayCollection($words);
    }

    /*public function shouldBeParsed($element) {
        $now = new \DateTime();

        if($element instanceof Onion) {
            $resource = $element->getResource();
        } elseif($element instanceof Resource) {
            $resource = $element;
        } else {
            return false;
        }

        if(!$resource || !$resource->getDateChecked()) {
            return true;
        }

        if($resource->getCountErrors() < 10) {
            return true;
        }

        if($resource->getDateLastSeen() > new \DateTime("7 days ago")) {
            return true;
        }

        $sevenDaysOld = clone $resource->getDateCreated();
        $sevenDaysOld->add(date_interval_create_from_date_string('7 days'));
        if($now < $sevenDaysOld) {
            return true;
        }

        return false;
    }*/
}
