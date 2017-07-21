<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
// SDK for build message
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
// SDK for build button and template action
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;

require(APPPATH . '/libraries/REST_Controller.php');

class Api extends REST_Controller {

    private $events;
    private $signature;
    private $bot;
    private $utilities;
    private $googleplace;
    private $muslimshalat;
    private $kataAI;
    private $news;

    function __construct() {
        parent::__construct();
        include APPPATH . 'api/GooglePlaceService.php';
        include APPPATH . 'api/MuslimShalatService.php';
        include APPPATH . 'api/KataAIService.php';
        include APPPATH . 'api/NewsApi.php';
        include APPPATH . 'api/Utilitas.php';
        $this->load->model('tebakkode_m');
        $httpClient = new CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
        $this->bot = new LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
        $this->utilities = new Utilitas();
        //API
        $this->googleplace = new GooglePlaceService();
        $this->muslimshalat = new MuslimShalatService();
        $this->kataAI = new KataAIService();
        $this->news = new NewsApi();
    }

    function bot_get() {

        if (!$this->get('pesan')) {
            $this->response(NULL, 400);
        }
        $pesan = urldecode($this->get('pesan'));
        $lat =  urldecode($this->get('lat'));
        $lon = urldecode($this->get('lon'));

        $this->response($this->textMessage($pesan, $lat, $lon));
    }

