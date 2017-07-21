<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class NewsSender extends CI_Controller {

    private $bot;
    private $newsAPI;

    function __construct() {
        parent::__construct();
        include APPPATH . 'api/NewsApi.php';
        $this->load->model('tebakkode_m');
        $httpClient = new CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
        $this->bot = new LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
        $this->newsAPI = new NewsApi();
    }

    public function index() {
        //filter hanya get request yang diperbolehkan
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo 'news seeeeeeeeeeeendeeeeer';
            $listUser = $this->tebakkode_m->getAllUser();
            foreach ($listUser as $user) {
                $this->sendNewsCategory($user->user_id);
            }
            header('HTTP/1.1 400 Only POST method allowed');
            exit;
        }
    }

    public function sendNewsCategory($to) {
        $message = 'Selamat Pagi kak, hiken kasih daftar berita terbaru seputar muslim, Selamat Membaca.';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($to, $textMessageBuilder);
        $column_templates = array();
        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news berita+muslim+indonesia');
        $item01 = new CarouselColumnTemplateBuilder("Berita Muslim", 'Berita Seputar Dunia Muslim', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news doa+dzikir');
        $item01 = new CarouselColumnTemplateBuilder("Doa & Djikir", 'Artikel terkait Doa & Djikir', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490667391/dzikir_dan_doa_vkvvy3.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news naik+haji+umroh');
        $item01 = new CarouselColumnTemplateBuilder("Info Haji & Umroh", 'Artikel terkait Info Naik Haji & Umroh', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490666786/Info_haji_dan_umroh_uljpv3.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news alquran+hadits');
        $item01 = new CarouselColumnTemplateBuilder("Al Qur'an & Hadits", 'Artikel terkait Al Quran & Hadits', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490666882/Al_Qur_an_dan_hadist_j4tii7.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news perbankan+syariah');
        $item01 = new CarouselColumnTemplateBuilder("Perbankan Syariah", 'Artikel terkait Perbankan Syariah', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490666780/Perbankan_syariah_na6hgv.png', $array_action_builders);
        array_push($column_templates, $item01);

        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
        $messageBuilder = new TemplateMessageBuilder("Artikel", $carouselbuilder);
        $this->bot->pushMessage($to, $messageBuilder);
    }

}
