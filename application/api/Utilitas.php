<?php

class Utilitas {

    public function getCleanMessage($pesan) {
        $array = explode(" ", $pesan);
        $cleanMessage = '';
        $result = array();
        for ($i = 0; $i < count($array); $i++) {
            $temp = preg_replace("/[^A-Za-z0-9 ]/", '', $array[$i]);
            $temp = strtolower($temp);
            if ($i === count($array)) {
                $cleanMessage = $cleanMessage . $temp;
            } else {
                $cleanMessage = $cleanMessage . '+' . $temp;
            }
        }
        //append text tambahan atasi bad request
        $cleanMessage = 'aaa' . $cleanMessage;
        return $cleanMessage;
    }

    public function getDaftarSholat($pesan) {
        $daftarSholat = array();
        if (strpos($pesan, 'subuh') !== false || strpos($pesan, 'subu') !== false) {
            array_push($daftarSholat, 'fajr');
        }
        if (strpos($pesan, 'ashar') !== false || strpos($pesan, 'asr') !== false || strpos($pesan, 'asar') !== false || strpos($pesan, 'azar') !== false || strpos($pesan, 'azr') !== false || strpos($pesan, 'ajr') !== false) {
            array_push($daftarSholat, 'asr');
        }
        if (strpos($pesan, 'dzuhur') !== false || strpos($pesan, 'djuhur') !== false || strpos($pesan, 'juhur') !== false || strpos($pesan, 'johor') !== false || strpos($pesan, 'johot') !== false || strpos($pesan, 'djohot') !== false || strpos($pesan, 'dzohot') !== false) {
            array_push($daftarSholat, 'dhuhr');
        }
        if (strpos($pesan, 'maghrib') !== false || strpos($pesan, 'maghrip') !== false || strpos($pesan, 'magrip') !== false || strpos($pesan, 'magrib') !== false || strpos($pesan, 'mahgrib') !== false || strpos($pesan, 'mahgrip') !== false) {
            array_push($daftarSholat, 'maghrib');
        }
        if (strpos($pesan, 'isya') !== false || strpos($pesan, 'isa') !== false || strpos($pesan, 'isha') !== false) {
            array_push($daftarSholat, 'isha');
        }
        return $daftarSholat;
    }

    public function distance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return round($miles * 1.609344, 3);
    }

    public function cekContainShalat($pesan) {
        $kamusSholat = array('sholat', 'salat', 'solat', 'shalat', 'waktu', 'jadwal');
        foreach ($kamusSholat as $key => $value) {
            if (strpos($pesan, $value) !== false) {
                return true;
            }
        }
        return false;
    }

    public function cekContainRestoran($pesan) {
        $kamusSholat = array('rumah', 'makan', 'rumah makan', 'restoran', 'restaurant');
        foreach ($kamusSholat as $key => $value) {
            if (strpos($pesan, $value) !== false) {
                return true;
            }
        }
        return false;
    }

    public function cekContainMasjid($pesan) {
        $kamusSholat = array('masjid', 'mesjid', 'masjit', 'masjit');
        foreach ($kamusSholat as $key => $value) {
            if (strpos($pesan, $value) !== false) {
                return true;
            }
        }
        return false;
    }

    public function cekContainBerita($pesan) {
        $kamusSholat = array('berita', 'artikel', 'berita terbaru', 'artikel terbaru', 'berita muslim', 'artikel muslim');
        foreach ($kamusSholat as $key => $value) {
            if (strpos($pesan, $value) !== false) {
                return true;
            }
        }
        return false;
    }

}