    //tested
    public function textMessage($pesan, $lat, $lon) {
        $pesan = strtolower($pesan);
        $temp = explode(" ", $pesan);

        //tested//pesan salam
        if ($pesan == 'halo' || $pesan == 'hallo' || $pesan == 'hi' || $pesan == 'hei' | $pesan == 'hey') {
            $pesans = array();
            $temp = array(
                'pesan' => $pesan . ' kak! cek jadwal sholat, cari masjid terdekat, cari rumah makan halal di kota seluruh indonesia! Untuk penunjuk silahkan kakak ketik "help"',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesans, $temp);
            return $pesans;
        }

        //tested//help / bantuan
        else if ($pesan === 'help' || $pesan === 'bantuan') {
            return $this->sendHelp();
        }
        //pesan instruksi
        else {
            //preprocessing pesan
            $pesan = $this->utilities->getCleanMessage($pesan);
            $lokasi = $this->kataAI->getLokasi($pesan);
            //tested //kondidi hanya lokasi saja, kirim
            if ($lokasi != 'KOSONG' && $this->utilities->cekContainShalat($pesan) == false && $this->utilities->cekContainRestoran($pesan) == false && $this->utilities->cekContainMasjid($pesan) == false) {
                $pesan = array();
                $temp = array(
                    'pesan' => 'Maaf kak, HikennoaceBot gak ngerti instruksi kakak, kakak cuman nyebutin lokasi doang :v , coba lebih spesifik lagi, atau ketik help untuk bantuan!!',
                    'alamat' => '',
                    'lat' => '',
                    'long' => '',
                    'waktu' => ''
                );
                array_push($pesan, $temp);
                return $pesan;
            }
            //tested//kondisi dimana lokasi di state dan keyword masjid ditemukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainMasjid($pesan) == true) {
                $pesan = str_replace("masjid", "aaaaa", $pesan);
                $pesan = str_replace("mesjid", "aaaaa", $pesan);
                $pesan = str_replace("masjit", "aaaaa", $pesan);
                $lokasi = $this->kataAI->getLokasi($pesan);
                return $this->sendDaftarMasjidRekomendasi($lokasi);
            }
            //tested//kondisi dimana lokasi di state dan keyword restoran di temukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainRestoran($pesan) == true) {
                $pesan = str_replace("makan", "aaaaa", $pesan);
                $pesan = str_replace("kedai", "aaaaa", $pesan);
                $pesan = str_replace("tempat", "aaaaa", $pesan);
                $pesan = str_replace("rumah", "aaaaa", $pesan);
                $lokasi = $this->kataAI->getLokasi($pesan);
                return $this->sendDaftarRestoranRekomendasi($lokasi);
            }
            //tested//kondisi dimana lokasi di-state dan keyword jadwal / sholat ditemukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainShalat($pesan) == true) {
                return $this->sendJadwalSholat($lokasi);
            }
            //kondisi lainnya
            else {
                if ($this->utilities->cekContainShalat($pesan) == true) {
                    $pesan = array();
                    $temp = array(
                        'pesan' => 'Untuk cek jadwal sholat silahkan kakak spesifikkan nama kotaya!',
                        'alamat' => '',
                        'lat' => '',
                        'long' => '',
                        'waktu' => ''
                    );
                    array_push($pesan, $temp);
                    return $pesan;
                }
                //hanya keyword mesjid tanpa lokasi
                else if ($this->utilities->cekContainMasjid($pesan) == true) {
                    return $this->sendDaftarMasjidTerdekat($lat, $lon);
                }
                //hanya keyword restoran tanpa lokasi
                else if ($this->utilities->cekContainRestoran($pesan) == true) {
                    return $this->sendDaftarRestoranTerdekat($lat, $lon);
                }
                //pesan tidak dimengerti
                else {
                    $pesan = array();
                    $temp = array(
                        'pesan' => 'Maaf kak, HikennoaceBot gak ngerti instruksi kakak, coba lebih spesifik lagi, atau ketik help untuk bantuan!!',
                        'alamat' => '',
                        'lat' => '',
                        'long' => '',
                        'waktu' => ''
                    );
                    array_push($pesan, $temp);
                    return $pesan;
                }
            }
        }
    }

    public function sendDaftarMasjidRekomendasi($lokasi) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getMasjidLocationNearest($latlong['lat'], $latlong['long']);
        if (count($lokasis) != 0) {
            $pesan = array();
            $temp = array(
                'pesan' => "Berikut Masjid di " . $lokasi . ':',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            foreach ($lokasis as $key => $value) {
                $temp = array(
                    'pesan' => $value['nama'],
                    'alamat' => $value['alamat'],
                    'lat' => $value['lat'],
                    'long' => $value['long'],
                    'waktu' => ''
                );
                array_push($pesan, $temp);
            }
            return $pesan;
        } else {
            $pesan = array();
            $temp = array(
                'pesan' => 'Duh!! Maaf ya kak, HikennoaceBot gak nemu rumah makannya nih, coba kakak masukin lebih spesifik lagi lokasinya!',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            return $pesan;
        }
    }

    public function sendDaftarRestoranRekomendasi($lokasi) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getRestoranLocationPopuler($latlong['lat'], $latlong['long']);
        if (count($lokasis) != 0) {
            $pesan = array();
            $temp = array(
                'pesan' => "Berikut Restoran di " . $lokasi . ':',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            foreach ($lokasis as $key => $value) {
                $temp = array(
                    'pesan' => $value['nama'],
                    'alamat' => $value['alamat'],
                    'lat' => $value['lat'],
                    'long' => $value['long'],
                    'waktu' => ''
                );
                array_push($pesan, $temp);
            }
            return $pesan;
        } else {
            $pesan = array();
            $temp = array(
                'pesan' => 'Duh!! Maaf ya kak, MBot gak nemu masjidnya nih, coba kakak masukin lebih spesifik lagi lokasinya!',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            return $pesan;
        }
    }

    public function sendJadwalSholat($lokasi) {
        $jadwals = $this->muslimshalat->getWaktuSholat($lokasi);
        if (count($jadwals) === 0) {
            $message = 'Maaf kak, Hiken gak nemu yang kakak cari :( silahkan ketik  help untuk bantuan';
            $textMessageBuilder = new TextMessageBuilder($message);
            return $textMessageBuilder->buildMessage();
        } else {
            $pesan = array();
            $temp = array(
                'pesan' => "Berikut Daftar Jadwal Sholat di " . $lokasi . ':',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            foreach ($jadwals as $key => $value) {
                if ($key === 'date_for') {
                    continue;
                }
                $temp = $key;
                if ($temp == 'fajr') {
                    $temp = 'Subuh';
                }
                if ($temp == 'dhuhr') {
                    $temp = 'Dzuhur';
                }
                if ($temp == 'asr') {
                    $temp = 'Ashar';
                }
                if ($temp == 'maghrib') {
                    $temp = 'Maghrib';
                }
                if ($temp == 'isha') {
                    $temp = 'Isha';
                }
                $temp = array(
                    'pesan' => $temp,
                    'alamat' => '',
                    'lat' => '',
                    'long' => '',
                    'waktu' => date("H:i", strtotime($value))
                );
                array_push($pesan, $temp);
            }
            return $pesan;
        }
    }

    public function sendHelp() {
        $pesan = array();
        $temp = array(
            'pesan' => 'Untuk mecari masjid dikota tertentu, kakak cukup ketikkan instruksi/pertanyaan seperti: "masjid di medan", "masjid", "masjid di medan dimana aja sih?", "carikan daftar masjid di medan dong", "masjid dekat sini", dsb.',
            'alamat' => '',
            'lat' => '',
            'long' => '',
            'waktu' => ''
        );
        array_push($pesan, $temp);
        $temp = array(
            'pesan' => 'Untuk mecari masjid dikota tertentu, kakak cukup ketikkan instruksi/pertanyaan seperti: "masjid di medan", "masjid", "masjid di medan dimana aja sih?", "carikan daftar masjid di medan dong", "masjid dekat sini", dsb.',
            'alamat' => '',
            'lat' => '',
            'long' => '',
            'waktu' => ''
        );
        array_push($pesan, $temp);
        $temp = array(
            'pesan' => 'Untuk mengetahui jadwal sholat hari ini, cukup ketikkan ketikkan instruksi/pertanyaan seperti "jadwal sholat di medan", "jadwal sholat", "sholat" , "sholat isa di medan jam berapa?", dsb.',
            'alamat' => '',
            'lat' => '',
            'long' => '',
            'waktu' => ''
        );
        array_push($pesan, $temp);
        return $pesan;
    }

    public function sendDaftarMasjidTerdekat($latitude, $longitude) {
        $lokasis = $this->googleplace->getMasjidLocationNearest($latitude, $longitude);
        if (count($lokasis) != 0) {
            $pesan = array();
            $temp = array(
                'pesan' => 'Berikut daftar Masjid terdekat dari lokasi kakak saat ini :',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            foreach ($lokasis as $key => $value) {
                $temp = array(
                    'pesan' => $value['nama'],
                    'alamat' => $value['alamat'],
                    'lat' => $value['lat'],
                    'long' => $value['long'],
                    'waktu' => ''
                );
                array_push($pesan, $temp);
            }
            return $pesan;
        } else {
            $pesan = array();
            $temp = array(
                'pesan' => 'Duh!! Maaf ya kak, HikennoaceBot gak nemu rumah makann dengan radius kurang dari 10km dari posisi kakak!',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            return $pesan;
        }
    }
    
    public function sendDaftarRestoranTerdekat($latitude, $longitude) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getRestoranLocationPopuler($latlong['lat'], $latlong['long']);
        if (count($lokasis) != 0) {
            $pesan = array();
            $temp = array(
                'pesan' => 'Berikut daftar Restoran terdekat dari lokasi kakak :',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            foreach ($lokasis as $key => $value) {
                $temp = array(
                    'pesan' => $value['nama'],
                    'alamat' => $value['alamat'],
                    'lat' => $value['lat'],
                    'long' => $value['long'],
                    'waktu' => ''
                );
                array_push($pesan, $temp);
            }
            return $pesan;
        } else {
            $pesan = array();
            $temp = array(
                'pesan' => 'Duh!! Maaf ya kak, MBot gak nemu masjid dengan radius kurang dari 10km dari posisi kakak!',
                'alamat' => '',
                'lat' => '',
                'long' => '',
                'waktu' => ''
            );
            array_push($pesan, $temp);
            return $pesan;
        }
    }

}
