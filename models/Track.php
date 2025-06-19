<?php

class Track {
    private $id;
    private $title;
    private $artist;
    private $file_path;

    public function __construct($id, $title, $artist, $file_path) {
        $this->id = $id;
        $this->title = $title;
        $this->artist = $artist;
        $this->file_path = $file_path;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getArtist() {
        return $this->artist;
    }

    public function getFilePath() {
        return $this->file_path;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setArtist($artist) {
        $this->artist = $artist;
    }

    public function setFilePath($file_path) {
        $this->file_path = $file_path;
    }

    public function save() {
        // Code to save the track to the database
    }

    public static function find($id) {
        // Code to find a track by its ID
    }

    public static function all() {
        // Code to retrieve all tracks
    }
}