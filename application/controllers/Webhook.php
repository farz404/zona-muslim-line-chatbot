<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
// SDK for build message
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
// SDK for build button and template action
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;

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
            echo 'TERBARU GANNNNNNNNNNNNNN';
            $event = array(
                'source' => array(
                    'userId' => 'U6906019e85e33597b8d9a941056a3273'
                ),
                'message' => array(
                    'text' => 'sholat di medan',
                    'type' => '::news'
                )
            );
            $this->tebakkode_m->updateUser('U6906019e85e33597b8d9a941056a3273', 'medan');
            // $this->sendNewsCa
            // tegory($event);
            // $this->sendJadwalSholat('siantar', $event);
//            $this->sendDaftarRestoranRekomendasi('medan', $event);
//            $this->sendDaftarRestoranTerdekat(3.5772224, 98.6824763, $event);
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
//                $textMessageBuilder = new TextMessageBuilder($data);
//                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                $pesan = explode(" ", $data);
                //cari masjid  //tested
                if ($pesan[0] == '::mesjid') {
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
                        $user = $this->tebakkode_m->getUser($event['source']['userId']);
                        $this->sendJadwalSholat($user->lokasi, $event);
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
                        if ($this->news->countNews($pesan[1]) > 5) {
                            $this->sendKonfirmasi($pesan[1], $event);
                        }
                    }
                    //::news topik more
                    else {
                        //kirim 5 berita terbaru lebih banyak setelah 5 diatas
                        $berita = $this->news->getNewsCarouselMore($pesan[1]);
                        $this->bot->pushMessage($event['source']['userId'], $berita);
                    }
                }
                //detail help
                else if ($pesan[0] == '::detail') {
                    $this->detailHelp($pesan[1], $event);
                }
                //tidak
                else if ($pesan[0] == '::tidak') {
                    $message = 'Untuk mendapatkan, Berita dan Artikel terbaru seputar muslim, kakak tinggal ketik "::news" lalu pilih kategori beritanya';
                    $textMessageBuilder = new TextMessageBuilder($message);
                    $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

                    $message = 'Sebagai info , MBot akan mengirimkan Katalog Berita & Artikel muslim kepada kakak, setiap harinya, pada pukul 7:30 WIB. Jadi Gak bakalan ketinggalan berita deh!!';
                    $textMessageBuilder = new TextMessageBuilder($message);
                    $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                }
                //lainnya
                else {
                    $pesan = explode("+", $data);
                    $locationMap = new LocationMessageBuilder($pesan[1], '(Sentuh Untuk Menampilkan Lokasi di Peta!)', $pesan[2], $pesan[3]);
                    $this->bot->pushMessage($event['source']['userId'], $locationMap);
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
        $message = "Hi kak" . $profile['displayName'] . " nama saya MBot! Salam Kenal!";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        $message = "Saya bisa bantu kakak untuk cek jadwal sholat, cari masjid terdekat, cari rumah makan halal, dan nyari berita / artikel muslim terupdate,Untuk memulai silahkan ketik help atau sapa saya dengan hallo/hi!";
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
            $message = $pesan . ' kak ' . $profile['displayName'] . '! cek jadwal sholat, cari masjid terdekat, cari rumah makan halal, dan nyari berita / artikel muslim terupdate, silahkan ketik "help"';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
        //tested//news
        else if ($this->utilities->cekContainBerita($pesan) == true) {
            $this->sendNewsCategory($event);
        }
        //tested//pesan user untuk set kota default
        else if ($temp[0] == '::setdomisili') {
            $lokasi = $this->kataAI->getLokasi($temp[1]);
            if ($lokasi != "KOSONG" && count($temp) == 2) {
                $message = "Update Kota Berhasil!!";
                $this->tebakkode_m->updateUser($event['source']['userId'], $temp[1]);
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            }
            //tested//lokasi tidak valid
            else {
                //jangan update dan kirim pesan lokasi tidak ditemukan
                $message = "Update Kota Domisili Gagal, lokasi yang kakak entri tidak valid!!";
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                $message = 'Silahkan kirim pesan dengan format "::setdomisili(spasi)kota_domisili_kakak"  !!';
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
                $pesan = str_replace("masjid", "aaaaa", $pesan);
                $pesan = str_replace("mesjid", "aaaaa", $pesan);
                $pesan = str_replace("masjit", "aaaaa", $pesan);
                $lokasi = $this->kataAI->getLokasi($pesan);
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
                    $message = 'Kakak gak spesifikin lokasinya! Berikut daftar jadwal sholat di kota domisili kakak di ' . $sender['lokasi'];
                    $textMessageBuilder = new TextMessageBuilder($message);
                    $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
                    //ambil data dari database sebagai default
                    $this->sendJadwalSholat($sender['lokasi'], $event);
                }
                //hanya keyword mesjid tanpa lokasi
                else if ($this->utilities->cekContainMasjid($pesan) == true) {
                    $message = 'Kakak gak spesifikin lokasinya! Berikut rekomendasi masjid dari MBot, di kota domisili kakak di ' . $sender['lokasi'];
                    $textMessageBuilder = new TextMessageBuilder($message);
                    //ambil data dari database sebagai default
                    $this->sendDaftarMasjidTerdekat($latlong['lat'], $latlong['long'], $event);
                }
                //hanya keyword restoran tanpa lokasi
                else if ($this->utilities->cekContainRestoran($pesan) == true) {
                    $message = 'Kakak gak spesifikin lokasinya! Berikut daftar rekomendasi Rumah makan Halal dari MBot di kota domisili kakak di ' . $sender['lokasi'];
                    $textMessageBuilder = new TextMessageBuilder($message);
                    //ambil data dari database sebagai default
                    $this->sendDaftarRestoranTerdekat($latlong['lat'], $latlong['long'], $event);
                }
                //pesan tidak dimengerti
                else {
                    $message = "Maaf kak, MBot gak ngerti instruksi kakak, coba lebih spesifik lagi, atau ketik help untuk bantuan!!";
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
        $this->sendMenuDua('', $event);
    }

    //untuk handle message tipe image //tested
    private function imageMessage($event) {
        $message = "Maaf kak, Hiken gak ngerti maksud kakak.Ketik help untuk bantuan";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
    }

    //untuk handle message tipe audio
    private function audioMessage($event) {
        $message = "Maaf kak, MBot gak ngerti maksud kakak.Ketik help untuk bantuan";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
//        $message = new AudioMessageBuilder('https://instaud.io/_/P77.mp3', 29000);
//        $this->bot->pushMessage($event['source']['userId'], $message);
//
//        $message = new AudioMessageBuilder('https://instaud.io/_/P7a.mp3', 29000);
//        $this->bot->pushMessage($event['source']['userId'], $message);
//
//        $message = new AudioMessageBuilder('https://instaud.io/_/P7e.mp3', 28000);
//        $this->bot->pushMessage($event['source']['userId'], $message);
//
//        $message = new AudioMessageBuilder('https://instaud.io/_/P7f.mp3', 28000);
//        $this->bot->pushMessage($event['source']['userId'], $message);
//
//        $message = new AudioMessageBuilder('https://instaud.io/_/P7g.mp3', 19000);
//        $this->bot->pushMessage($event['source']['userId'], $message);
    }

    //untuk handle message tipe video
    private function videoMessage($event) {
        $message = "Maaf kak, Mbot gak ngerti maksud kakak.Ketik help untuk bantuan";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
    }

    //tested
    public function sendHelp($event) {
        $message = "Berikut hal yang bisa MBot lakuin buat kakak, silahkan ikuti petunjuknya!!";
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        $column_templates = array();
        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail masjidfinder');
        $item01 = new CarouselColumnTemplateBuilder("Masjid Finder", 'Untuk mencari lokasi masjid terdekat, dan kota lainnya.', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490794530/Masjid_Finder_jhwdzd.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail restoranfinder');
        $item01 = new CarouselColumnTemplateBuilder("Restoran Halal Finder", 'Untuk mencari lokasi restoran Halal terdekat dan kota lainya', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490794530/Restoran_Halal_Finder_munnhk.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490683711/reminder_maghrib_lr7lea.png');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail jadwalsholat');
        $item01 = new CarouselColumnTemplateBuilder("Jadwal Sholat", 'Cek jadwal sholat di kota domisili anda,dan kota lainnya.', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490794530/Jadwal_sholat_lvtmba.png', $array_action_builders);
        array_push($column_templates, $item01);

        $array_action_builders = array();
        $array_action_builders[] = new UriTemplateActionBuilder("Tampilkan Demo", 'https://giphy.com/gifs/3o7btObDMcBpPsp4aI/html5');
        $array_action_builders[] = new PostbackTemplateActionBuilder('Petunjuk', '::detail news');
        $item01 = new CarouselColumnTemplateBuilder("Artikel & Berita", 'Untuk membaca artikel & berita seputar muslim.', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490794530/Muslim_News_mxccao.png', $array_action_builders);
        array_push($column_templates, $item01);

        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
        $messageBuilder = new TemplateMessageBuilder("Panduan", $carouselbuilder);
        $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
    }

    public function detailHelp($key, $event) {
        if ($key == 'masjidfinder') {
            $message = 'Untuk mecari masjid dikota tertentu, kakak cukup ketikkan instruksi/pertanyaan seperti: "masjid di medan", "masjid", "masjid di medan dimana aja sih?", "carikan daftar masjid di medan dong", "masjid dekat sini", dsb.';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            $message = 'Sedangkan untuk pencarian masjid terdekat atau pencarian dengan lokasi lebih akurat, kakak cukup kirim lokasi kakak dengan fitur share lokasi pada line! ';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else if ($key == 'restoranfinder') {
            $message = 'Untuk mecari restoran halal dikota tertentu, kakak cukup ketikkan instruksi/pertanyaan seperti "tempat makan di medan", "restoran/makan/rumah makan", "restoran halal di medan dimana aja sih?", "carikan daftar tempat makan di medan dong", "restoran halal dekat sini", dsb.';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            $message = 'Sedangkan untuk pencarian restoran halal terdekat atau pencarian dengan lokasi lebih akurat, kakak cukup kirim lokasi kakak dengan fitur share lokasi pada line! ';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            $message = "Sebagai info, MBot tidak bisa menjamin 100% restoran tersebut Halal, karena MBot cuma memberikan rekomendasi berdasarkan review NETIZEN di Google, bukan dari data resmi MUI!";
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else if ($key == 'jadwalsholat') {
            $message = 'Untuk mengetahui jadwal sholat hari ini, cukup ketikkan ketikkan instruksi/pertanyaan seperti "jadwal sholat di medan", "jadwal sholat", "sholat" , "sholat isa di medan jam berapa?", dsb.!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

            $message = 'Sedangkan untuk pengecekan jadwal sholat dengan lokasi lebih presisi, kakak cukup kirim lokasi kakak dengan fitur share lokasi pada line! ';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

            $message = 'Sebagai info tambahan MBot akan kirimin kakak Reminder sholat 5 waktu 15 menit sebelum waktu sholat di kota domisili kakak.';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

            $message = 'secara default lokasi domisili kakak di set di Jakarta, kakak bisa mengubahnya dengan mengentri instruksi "::setdomisili(spasi)kota_domisili"';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            
        } else if ($key == 'news') {
            $message = 'Untuk mendapatkan, Berita dan Artikel terbaru seputar muslim, kakak tinggal ketik instruksi seperti "berita hari ini", "artikel", "berita terbarunya tampilin dong", "artikel terbaru", "berita", dsb';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);

            $message = 'Sebagai info , MBot akan mengirimkan Katalog Berita & Artikel muslim kepada kakak, setiap harinya, pada pukul 7:30 WIB. Jadi Gak bakalan ketinggalan berita deh!!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendNewsCategory($event) {
        $column_templates = array();

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

        $array_action_builders = array();
        $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Berita', '::news berita+muslim+indonesia');
        $item01 = new CarouselColumnTemplateBuilder("Berita Lainnya", 'Artikel dan berita umum lainnya.', 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490794530/Muslim_News_mxccao.png', $array_action_builders);
        array_push($column_templates, $item01);

        $carouselbuilder = new CarouselTemplateBuilder($column_templates);
        $messageBuilder = new TemplateMessageBuilder("Artikel", $carouselbuilder);
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
			"text": "Kakak ingin MBot bantuin apa?",
			"actions": [{
				"type": "postback",
				"label": "Cari Masjid",
				"data": "::mesjid ' . $pesan . '"
			}, {
				"type": "postback",
				"label": "Cari Restoran Halal",
				"data": "::restoran ' . $pesan . '"
			}, {
				"type": "postback",
				"label": "Cek Jadwal Sholat",
				"data": "::sholat ' . $pesan . '"
			}]
		}]
	}
}]';
        $this->bot->pushMessageJson($event['source']['userId'], json_decode($menus, true));
    }

    public function sendMenuDua($pesan, $event) {
        if ($event['message']['type'] === 'location') {
            $pesan = $event['message']['latitude'] . ' ' . $event['message']['longitude'];
        }

        $menus = '[{
	"type": "template",
	"altText": "Menu",
	"template": {
		"type": "carousel",
		"columns": [{
			"text": "Kakak ingin MBot bantuin apa?",
			"actions": [{
				"type": "postback",
				"label": "Cari Masjid",
				"data": "::mesjid ' . $pesan . '"
			}, {
				"type": "postback",
				"label": "Cari Restoran Halal",
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
            $message = 'Maaf kak, Hiken gak nemu yang kakak cari :( silahkan ketik  help untuk bantuan';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        } else {
            $message = 'Berikut daftar jadwal sholatnya :';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            $column_templates = array();
            foreach ($jadwals as $key => $value) {
                if ($key === 'date_for') {
                    continue;
                }
                $array_action_builders = array();
                $array_action_builders[] = new PostbackTemplateActionBuilder('Daftar Masjid', '::mesjid ' . $lokasi);
                $temp = $key;
                $uri = '';
                if ($temp == 'fajr') {
                    $temp = 'Subuh';
                    $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665225/Subuh_muokft.png';
                }
                if ($temp == 'dhuhr') {
                    $temp = 'Dzuhur';
                    $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665229/Zuhur_nqhes5.png';
                }
                if ($temp == 'asr') {
                    $temp = 'Ashar';
                    $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665208/Ashar_dg9m29.png';
                }
                if ($temp == 'maghrib') {
                    $temp = 'Maghrib';
                    $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665218/maghrib_dqnxpv.png';
                }
                if ($temp == 'isha') {
                    $temp = 'Isha';
                    $uri = 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665213/Isya_kpxi8y.png';
                }
                $item01 = new CarouselColumnTemplateBuilder('Jadwal Sholat ' . $temp . ' : ' . date("H:i", strtotime($value)), 'Tanggal : ' . date("d/m/Y"), $uri, $array_action_builders);
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
        $message = 'Bentar MBot cariin Daftar Masjidnya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Berikut Daftar Masjidnya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
//            foreach ($lokasis as $key => $value) {
//                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
//                $this->bot->pushMessage($event['source']['userId'], $locationMap);
//            }
            $column_templates = array();
            foreach ($lokasis as $key => $value) {
                $array_action_builders = array();
                $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Lokasi', '::lokasi+' . $value['nama'] . '+' . $value['lat'] . '+' . $value['long'] . '+' . $value['nama']);
                $alamat = (strlen($value['alamat']) > 55) ? substr($value['alamat'], 0, 55) . '...' : $value['alamat'];
                $nama = (strlen($value['nama']) > 36) ? substr($value['nama'], 0, 36) . '...' : $value['nama'];
                $item01 = new CarouselColumnTemplateBuilder($nama, $alamat, 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665100/location_lgdia6.png', $array_action_builders);
                array_push($column_templates, $item01);
            }
            $carouselbuilder = new CarouselTemplateBuilder($column_templates);
            $messageBuilder = new TemplateMessageBuilder("Daftar Masjid", $carouselbuilder);
            $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
        } else {
            $message = 'Duh!! Maaf ya kak, MBot gak nemu masjidnya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendDaftarMasjidRekomendasi($lokasi, $event) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getMasjidLocationNearest($latlong['lat'], $latlong['long']);
        $message = 'Bentar MBot cariin Daftar Masjidnya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Berikut Daftar Masjidnya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
            $column_templates = array();
            foreach ($lokasis as $key => $value) {
                $array_action_builders = array();
                $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Lokasi', '::lokasi+' . $value['nama'] . '+' . $value['lat'] . '+' . $value['long'] . '+' . $value['nama']);
                $alamat = (strlen($value['alamat']) > 55) ? substr($value['alamat'], 0, 55) . '...' : $value['alamat'];
                $nama = (strlen($value['nama']) > 36) ? substr($value['nama'], 0, 36) . '...' : $value['nama'];
                $item01 = new CarouselColumnTemplateBuilder($nama, $alamat, 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665100/location_lgdia6.png', $array_action_builders);
                array_push($column_templates, $item01);
            }
            $carouselbuilder = new CarouselTemplateBuilder($column_templates);
            $messageBuilder = new TemplateMessageBuilder("Daftar Masjid", $carouselbuilder);
            $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
        } else {
            $message = 'Duh!! Maaf ya kak, MBot gak nemu masjidnya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendDaftarRestoranTerdekat($lat, $long, $event) {
        $lokasis = $this->googleplace->getRestoranLocationNearest($lat, $long);
        $message = 'Bentar MBot cariin Daftar Rumah makannya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Ini Daftar Rumah makannya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
//            foreach ($lokasis as $key => $value) {
//                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
//                $this->bot->pushMessage($event['source']['userId'], $locationMap);
//            }
            $column_templates = array();
            foreach ($lokasis as $key => $value) {
                $array_action_builders = array();
                $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Lokasi', '::lokasi+' . $value['nama'] . '+' . $value['lat'] . '+' . $value['long'] . '+' . $value['nama']);
                $alamat = (strlen($value['alamat']) > 55) ? substr($value['alamat'], 0, 55) . '...' : $value['alamat'];
                $nama = (strlen($value['nama']) > 36) ? substr($value['nama'], 0, 36) . '...' : $value['nama'];
                $item01 = new CarouselColumnTemplateBuilder($nama, $alamat, 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665087/location_halal_kurekb.png', $array_action_builders);
                array_push($column_templates, $item01);
            }
            $carouselbuilder = new CarouselTemplateBuilder($column_templates);
            $messageBuilder = new TemplateMessageBuilder("Rumah Makan Halal", $carouselbuilder);
            $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
        } else {
            $message = 'Duh!! Maaf ya kak, MBot gak nemu rumah makannya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested //tested
    public function sendDaftarRestoranRekomendasi($lokasi, $event) {
        $latlong = $this->muslimshalat->getLatituteLongitute($lokasi);
        $lokasis = $this->googleplace->getRestoranLocationPopuler($latlong['lat'], $latlong['long']);
        $message = 'Bentar MBot cariin Daftar Rumah makannya ya kak..';
        $textMessageBuilder = new TextMessageBuilder($message);
        $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        if (count($lokasis) != 0) {
            $message = 'Ini Daftar Rumah makannya kak..';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
//            foreach ($lokasis as $key => $value) {
//                $locationMap = new LocationMessageBuilder($value['nama'], $value['alamat'], $value['lat'], $value['long']);
//                $this->bot->pushMessage($event['source']['userId'], $locationMap);
//            }
            $column_templates = array();
            foreach ($lokasis as $key => $value) {
                $array_action_builders = array();
                $array_action_builders[] = new PostbackTemplateActionBuilder('Tampilkan Lokasi', '::lokasi+' . $value['nama'] . '+' . $value['lat'] . '+' . $value['long'] . '+' . $value['nama']);
                $alamat = (strlen($value['alamat']) > 55) ? substr($value['alamat'], 0, 55) . '...' : $value['alamat'];
                $nama = (strlen($value['nama']) > 35) ? substr($value['nama'], 0, 35) . '...' : $value['nama'];
                $item01 = new CarouselColumnTemplateBuilder($nama, $alamat, 'https://res.cloudinary.com/institut-teknologi-del/image/upload/v1490665087/location_halal_kurekb.png', $array_action_builders);
                array_push($column_templates, $item01);
            }
            $carouselbuilder = new CarouselTemplateBuilder($column_templates);
            $messageBuilder = new TemplateMessageBuilder("Rumah Makan Halal", $carouselbuilder);
            $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
        } else {
            $message = 'Duh!! Maaf ya kak, Mbot gak nemu rumah makannya nih, coba kakak masukin lebih spesifik lagi lokasinya!';
            $textMessageBuilder = new TextMessageBuilder($message);
            $this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
        }
    }

    //tested
    public function sendKonfirmasi($topik, $event) {
        $actionBuilders = array();
        $actionBuilders[] = new PostbackTemplateActionBuilder('Ya', '::news ' . $topik . ' more');
        $actionBuilders[] = new PostbackTemplateActionBuilder('Tidak', '::tidak');
        $confirm = new ConfirmTemplateBuilder("Ingin membaca Artikel/Berita lebih banyak ??", $actionBuilders);
        $messageBuilder = new TemplateMessageBuilder("Baca Berita Lebih", $confirm);
        $this->bot->pushMessage($event['source']['userId'], $messageBuilder);
    }

}
