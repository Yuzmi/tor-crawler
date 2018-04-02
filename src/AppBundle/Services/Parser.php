<?php

namespace AppBundle\Services;

use AppBundle\Services\HtmlParser;
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

    // https://stackoverflow.com/questions/15445285
    public function getUrlContent($url, $options = []) {
        $response = array("success" => false);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30
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

    public function getOnionHashesFromContent($content) {
        $hashes = array();

        if(preg_match_all('#([a-z2-7]{16})\.onion#isU', $content, $matches)) {
            foreach($matches[1] as $hash) {
                $hashes[] = mb_strtolower($hash);
            }
        }

        return array_unique($hashes);
    }

    public function getOnionForHash($hash) {
        $onion = $this->em->getRepository("AppBundle:Onion")->findOneByHash($hash);
        if(!$onion) {
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
            $onions[] = $o;
            $dbHashes[] = $o->getHash();
        }

        $unknownHashes = array_diff($hashes, $dbHashes);
        foreach($unknownHashes as $h) {
            $onion = $this->getOnionForHash($h);
            if($onion) {
                $onions[] = $onion;
            }
        }

        return $onions;
    }

    public function isOnionHash($hash) {
        return preg_match("#^[a-z2-7]{16,56}$#i", $hash);
    }

    public function isOnionUrl($url, $returnHash = false) {
        $hostname = parse_url($url, PHP_URL_HOST);

        if($hostname !== false && preg_match('#([a-z2-7]{16,56})\.onion$#i', $hostname, $match)) {
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

    public function getOnionsFromHtml($html) {
        $hashes = $this->getOnionHashesFromHtml($html);

        $onions = array();
        foreach($hashes as $hash) {
            $onion = $this->getOnionForHash($hash);
            if($onion) {
                $onions[] = $onion;
            }
        }

        return $onions;
    }

    public function getResourceForUrl($url) {
        $save = false;

        $resource = $this->em->getRepository("AppBundle:Resource")->findOnebyUrl($url);
        if(!$resource) {
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
        foreach($unknownUrls as $url) {
            $resource = $this->getResourceForUrl($url);
            if($resource) {
                $resources[] = $resource;
            }
        }

        return $resources;
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

    public function saveResultForResource($result, Resource $resource) {
        if(isset($result["date"]) && $result["date"]) {
            $date = $result["date"];
        } else {
            $date = new \DateTime();
        }

        $resource->setDateChecked($date);

        if($result["success"]) {
            // Title
            $result["title"] = $this->htmlParser->getTitleFromHtml($result["content"]);
            if(!empty($result["title"])) {
                $title = mb_substr($result["title"], 0, 191);
                $resource->setTitle($title);
            }

            // Content size
            $result["length"] = mb_strlen($result["content"]);
            if($result["length"]) {
                $resource->setLastLength($result["length"]);
            }

            // Other data
            $result["onion-hashes"] = $this->getOnionHashesFromContent($result["content"]);
            $result["onion-urls"] = $this->htmlParser->getOnionUrlsFromHtml($result["content"]);
            
            if($resource->getDateLastSeen() < $date) {
                $resource->setDateLastSeen($date);
            }

            // Successes and errors
            $resource->setTotalSuccess($resource->getTotalSuccess() + 1);
            $resource->setCountErrors(0);

            // Data about words in the content
            $dataWords = $this->htmlParser->getWordDataFromHtml($result["content"]);

            $words = $this->em->getRepository("AppBundle:Word")->findForStringsPerString($dataWords["strings"]);

            $existingStrings = [];
            foreach($words as $word) {
                $existingStrings[] = $word->getString();
            }

            // Create new words
            $missingStrings = array_diff($dataWords["strings"], $existingStrings);
            if(count($missingStrings) > 0) {
                foreach($missingStrings as $string) {
                    $word = new Word();
                    $word->setString($string);
                    $word->setLength($dataWords["words"][$string]["length"]);
                    $this->em->persist($word);
                }
                $this->em->flush();

                $words = $this->em->getRepository("AppBundle:Word")->findForStringsPerString($dataWords["strings"]);
            }

            // Update current words for resource
            $resourceWords = $this->em->getRepository("AppBundle:ResourceWord")->findForResourceAndStringsPerString($resource, $dataWords["strings"]);
            foreach($dataWords["strings"] as $string) {
                if(isset($resourceWords[$string])) {
                    $resourceWord = $resourceWords[$string];
                } else {
                    $resourceWord = new ResourceWord();
                    $resourceWord->setResource($resource);
                    $resourceWord->setWord($words[$string]);
                }

                $resourceWord->setCount($dataWords["words"][$string]["count"]);
                $resourceWord->setDateSeen($date);
                $this->em->persist($resourceWord);
            }

            // Update obsolete words for resource
            foreach($resourceWords as $string => $resourceWord) {
                if(!in_array($string, $dataWords["strings"]) && $resourceWord->getCount() > 0) {
                    $resourceWord->setCount(0);
                    $this->em->persist($resourceWord);
                }
            }
        } else {
            // Error
            $error = isset($result["error"]) ? mb_substr($result["error"], 0, 191) : null;
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

        return $result;
    }

    public function parseResource(Resource $resource, $options = []) {
        if(isset($options["result"])) {
            $result = $options["result"];
        } else {
            $result = $this->getUrlContent($resource->getUrl());
        }

        $result = $this->saveResultForResource($result, $resource);

        return $result;
    }

    public function parseUrl($url) {
        $resource = $this->getResourceForUrl($url);
        if($resource) {
            $result = $this->parseResource($resource);
        } else {
            $result = array("success" => false);
        }

        return $result;
    }

    public function parseOnion(Onion $onion, $options = []) {
        $resource = $this->getResourceForOnion($onion);

        $result = $this->parseResource($resource, $options);

        return $result;
    }

    public function shouldBeParsed($element) {
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
    }
}
