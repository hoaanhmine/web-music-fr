<?php
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/MusicController.php';
require_once 'controllers/UserController.php';

// Initialize the application
session_start();

// Routing logic
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
switch ($requestUri) {
    case '/login':
        $controller = new AuthController();
        $controller->login();
        break;
    case '/register':
        $controller = new AuthController();
        $controller->register();
        break;
    case '/upload':
        $controller = new MusicController();
        $controller->uploadTrack();
        break;
    case '/play':
        $controller = new MusicController();
        $controller->getTrack();
        break;
    case '/profile':
        $controller = new UserController();
        $controller->getUserProfile();
        break;
    default:
        // Load the main layout or a 404 page
        require 'views/layout.php';
        break;
}
?>