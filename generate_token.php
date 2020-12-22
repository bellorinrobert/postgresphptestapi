<?php

require_once 'ConfigReader.php';
require_once 'JWToken.php';

$tokenClass = new JWToken();
$config = new ConfigReader();

// TODO: remove test data
$payload = array(
    "expiresIn" => "365 days",
    "data" => [
        "username" => "robert",
        "password" => "1234!@#$"
    ],
    "iat" => 1608322443
);

echo $tokenClass->encrypt($payload);
