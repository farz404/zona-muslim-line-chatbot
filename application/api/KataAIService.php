<?php

class KataAIService {

    //final
    public $baseURL = 'https://api.kata.ai/v1/insights?m=';
    public $key = '&api_token=f350169c-ce69-42fc-bd05-7d6eba20d6ba';

    public function getLokasi($pesan) {
        $result = array();
        $url = $this->baseURL . $pesan . $this->key;
        $response = \Httpful\Request::get($url)->send();
        //pesan yang dikirim tidak valid
        if (count($response->body->entities) === 0) {
            return "KOSONG";
        } else {
            foreach ($response->body->entities as $key => $value) {
                if ($value->entity === 'LOCATION') {
                    if ($value->fragment != 'masjid' && $value->fragment != 'restoran') {
                        array_push($result, $value->fragment);
                    }
                }
            }
            //entiti lokasi pada pesan tidak ditemukan
            if (count($result) === 0) {
                return "KOSONG";
            }
            return $result[0];
        }
    }

}
