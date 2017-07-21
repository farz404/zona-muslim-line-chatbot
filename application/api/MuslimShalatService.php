<?php


class MuslimShalatService {

    //final value
    public $muslim_salat_service = 'http://muslimsalat.com/';
    public $muslim_salat_key = '.json?key=ce73bca4b628407e8979496ddad86afd';

    //get jadwal sholat 5 waktu di lokasi tertentu
    public function getWaktuSholat($lokasi) {
        $daftarString = 'fajrasrdhuhrmaghribisha';
        $request = $this->muslim_salat_service . $lokasi . $this->muslim_salat_key;
        $curl = curl_init($request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            die('demmmmmm bad request' . var_export($info));
        }
        curl_reset($curl);
        curl_close($curl);
        //decode respon
        $decoded = json_decode($curl_response);
        $listSholat = array();
        if (strpos($curl_response, 'items') != 8176) {
            foreach ($decoded->items[0] as $key => $value) {
                if (strpos($daftarString, $key) !== false) {
                    $listSholat[$key] = $value;
                }
            }
        }
        return $listSholat;
    }

    //get jadwal sholat sesuai list waktu sholat, dilokasi tertentu
    public function getWaktuSholatList($lokasi, $daftar) {
        $daftarString = "";
        foreach ($daftar as $key => $value) {
            $daftarString .= $value;
        }
        $request = $this->muslim_salat_service . $lokasi . $this->muslim_salat_key;
        $curl = curl_init($request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            die('demmmmmm bad request' . var_export($info));
        }
        curl_reset($curl);
        curl_close($curl);
        $decoded = json_decode($curl_response);
        $listSholat = array();
        if (strpos($curl_response, 'items') != 8176) {
            foreach ($decoded->items[0] as $key => $value) {
                if ($key === 'date_for') {
                    $listSholat[$key] = $value;
                }
                if (strpos($daftarString, $key) !== false) {
                    $listSholat[$key] = $value;
                }
            }
        }
        return $listSholat;
    }
    
        //get jadwal sholat 5 waktu di lokasi tertentu
    function getWaktuSholatAndTimeZone($lokasi) {        
        $muslim_salat_service = 'http://muslimsalat.com/';
        $muslim_salat_key = '.json?key=ce73bca4b628407e8979496ddad86afd';
    
        $daftarString = 'fajrasrdhuhrmaghribisha';
        $request = $muslim_salat_service . $lokasi . $muslim_salat_key;
        $curl = curl_init($request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            die('demmmmmm bad request' . var_export($info));
        }
        curl_reset($curl);
        curl_close($curl);
        //decode respon
        $decoded = json_decode($curl_response);        
        $listSholat = array();
        $listSholat['timezone'] = $decoded->timezone;
        
        if (strpos($curl_response, 'items') != 8176) {
            foreach ($decoded->items[0] as $key => $value) {
                if (strpos($daftarString, $key) !== false) {
                    $listSholat[$key] = $value;
                }
            }
        }        
        return $listSholat;
    }
    

    //get lat & long dari lokasi sesuai keyword
    public function getLatituteLongitute($location) {
        $muslim_salat_service = 'http://muslimsalat.com/';
        $muslim_salat_key = '.json?key=ce73bca4b628407e8979496ddad86afd';
        $request = $muslim_salat_service . $location . '+indonesia' . $muslim_salat_key;
        $curl = curl_init($request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            die('Demm Error ' . var_export($info));
        }
        curl_close($curl);
        $decoded = json_decode($curl_response);
        return array(
            'lat' => $decoded->latitude,
            'long' => $decoded->longitude);
    }

}
