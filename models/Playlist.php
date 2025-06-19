<?php

class Playlist {
    private $id;
    private $name;
    private $user_id;
    private $tracks = [];

    public function __construct($id, $name, $user_id) {
        $this->id = $id;
        $this->name = $name;
        $this->user_id = $user_id;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function addTrack($track) {
        $this->tracks[] = $track;
    }

    public function getTracks() {
        return $this->tracks;
    }

    public function removeTrack($trackId) {
        foreach ($this->tracks as $key => $track) {
            if ($track->getId() === $trackId) {
                unset($this->tracks[$key]);
                break;
            }
        }
    }
}