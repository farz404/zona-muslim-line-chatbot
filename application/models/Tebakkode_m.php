<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Tebakkode_m extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    function log_events($signature, $body) {
        $this->db->set('signature', $signature)
                ->set('events', $body)
                ->insert('eventlog');
        return $this->db->insert_id();
    }

    function getUser($userId) {
        $data = $this->db->where('user_id', $userId)->get('users')->row_array();
        if (count($data) > 0)
            return $data;
        return false;
    }

    function getAllUser() {
        $this->db->select('*');
        $this->db->from("users");
        $query = $this->db->get();
        return $query->result();
    }

    function saveUser($profile) {
        $user = $this->getUser($profile['userId']);
        if (!$user) {
            $this->db->set('user_id', $profile['userId'])
                    ->set('display_name', $profile['displayName'])
                    ->insert('users');
            return $this->db->insert_id();
        }
    }

    function updateUser($id, $lokasibaru) {
        $this->db->set('lokasi', $lokasibaru);
        $this->db->where('user_id', $id);
        $this->db->update('users');
    }

}
