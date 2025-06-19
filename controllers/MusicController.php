<?php

namespace App\Controllers;

use App\Models\Track;

class MusicController
{
    public function uploadTrack($request)
    {
        // Handle the music file upload
        if (isset($request['file']) && $request['file']['error'] == 0) {
            $targetDir = __DIR__ . '/../../public/uploads/';
            $targetFile = $targetDir . basename($request['file']['name']);
            $uploadOk = 1;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Check if file is a valid audio format
            $validFormats = ['mp3', 'wav', 'ogg'];
            if (!in_array($fileType, $validFormats)) {
                echo "Sorry, only MP3, WAV & OGG files are allowed.";
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
            } else {
                if (move_uploaded_file($request['file']['tmp_name'], $targetFile)) {
                    // Save track information to the database
                    $track = new Track();
                    $track->title = $request['title'];
                    $track->artist = $request['artist'];
                    $track->file_path = 'uploads/' . basename($request['file']['name']);
                    $track->save();

                    echo "The file " . htmlspecialchars(basename($request['file']['name'])) . " has been uploaded.";
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        } else {
            echo "No file was uploaded or there was an upload error.";
        }
    }

    public function getTrack($id)
    {
        // Retrieve track information from the database
        $track = Track::find($id);
        if ($track) {
            return $track;
        } else {
            echo "Track not found.";
        }
    }
}