<?php

use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;

class NewsApi {

    //sort by date    
    public function mySort($a, $b) {
        return $a->datePublished < $b->datePublished;
    }

    public function getNewsCarousel($topik) {
        //$topik = 'islam+sholat+muslim';
        $url = 'https://api.cognitive.microsoft.com/bing/v5.0/news/search?q=' . $topik . '&count=50&offset=0&mkt=en-ID&safeSearch=Moderate';
        $response = \Httpful\Request::get($url)->addHeader('Ocp-Apim-Subscription-Key', 'ddc6d5ceb87b468d9ec9ae1d99cc8da6')->send();
        $column_templates = array();
        $daftarBerita = $response->body->value;
        usort($daftarBerita, array($this, "mySort"));
        foreach ($daftarBerita as $berita) {
            if (isset($berita->image)) {
                $array_action_builders = array();
                $uri = $berita->url;
                $array_action_builders[] = new UriTemplateActionBuilder("Baca Berita", $uri);
                $string = $berita->name;
                $string = (strlen($string) > 55) ? substr($string, 0, 55) . '...' : $string;
                $item01 = new CarouselColumnTemplateBuilder($berita->provider[0]->name, $string, $berita->image->thumbnail->contentUrl, $array_action_builders);
                array_push($column_templates, $item01);
            }
        }

        $lima = array_slice($column_templates, 0, 5);
        $carouselbuilder = new CarouselTemplateBuilder($lima);
        $messageBuilder = new TemplateMessageBuilder("Muslim News", $carouselbuilder);
        return $messageBuilder;
    }
    
     public function countNews($topik) {
        //$topik = 'islam+sholat+muslim';
        $url = 'https://api.cognitive.microsoft.com/bing/v5.0/news/search?q=' . $topik . '&count=50&offset=0&mkt=en-ID&safeSearch=Moderate';
        $response = \Httpful\Request::get($url)->addHeader('Ocp-Apim-Subscription-Key', 'ddc6d5ceb87b468d9ec9ae1d99cc8da6')->send();
        $column_templates = array();
        $daftarBerita = $response->body->value;
        return count($daftarBerita);
    }

    public function getNewsCarouselMore($topik) {
        //$topik = 'islam+sholat+muslim';
        $url = 'https://api.cognitive.microsoft.com/bing/v5.0/news/search?q=' . $topik . '&count=50&offset=0&mkt=en-ID&safeSearch=Moderate';
        $response = \Httpful\Request::get($url)->addHeader('Ocp-Apim-Subscription-Key', 'ddc6d5ceb87b468d9ec9ae1d99cc8da6')->send();
        $column_templates = array();
        $daftarBerita = $response->body->value;
        usort($daftarBerita, array($this, "mySort"));
        foreach ($daftarBerita as $berita) {
            if (isset($berita->image)) {
                $array_action_builders = array();
                $uri = $berita->url;
                $array_action_builders[] = new UriTemplateActionBuilder("Baca Berita", $uri);
                $string = $berita->name;
                $string = (strlen($string) > 55) ? substr($string, 0, 55) . '...' : $string;
                $item01 = new CarouselColumnTemplateBuilder($berita->provider[0]->name, $string, $berita->image->thumbnail->contentUrl, $array_action_builders);
                array_push($column_templates, $item01);
            }
        }
        $lima = array_slice($column_templates, 5, 5);
        $carouselbuilder = new CarouselTemplateBuilder($lima);
        $messageBuilder = new TemplateMessageBuilder("Muslim News", $carouselbuilder);
        return $messageBuilder;
    }

}
