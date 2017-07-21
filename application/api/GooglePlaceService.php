<?php

class GooglePlaceService {

    protected $google_places;
    //final value
    public $key = 'AIzaSyCnzW3ZY2vUm8t7bhrV0NKsvLF_6SvuIX8';

    function __construct() {
        $this->google_places = new \joshtronic\GooglePlaces($this->key);
    }

    //get lokasi masjid populer
    public function getMasjidLocationPopuler($longitute, $latitude) {
        $this->google_places->location = array($longitute, $latitude);
        $this->google_places->rankby = 'prominence';
        $this->google_places->radius = '5000';
        $this->google_places->keyword = 'masjid'; // Requires keyword, name or types
        $results = $this->google_places->nearbySearch();
        //$temp = json_decode($results);
        $temp = $results['results'];
        $daftarMasjid = array();
        $i = 0;
        foreach ($temp as $key => $value) {
            if ($i === 5) {
                break;
            }
            $temp = array(
                'nama' => $value['name'],
                'lat' => $value['geometry']['location']['lat'],
                'long' => $value['geometry']['location']['lng'],
                //'jarak' => $this->distance(3.5772224, 98.6824763, $value['geometry']['location']['lat'], $value['geometry']['location']['lng']),
                'alamat' => $value['vicinity']
            );
            array_push($daftarMasjid, $temp);
            $i++;
        }
        return $daftarMasjid;
    }

    //get lokasi masjid terdekat
    public function getMasjidLocationNearest($longitute, $latitude) {
        $this->google_places->location = array($longitute, $latitude);
        $this->google_places->rankby = 'distance';
        //$google_places->radius = '5000';
        $this->google_places->keyword = 'masjid'; // Requires keyword, name or types
        $results = $this->google_places->nearbySearch();

        $temp = $results['results'];
        $daftarMasjid = array();
        $i = 0;
        foreach ($temp as $key => $value) {
            if ($i === 5) {
                break;
            }
            $temp = array(
                'nama' => $value['name'],
                'lat' => $value['geometry']['location']['lat'],
                'long' => $value['geometry']['location']['lng'],
                //'jarak' => $this->distance(3.5772224, 98.6824763, $value['geometry']['location']['lat'], $value['geometry']['location']['lng']),
                'alamat' => $value['vicinity'],
            );
            array_push($daftarMasjid, $temp);
            $i++;
        }
        return $daftarMasjid;
    }

    //get lokasi restoran halal populer
    public function getRestoranLocationPopuler($longitute, $latitude) {
        $this->google_places->location = array($longitute, $latitude);
        $this->google_places->rankby = 'distance';
        //$this->google_places->radius = '5000';
        $this->google_places->keyword = 'restaurant+halal'; // Requires keyword, name or types
        $results = $this->google_places->nearbySearch();
        //$temp = json_decode($results);
        $temp = $results['results'];
        $daftarRestoran = array();
        $i = 0;
        foreach ($temp as $key => $value) {
            if ($i === 5) {
                break;
            }
            $temp = array(
                'nama' => $value['name'],
                'lat' => $value['geometry']['location']['lat'],
                'long' => $value['geometry']['location']['lng'],
                //'jarak' => $this->distance(3.5772224, 98.6824763, $value['geometry']['location']['lat'], $value['geometry']['location']['lng']),
                'alamat' => $value['vicinity']
            );
            array_push($daftarRestoran, $temp);
            $i++;
        }
        return $daftarRestoran;
    }

    //get lokasi restoran halal terdekat
    public function getRestoranLocationNearest($longitute, $latitude) {
        $this->google_places->location = array($longitute, $latitude);
        $this->google_places->rankby = 'distance';
        //$google_places->radius = '5000';
        $this->google_places->keyword = 'restaurant+halal'; // Requires keyword, name or types
        $results = $this->google_places->nearbySearch();

        $temp = $results['results'];
        $daftarRestoran = array();
        $i = 0;
        foreach ($temp as $key => $value) {
            if ($i === 5) {
                break;
            }
            $temp = array(
                'nama' => $value['name'],
                'lat' => $value['geometry']['location']['lat'],
                'long' => $value['geometry']['location']['lng'],
                //'jarak' => $this->distance(3.5772224, 98.6824763, $value['geometry']['location']['lat'], $value['geometry']['location']['lng']),
                'alamat' => $value['vicinity']
            );
            array_push($daftarRestoran, $temp);
            $i++;
        }
        return $daftarRestoran;
    }

}
