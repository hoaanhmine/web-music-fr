<?php

namespace App\Controllers;

use App\Models\User;

class UserController
{
    public function getUserProfile($userId)
    {
        // Logic to retrieve user profile by user ID
        $user = User::find($userId);
        return $user;
    }

    public function updateUserProfile($userId, $data)
    {
        // Logic to update user profile
        $user = User::find($userId);
        if ($user) {
            $user->username = $data['username'] ?? $user->username;
            $user->email = $data['email'] ?? $user->email;
            // Save other fields as necessary
            $user->save();
            return $user;
        }
        return null;
    }
}