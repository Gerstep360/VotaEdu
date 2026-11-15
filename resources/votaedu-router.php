<?php

$publicDirectory = dirname(__DIR__).'/public';
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$requestedFile = realpath($publicDirectory.$requestPath);

if ($requestPath !== '/' && $requestedFile !== false && is_file($requestedFile) && str_starts_with($requestedFile, realpath($publicDirectory).DIRECTORY_SEPARATOR)) {
    return false;
}

require $publicDirectory.'/index.php';
