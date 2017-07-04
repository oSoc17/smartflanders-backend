<?php

namespace oSoc\smartflanders;

require __DIR__ . '/../../vendor/autoload.php';
use \Dotenv;


// TODO publish static data with cache header on separate api point (no priority)

// If no preferred content type is specified, prefer turtle
if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
    $_SERVER['HTTP_ACCEPT'] = 'text/turtle';
}

$filename = null;

$fs = new \otn\linkeddatex2\gather\ParkingHistoryFilesystem(__DIR__ . "/out", __DIR__ . "/../../resources");

if (!isset($_GET['page']) && !isset($_GET['time'])) {
    $filename = $fs->get_last_page();
} else if (isset($_GET['page'])) {
    // If page name is provided, it must be exact
    $filename = $_GET['page'];
    if (!$fs->has_file($filename)) {
        http_response_code(404);
        die();
    }
} else if (isset($_GET['time'])) {
    // If timestamp is provided, find latest file before timestamp
    $filename = $fs->get_closest_page_for_timestamp(strtotime($_GET['time']));
    if (!$filename) {
        http_response_code(404);
        die();
    }
}

if (!isset($_GET['page'])) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../");
    $dotenv->load();
    header("Access-Control-Allow-Origin: *");
    header('Location: ' . $_ENV["BASE_URL"] . '?page=' . $filename);
} else {
    $graphs = $fs->get_graphs_from_file_with_links($filename);
    $historic = true;
    if ($filename === $fs->get_last_page()) {
        $historic = false;
    }
    \otn\linkeddatex2\View::view($_SERVER['HTTP_ACCEPT'], $graphs, $historic);
}

