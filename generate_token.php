<?php

require_once 'ConfigReader.php';
require_once 'JWToken.php';

$tokenClass = new JWToken();
$config = new ConfigReader();

echo $tokenClass->encrypt($config->getDBSettings());
