<?php

namespace AppBundle\Services;

use Symfony\Component\DomCrawler\Crawler;
use AppBundle\Services\phpUri;

class HtmlParser {
	public function getTitleFromHtml($html) {
        $title = null;

        $crawler = new Crawler($html);
        $titleNode = $crawler->filter("head title");

        if($titleNode->count() > 0) {
            $title = $titleNode->text();
            $title = mb_convert_encoding($title, 'UTF-8');
            $title = html_entity_decode($title);
            $title = preg_replace('/\s+/', ' ', $title);
            $title = trim($title);
        }

        return $title;
    }

    public function getDescriptionFromHtml($html) {
        $description = null;

        $crawler = new Crawler($html);
        $descriptionNode = $crawler->filter("head meta[name='description']");
        
        if($descriptionNode->count() > 0) {
            $description = $descriptionNode->attr("content");
        }

        return $description;
    }

    public function getUrlsFromHtml($html, $htmlUrl = null) {
        $urls = array();

        $html = str_replace(["&shy;", "&#173;", "&#xAD;"], "", $html);

        $crawler = new Crawler($html);
        $linkNodes = $crawler->filter("a");
        
        for($i=0, $count = count($linkNodes);$i<$count;$i++) {
            $url = trim($linkNodes->eq($i)->attr("href"));

            // Relative to absolute URL
            if($htmlUrl && filter_var($url, FILTER_VALIDATE_URL) === false) {
                $url = phpUri::parse($htmlUrl)->join($url);
            }

            // Remove the fragment
            $url = explode('#', $url)[0];

            if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $urls[] = $url;
            }
        }

        return array_unique($urls);
    }

    public function getOnionUrlsFromHtml($html, $htmlUrl = null) {
        $onionUrls = [];

        $urls = $this->getUrlsFromHtml($html, $htmlUrl);
        foreach($urls as $url) {
            $hostname = parse_url($url, PHP_URL_HOST);
            if($hostname !== false && preg_match('#\.onion$#isU', $hostname)) {
                $onionUrls[] = $url;
            }
        }

        return $onionUrls;
    }

    public function getOnionHashesFromContent($content) {
        $hashes = array();

        if(preg_match_all('#([a-z2-7]{16}|[a-z2-7]{56})\.onion#i', $content, $matches)) {
            foreach($matches[1] as $hash) {
                $hashes[] = strtolower($hash);
            }
        }

        return array_unique($hashes);
    }

    public function getWordsFromHtml($html) {
        $words = [];

        // Was supposed to solve problems but cause problems with entities
        /*if(extension_loaded("tidy")) {
            $html = tidy_repair_string($html, [
            	"output-html" => true,
                "preserve-entities" => true,
            	"show-body-only" => true
            ], "utf8");
        }*/

        $html = $this->removeTagsFromHtml($html, ["script", "style"]);
        $html = html_entity_decode($html, ENT_COMPAT|ENT_HTML5, "UTF-8");

        $html = str_replace("<", " <", $html); // Avoid "foo<br>bar" becoming "foobar"
        $text = strip_tags($html);

        $text = str_replace("\xc2\xa0", " ", $text); // Replace $nbsp;
        $text = mb_strtolower($text);
        $text = preg_replace("/(?!\-)[\p{P}=\$€~\|°]/u", " ", $text);
        $text = trim(preg_replace("/(\s)+/", " ", $text));

        $potentialWords = explode(" ", $text);

        $words = preg_grep("/^[\p{L}][\p{L}\d-]{0,30}[\p{L}\d]$/iu", $potentialWords);

        return $words;
    }

    public function removeTagsFromHtml($html, $tags) {
    	if(empty($html)) return "";

    	$doc = new \DOMDocument();
    	libxml_use_internal_errors(true);
    	$doc->loadHtml($html);

    	foreach($tags as $tag) {
	    	$elements = iterator_to_array($doc->getElementsByTagName($tag));
	    	foreach($elements as $element) {
	    		$element->parentNode->removeChild($element);
	    	}
	    }

    	return $doc->saveHTML();
    }
}
