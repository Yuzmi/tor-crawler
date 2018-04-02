<?php

namespace AppBundle\Services;

class HtmlParser {
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

    public function getWordDataFromHtml($html) {
        $data = [
        	"strings" => [],
        	"words" => []
        ];

        if(extension_loaded("tidy")) {
            $html = tidy_repair_string($html, [
            	"output-html" => true,
            	"show-body-only" => true
            ]);
        }

        $html = $this->removeTagsFromHtml($html, ["script", "style"]);
        $html = html_entity_decode($html);

        $html = str_replace("<", " <", $html); // Avoid "foo<br>bar" becoming "foobar"
        $text = strip_tags($html);

        $text = str_replace("\xc2\xa0", " ", $text); // Remove $nbsp;
        $text = preg_replace("/[\[\]\{\}\(\),;]/i", " ", $text);
        $text = trim(preg_replace("/(\s)+/", " ", $text));
        $text = mb_strtolower($text);
        
        $words = explode(" ", $text);
        $words = preg_replace("/^[[:punct:]]+/i", "", $words);
        $words = preg_replace("/[[:punct:]]+$/i", "", $words);

        foreach($words as $word) {
        	$length = mb_strlen($word);
        	if(
        		$length > 1 && $length <= 50 
        		&& preg_match("/[a-z]/i", $word)
        	) {
        		if(in_array($word, $data["strings"])) {
        			$data["words"][$word]["count"]++;
        		} else {
        			$data["strings"][] = $word;
        			$data["words"][$word] = [
        				"string" => $word,
        				"length" => $length,
        				"count" => 1
        			];
        		}
        	}
        }

        return $data;
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
