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
            CURLOPT_CONNECTTIMEOUT => 10,
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

        $content = curl_exec($ch);
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

    public function getOnionForUrl($url) {
        $onion = null;

        $hostname = parse_url($url, PHP_URL_HOST);
        if($hostname !== false && preg_match('#(.{16})\.onion$#i', $hostname, $match)) {
            $onion = $this->getOnionForHash($match[1]);
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
            $resource = new Resource();
            $resource->setUrl($url);
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

    public function parseOnion(Onion $onion) {
        $url = $onion->getUrl();

        $resource = $onion->getResource();
        if(!$resource || $resource->getUrl() != $url) {
            $resource = $this->getResourceForUrl($url);
            $onion->setResource($resource);

            $this->em->persist($onion);
            $this->em->flush();
        }

        $result = $this->parseResource($resource);

        return $result;
    }

    public function parseOnionUrl($url) {
        $resource = $this->getResourceForUrl();

        $result = $this->parseResource($resource);

        return $result;
    }

    public function parseResource(Resource $resource, $options = array()) {
        $now = new \DateTime();

        $resource->setDateChecked($now);

        $result = $this->getUrlContent($resource->getUrl());
        if($result["success"]) {
            // Titre
            $result["title"] = $this->getTitleFromHtml($result["content"]);
            if(!empty($result["title"])) {
                $title = mb_substr($result["title"], 0, 255);
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
            
            $resource->setDateSeen($now);
            $resource->setCountErrors(0);
        } else {
            // Erreur
            $resource->setLastError($result["error"]);
            $resource->setDateError($now);
            $resource->setCountErrors($resource->getCountErrors() + 1);
        }

        // Enregistrement
        $this->em->persist($resource);
        $this->em->flush();

        return $result;
    }
}
