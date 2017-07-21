<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

class RemainderSholat extends CI_Controller {

    private $serviceSholat;
    private $bot;

    function __construct() {
        parent::__construct();
        include APPPATH . 'api/MuslimShalatService.php';
        $this->load->model('tebakkode_m');
        $httpClient = new CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
        $this->bot = new LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
        $this->serviceSholat = new MuslimShalatService();
    }

    public function index() {
//        $listWaktuSholat = $this->serviceSholat->getWaktuSholatAndTimeZone('m);
//        //    foreach each user ()
        echo 'sholat remainderrrr';

        $listUser = $this->tebakkode_m->getAllUser();
        foreach ($listUser as $user) {
            $listWaktuSholat = $this->serviceSholat->getWaktuSholatAndTimeZone($user->lokasi);
            $timezone = $listWaktuSholat['timezone'];

            $utc_time = gmdate("H:i:s", time());
            $timezone = strtotime($utc_time) + ($timezone * 3600); // dikali jumlah menit dalam 1 jam
            $timeUserNow = date("H:i", $timezone); // convert dari strtotime ke jam nya
            foreach ($listWaktuSholat as $key => $value) {
                if ($key != 'timezone') {
                    $jamDaerah = date("H:i", strtotime($timeUserNow) + (15 * 60)); //selisih waktu 5 menit
                    $jamSholat = date("H:i", strtotime($value));
                    $temp = $key;
                    $uri = '';
                    if ($temp == 'fajr') {
                        $temp = 'Subuh';
                        $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683715/Reminder_Subuh_wmltl2.png';
                    }
                    if ($temp == 'dhuhr') {
                        $temp = 'Dzuhur';
                        $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683719/Reminder_Zuhur_t3a3gw.png';
                    }
                    if ($temp == 'asr') {
                        $temp = 'Ashar';
                        $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683706/reminder_Ashar_r3zqew.png';
                    }
                    if ($temp == 'maghrib') {
                        $temp = 'Maghrib';
                        $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683711/reminder_maghrib_lr7lea.png';
                    }
                    if ($temp == 'isha') {
                        $temp = 'Isha';
                        $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683708/Reminder_Isya_obuabu.png';
                    }
                    if ($jamDaerah == $jamSholat) {
                        $column_templates = array();
                        $array_action_builders = array();
                        $array_action_builders[] = new PostbackTemplateActionBuilder('Masjid Terdekat', '::mesjid ' . $user->lokasi);
                        $item01 = new CarouselColumnTemplateBuilder("Sholat 15 Menit Lagi", 'Waktu Sholat ' . $temp . ' pukul ' . $jamSholat . ', Jangan Lupa Sholat Ya!!', $uri, $array_action_builders);
                        array_push($column_templates, $item01);

                        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
                        $messageBuilder = new TemplateMessageBuilder("Sholat Remainder", $carouselbuilder);
                        $this->bot->pushMessage($user->user_id, $messageBuilder);
                    }
                }
            }
        }
    }

    //put your code here
}
