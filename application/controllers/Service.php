<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
// SDK for build message
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
// SDK for build button and template action
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;

class Webhook extends CI_Controller {

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

    public function index() {
        //filter hanya get request yang diperbolehkan
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo 'TERBARU GANNNNNNNNNNNNNN------';
//            $event = array(
//                'source' => array(
//                    'userId' => 'U6906019e85e33597b8d9a941056a3273'
//                ),
//                'message' => array(
//                    'text' => 'sholat di medan',
//                    'type' => '::news'
//                )
//            );
//            //$this->sendJadwalSholat('medan', $event);
//            $this->textMessage($event);
//            die();

            header('HTTP/1.1 400 Only POST method allowed');
            exit;
        }
        // get request
        $body = file_get_contents('php://input');
        $this->signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : "-";
        $this->events = json_decode($body, true);

        // loop semua event
        $this->tebakkode_m->log_events($this->signature, $body);
        foreach ($this->events['events'] as $event) {
            // skip group and room event
            if (!isset($event['source']['userId']))
                continue;
            // respond event     
            if ($event['type'] == 'postback') {
                $data = $event['postback']['data'];
                $pesan = explode(" ", $data);
                //cari masjid  //tested
                if ($pesan[0] == 'mesjid') {
                    //mesjid 2.386042 99.1473 //tested
                    if (count($pesan) === 3) {
                        $this->sendDaftarMasjidTerdekat($pesan[1], $pesan[2], $event);
                    }
                    //mesjid jakarta  //tested
                    else {
                        $this->sendDaftarMasjidRekomendasi($pesan[1], $event);
                    }
                }
                //rumah makan halal tested
                else if ($pesan[0] == '::restoran') {
                    //restoran 2.386042 99.1473 //tested
                    if (count($pesan) === 3) {
                        $this->sendDaftarRestoranTerdekat($pesan[1], $pesan[2], $event);
                    }
                    //restoran jakarta //tested
                    else {
                        $this->sendDaftarRestoranRekomendasi($pesan[1], $event);
                    }
                }
                //jadwal sholat partially tested
                else if ($pesan[0] == '::sholat') {
                    //::sholat 2.386042 99.1473
                    if (count($pesan) === 3) {
                        echo '<br><br>jadwal sholat latitude longitude<br><br>';
                        //cari dulu nama lokasi berdasarkan latitude dan longitudenya
                        //$this->sendDaftarRestoranTerdekat(, $event);
                    }
                    //::sholat jakarta tested
                    else {
                        $this->sendJadwalSholat($pesan[1], $event);
                    }
                }
                //news partially tested
                else if ($pesan[0] == '::news') {
                    //::news renungan+muslim
                    if (count($pesan) === 2) {
                        //kirim 5 berita terbaru berdasarkan topik
                        $berita = $this->news->getNewsCarousel($pesan[1]);
                        $this->bot->pushMessage($event['source']['userId'], $berita);
                        //echo '<br><br>berita  ' . $pesan[1] . ' <br><br>';
                    }
                    //::news topik more
                    else {
                        //kirim 5 berita terbaru lebih banyak setelah 5 diatas
                        $berita = $this->news->getNewsCarousel($pesan[1]);
                        $this->bot->pushMessage($event['source']['userId'], $berita);
                    }
                }
                //detail help
                else if ($pesan[0] == '::detail') {
                    $this->detailHelp($pesan[1], $event);
                }
            }
            //pesan
            else if ($event['type'] == 'message') {
                if (method_exists($this, $event['message']['type'] . 'Message')) {
                    $this->{$event['message']['type'] . 'Message'}($event);
                }
            }
            //follow & unfollow
            else {
                if (method_exists($this, $event['type'] . 'Callback')) {
                    $this->{$event['type'] . 'Callback'}($event);
                }
            }
        }
    }

    //handle pesan type follow //tested
    private function followCallback($event) {
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();
            // save user data
            $this->tebakkode_m->saveUser($profile);
        }
        // send welcome message
        $message = "Saya bisa bantu kak " . $profile['displayName'] . " untuk cek jadwal sholat, masjid terdekat, rumah makan halal terdekat di sekitar kakak, silahkan ketik help untuk memulai!";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
    }

    private function textMessage($event) {
        $pesan = strtolower($event['message']['text']);
        $temp = explode(" ", $pesan);
        //tested//pesan salam
        if ($pesan == 'halo' || $pesan == 'hallo' || $pesan == 'hi' || $pesan == 'hei' | $pesan == 'hey') {
            $res = $this->bot->getProfile($event['source']['userId']);
            if ($res->isSucceeded()) {
                $profile = $res->getJSONDecodedBody();
            }
            $message = $pesan . ' ' . $profile['displayName'] . "!! Untuk cek jadwal sholat, masjid terdekat, rumah makan halal terdekat di sekitar kakak, silahkan ketik help untuk memulai!";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
        //tested//news
        else if ($pesan == '::news') {
            $this->sendNewsCategory($event);
        }
        //pesan admin untuk broadcast
        else if ($temp[0] == '::admin') {
            $message = "Admin Broadcast!!";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
        //tested//pesan user untuk set kota default
        else if ($temp[0] == '::setdomisili') {
            $lokasi = $this->kataAI->getLokasi($temp[1]);
            if ($lokasi != "KOSONG") {
                $message = "Update Kota Berhasil!!";
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            }
            //tested//lokasi tidak valid
            else {
                //jangan update dan kirim pesan lokasi tidak ditemukan
                $message = "Update Kota Domisili Gagal!!";
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                $message = 'Silahkan kirim pesan dengan format "::setdomisili(spasi)kota_domisili_anda"  !!';
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            }
        }
        //tested//help / bantuan
        else if ($pesan === 'help') {
            $this->sendHelp($event);
        }
        //pesan instruksi
        else {
            //preprocessing pesan
            $pesan = $this->utilities->getCleanMessage($pesan);
            $lokasi = $this->kataAI->getLokasi($pesan);
            //tested //kondidi hanya lokasi saja, kirim
            if ($lokasi != 'KOSONG' && $this->utilities->cekContainShalat($pesan) == false && $this->utilities->cekContainRestoran($pesan) == false && $this->utilities->cekContainMasjid($pesan) == false) {
                $this->sendMenu($lokasi, $event);
            }
            //tested//kondisi dimana lokasi di state dan keyword masjid ditemukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainMasjid($pesan) == true) {
                $this->sendDaftarMasjidRekomendasi($lokasi, $event);
            }
            //tested//kondisi dimana lokasi di state dan keyword restoran di temukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainRestoran($pesan) == true) {
                $pesan = str_replace("makan", "aaaaa", $pesan);
                $pesan = str_replace("kedai", "aaaaa", $pesan);
                $pesan = str_replace("tempat", "aaaaa", $pesan);
                $pesan = str_replace("rumah", "aaaaa", $pesan);
                $lokasi = $this->kataAI->getLokasi($pesan);
                $this->sendDaftarRestoranRekomendasi($lokasi, $event);
            }
            //tested//kondisi dimana lokasi di-state dan keyword jadwal / sholat ditemukan
            else if ($lokasi != 'KOSONG' && $this->utilities->cekContainShalat($pesan) == true) {
                $this->sendJadwalSholat($lokasi, $event);
            }
            //kondisi lainnya
            else {
                $sender = $this->tebakkode_m->getUser($event['source']['userId']);
                $latlong = $this->muslimshalat->getLatituteLongitute($sender['lokasi']);
                if ($this->utilities->cekContainShalat($pesan) == true) {
                    //ambil data dari database sebagai default
                    $this->sendJadwalSholat($sender['lokasi'], $event);
                }
                //hanya keyword mesjid tanpa lokasi
                else if ($this->utilities->cekContainMasjid($pesan) == true) {
                    //ambil data dari database sebagai default
                    $this->sendDaftarMasjidTerdekat($latlong['lat'], $latlong['long'], $event);
                }
                //hanya keyword restoran tanpa lokasi
                else if ($this->utilities->cekContainRestoran($pesan) == true) {
                    //ambil data dari database sebagai default
                    $this->sendDaftarRestoranTerdekat($latlong['lat'], $latlong['long'], $event);
                }
                //pesan tidak dimengerti
                else {
                    $message = "Maaf kak, Hiken gak ngerti instruksi kakak, coba lebih spesifik lagi, atau ketik help untuk bantuan!!";
                    $textMessageBuilder = new TextMessageBuilder($message);
                    $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                }
            }
        }
    }

    //untuk handle message type sticker
    private function stickerMessage($event) {
        $stickerMessageBuilder = new StickerMessageBuilder(1, 10);
        $this->bot->pushMessage($event['source']['userId'], $stickerMessageBuilder);
    }

    //untuk handle message tipe lokasi //tested
    private function locationMessage($event) {
        $this->sendMenu('', $event);
    }

    //untuk handle message tipe image //tested
    private function imageMessage($event) {
        $message = "Ini rekomendasi walpaper dari Hiken ya kak..";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

        $image = new ImageMessageBuilder('https://static.getjar.com/ss/eb/470679.png', 'https://static.getjar.com/ss/eb/470679.png');
        $this->bot->pushMessage($event['source']['userId'], $image);

        $image = new ImageMessageBuilder('https://ibin.co/3FzjyYbQqFGN.jpg', 'https://ibin.co/3FzjyYbQqFGN.jpg');
        $this->bot->pushMessage($event['source']['userId'], $image);

        $image = new ImageMessageBuilder('https://ibin.co/3FzkJACOn3Ip.jpg', 'https://ibin.co/3FzkJACOn3Ip.jpg');
        $this->bot->pushMessage($event['source']['userId'], $image);

        $image = new ImageMessageBuilder('https://ibin.co/3Fzkt4JVY2nj.jpg', 'https://ibin.co/3Fzkt4JVY2nj.jpg');
        $this->bot->pushMessage($event['source']['userId'], $image);

        $image = new ImageMessageBuilder('https://ibin.co/3FzlDfuTUpuN.jpg', 'https://ibin.co/3FzlDfuTUpuN.jpg');
        $this->bot->pushMessage($event['source']['userId'], $image);
    }

    //untuk handle message tipe audio
    private function audioMessage($event) {
        $message = "Ini rekomendasi Audio dari Hiken buat kakak jadiin Ringtone.";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        $message = new AudioMessageBuilder('https://instaud.io/_/P77.mp3', 29000);
        $this->bot->pushMessage($event['source']['userId'], $message);

        $message = new AudioMessageBuilder('https://instaud.io/_/P7a.mp3', 29000);
        $this->bot->pushMessage($event['source']['userId'], $message);

        $message = new AudioMessageBuilder('https://instaud.io/_/P7e.mp3', 28000);
        $this->bot->pushMessage($event['source']['userId'], $message);

        $message = new AudioMessageBuilder('https://instaud.io/_/P7f.mp3', 28000);
        $this->bot->pushMessage($event['source']['userId'], $message);

        $message = new AudioMessageBuilder('https://instaud.io/_/P7g.mp3', 19000);
        $this->bot->pushMessage($event['source']['userId'], $message);
    }

    //untuk handle message tipe video
    private function videoMessage($event) {
        $message = "Kakak ngirim VIDEO ya?, maaf ya kak, hiken belum ngarti nih.) ";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        $this->sendInstruksi($event);
    }

    //tested
    public function sendHelp($event) {
        $column_templates = array();
        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail masjidfinder');
        $item01 = new CarouselColumnTemplateBuilder("Masjid Finder", 'untuk mencari lokasi masjid terdekat', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail restoranfinder');
        $item01 = new CarouselColumnTemplateBuilder("Restoran Halal Finder", 'untuk mencari lokasi restoran Halal terdekat', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail jadwalsholat');
        $item01 = new CarouselColumnTemplateBuilder("Jadwal Sholat", 'untuk mencari jadwal sholat di lokasi tertentu', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Coba Post', '::detail news');
        $item01 = new CarouselColumnTemplateBuilder("Muslim News", 'berita seputar muslim', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
        $messageBuilder = new TemplateMessageBuilder("Panduan", $carouselbuilder);
        $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
    }

    public function detailHelp($key, $event) {
        if ($key == 'masjidfinder') {
            $message = "Untuk mecari masjid dikota tertentu, kakak cukup ketikkan lokasi dan masjidnya contoh: masjid di medan.Sedangkan untuk pencarian masjid terdekat, cukup denganmengirimkan pesan lokasi dengan fitur share lokasi pada line! ";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else if ($key == 'restoranfinder') {
            $message = "Untuk mecari restoran halal dikota tertentu, kakak cukup ketikkan lokasi dan masjidnya contoh: masjid di medan.Sedangkan untuk pencarian masjid terdekat, cukup denganmengirimkan pesan lokasi dengan fitur share lokasi pada line! ";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

            $message = "Sebagai info, hiken tidak bisa menjamin 100% resto tersebut Halal, karena hiken cuma menilai review NETIZEN!";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else if ($key == 'jadwalsholat') {
            $message = 'Untuk mendapatkan jadwal sholat, cukup ketikkan sholat/jadwal dan lokasinya contoh(jadwal sholat di medan jam berapa?) atau dengan mengirimkan pesan lokasi anda melalui fitur share location!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            
            $message = 'Sebagai info tambahan : Reminder sholat % waktu,akan dikirimkan 15 menit sebelum sholat, dengan lokasi jakarta sebagai default domisilinya, untuk mengubah kota domisili kakak, kakak bisa entri "::setdomisili(spasi)kota_domisili"';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else if ($key == 'news') {
            $message = 'Untuk mendapatkan, Berita dan Artikel terbaru seputar muslim, kakak tinggal ketik "::news" lalu pilih kategori beritanya';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            
            $message = 'Sebagai info tambahan : Katalog Berita & Artikal muslim akan dikirim kepada kakak, setiap harinya, pada pukul 7:30 WIB. Jadi Gak bakalan ketinggalan berita deh!!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendNewsCategory($event) {
        $column_templates = array();
        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news majelis+ulama+indonesia');
        $item01 = new CarouselColumnTemplateBuilder("Majelis Ulama Indonesia", 'Berita dan Artikel terkait Majelis Ulama Indonesia', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news nahdlatul+ulama');
        $item01 = new CarouselColumnTemplateBuilder("Nahdlatul Ulama", 'Berita dan Artikel terkait Nahdlatul Ulama', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news naik+haji');
        $item01 = new CarouselColumnTemplateBuilder("Info Haji", 'Berita dan Artikel terkait Info Naik Haji', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news sunnah+hadits');
        $item01 = new CarouselColumnTemplateBuilder("Sunnah & Hadits", 'Berita dan Artikel terkait Sunnah & Hadits', 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
        array_push($column_templates, $item01);

        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
        $messageBuilder = new TemplateMessageBuilder("Panduan", $carouselbuilder);
        $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
    }

    //tested
    public function sendMenu($pesan, $event) {
        if ($event['message']['type'] === 'location') {
            $pesan = $event['message']['latitude'] . ' ' . $event['message']['longitude'];
        }

        $menus = '[{
	"type": "template",
	"altText": "Menu",
	"template": {
		"type": "carousel",
		"columns": [{
			"text": "Kakak ingin apa?",
			"actions": [{
				"type": "postback",
				"label": "Cari Masjid",
				"data": "::mesjid ' . $pesan . '"
			}, {
				"type": "postback",
				"label": "Cari Restoran",
				"data": "::sholat ' . $pesan . '"
			}, {
				"type": "postback",
				"label": "Cek Jadwal Sholat",
				"data": "::restoran ' . $pesan . '"
			}]
		}]
	}
}]';
        $this->bot->pushMessageJson($event['source']['userId'], json_decode($menus, true));
    }

    //tested
    public function sendJadwalSholat($lokasi, $event) {
        $jadwals = $this->muslimshalat->getWaktuSholat($lokasi);
        if (count($jadwals) === 0) {
            $message = 'maaf ya kak, hiken gak nemu yang kakak cari :( silahkan ketik  help untuk bantuan';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else {
            $column_templates = array();
            foreach ($jadwals as $key => $value) {
                if ($key === 'date_for') {
                    continue;
                }
                $array_action_builders = array();
                $uri = "https://www.facebook.com/";
                $array_action_builders[] = new UriTemplateActionBuilder("Lihat Tips Sholat", $uri);
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
                $item01 = new CarouselColumnTemplateBuilder('Jadwal Sholat ' . $temp . ' : ' . date("H:i", strtotime($value)), 'Tanggal : ' . date("d/m/Y"), 'https://res.cloudinary.com/prideprize/image/upload/v1489727281/banner_pcuknf.png', $array_action_builders);
                array_push($column_templates, $item01);
            }
            $carouselbuilder = new CarouselTemplateBuilder($column_templates);
            $messageBuilder = new TemplateMessageBuilder('Jadwal Sholat Hari ini di' . $lokasi, $carouselbuilder);
            $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
        }
    }

    //tested
    public function sendDaftarMasjidTerdekat($lat, $long, $event) {
        $lokasis = $this->googleplace->getMasjidLocationNearest($lat, $long);
        $message = 'Bentar hiken cariin Daftar Masjidnya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        print_r($lokasis);
        if (count($lokasis) != 0) {
            $message = 'Berikut Daftar Masjidnya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            foreach ($lokasis as $key => $value) {
                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
                $this->bot->pushMessage($event['source']['userId'], $locationMap);
            }
        } else {
            $message = 'Duh!! Maaf ya kak, hiken gak nemu masjidnya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendDaftarMasjidRekomendasi($lokasi, $event) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getMasjidLocationNearest($latlong['lat'], $latlong['long']);
        $message = 'Bentar hiken cariin Daftar Masjidnya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Berikut Daftar Masjidnya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            foreach ($lokasis as $key => $value) {
                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
                $this->bot->pushMessage($event['source']['userId'], $locationMap);
            }
        } else {
            $message = 'Duh!! Maaf ya kak, hiken gak nemu masjidnya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendDaftarRestoranTerdekat($lat, $long, $event) {
        $lokasis = $this->googleplace->getRestoranLocationNearest($lat, $long);
        $message = 'Bentar hiken cariin Daftar Rumah makannya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Ini Daftar Rumah makannya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            foreach ($lokasis as $key => $value) {
                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
                $this->bot->pushMessage($event['source']['userId'], $locationMap);
            }
        } else {
            $message = 'Duh!! Maaf ya kak, hiken gak nemu rumah makannya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested //tested
    public function sendDaftarRestoranRekomendasi($lokasi, $event) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getRestoranLocationPopuler($latlong['lat'], $latlong['long']);
        $message = 'Bentar hiken cariin Daftar Rumah makannya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Ini Daftar Rumah makannya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            foreach ($lokasis as $key => $value) {
                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
                $this->bot->pushMessage($event['source']['userId'], $locationMap);
            }
        } else {
            $message = 'Duh!! Maaf ya kak, hiken gak nemu rumah makannya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

}
