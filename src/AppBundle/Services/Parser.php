<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Onion;
use AppBundle\Entity\Resource;

class Parser {
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    // https://stackoverflow.com/questions/15445285
    public function getUrlContent($url, $onion = true) {
        $response = array("success" => false);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30
        ));

        if($onion) {
            curl_setopt_array($ch, array(
                CURLOPT_PROXY => "http://127.0.0.1:9050/",
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            ));
        }

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

    public function getTitleFromHtml($html) {
        $title = null;

        if(preg_match('#<title>(.*)</title>#isU', $html, $match)) {
            $title = $match[1];
            $title = mb_convert_encoding($title, 'UTF-8');
            $title = html_entity_decode($title);
            $title = preg_replace('/\s+/', ' ', $title);
            $title = trim($title);
        }

        return $title;
    }

    public function getUrlsFromHtml($html) {
        $urls = array();

        preg_match_all('#<a[^>]*href="(.*)"[^>]*>#isU', $html, $matches);

        foreach($matches[1] as $url) {
            if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $urls[] = $url;
            }
        }

        return array_unique($urls);
    }

    public function getOnionUrlsFromHtml($html) {
        $urls = array();

        preg_match_all('#<a[^>]*href="(.*)"[^>]*>#isU', $html, $matches);

        foreach($matches[1] as $url) {
            $url = trim($url);
            if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $hostname = parse_url($url, PHP_URL_HOST);
                if($hostname !== false && preg_match('#\.onion$#isU', $hostname)) {
                    $urls[] = $url;
                }
            }
        }

        return array_unique($urls);
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

    public function isOnionUrl($url, $returnHash = false) {
        $hostname = parse_url($url, PHP_URL_HOST);

        if($hostname !== false && preg_match('#([a-z2-7]{16})\.onion$#i', $hostname, $match)) {
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

        if(!$resource->getOnion()) {
            $onion = $this->getOnionForUrl($url);
            if($onion) {
                $resource->setOnion($onion);
                $save = true;
            }
        }

        if($save) {
            $this->em->persist($resource);
            $this->em->flush();
        }

        return $resource;
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
            // Titre
            $result["title"] = $this->getTitleFromHtml($result["content"]);
            if(!empty($result["title"])) {
                $title = mb_substr($result["title"], 0, 191);
                $resource->setTitle($title);
            }

            // Taille du contenu
            $result["length"] = mb_strlen($result["content"]);
            if($result["length"]) {
                $resource->setLastLength($result["length"]);
            }

            // Données supplémentaires
            // $result["onion-urls"] = $this->getOnionUrlsFromHtml($result["content"]);
            $result["onion-hashes"] = $this->getOnionHashesFromContent($result["content"]);
            
            if($resource->getDateLastSeen() < $date) {
                $resource->setDateLastSeen($date);
            }

            $resource->setTotalSuccess($resource->getTotalSuccess() + 1);
            $resource->setCountErrors(0);
        } else {
            // Erreur
            $error = mb_substr($result["error"], 0, 191);
            $resource->setLastError($error);

            if($resource->getDateError() < $date) {
                $resource->setDateError($date);
            }

            $resource->setCountErrors($resource->getCountErrors() + 1);
        }

        // Enregistrement
        $this->em->persist($resource);
        $this->em->flush();

        return $result;
    }

    public function parseResource(Resource $resource) {
        $result = $this->getUrlContent($resource->getUrl());

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

    public function parseOnion(Onion $onion) {
        $resource = $this->getResourceForOnion($onion);

        $result = $this->parseResource($resource);

        return $result;
    }

    public function parseOnions(array $onions, $multi_exec = false) {
        $onionsByHash = array();
        $resources = array();
        foreach($onions as $onion) {
            $onionsByHash[$onion->getHash()] = $onion;
            $resources[$onion->getHash()] = $this->getResourceForOnion($onion);
        }

        $results = array();
        if(!$multi_exec) {
            foreach($resources as $hash => $r) {
                $results[$hash] = $this->parseResource($r);
            }
        } else {
            $default_options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_PROXY => "http://127.0.0.1:9050/",
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME
            );

            $chs = array();
            foreach($resources as $hash => $r) {
                $chs[$hash] = curl_init();
                curl_setopt_array($chs[$hash], $default_options);
                curl_setopt($chs[$hash], CURLOPT_URL, $r->getUrl());
            }

            $mh = curl_multi_init();
            foreach($chs as &$ch) {
                curl_multi_add_handle($mh, $ch);
            }

            $active = null;
            /*do {
                $mrc = curl_multi_exec($mh, $active);
            } while($mrc == CURLM_CALL_MULTI_PERFORM);

            while($active && $mrc == CURLM_OK) {
                if(curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }*/
            do {
                curl_multi_exec($mh, $active);
                curl_multi_select($mh);
            } while($active);

            foreach($chs as $hash => &$ch) {
                $result = array("success" => false);

                $content = curl_multi_getcontent($ch);
                if($content) {
                    $result["success"] = true;
                    $result["content"] = $content;
                } else {
                    $result["error"] = curl_error($ch);
                }

                $result = $this->saveResultForResource($result, $resources[$hash]);

                $results[$hash] = $result;

                curl_multi_remove_handle($mh, $ch);
                unset($ch);
            }

            curl_multi_close($mh);
        }

        return $results;
    }
}
