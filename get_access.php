<?php

require_once 'ConfigReader.php';
require_once 'JWToken.php';

$headers = getallheaders();

$token = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($token)) {
    die('wrong auth');
}

$tokenClass = new JWToken();

$result = $tokenClass->decrypt($token);

if (!$result['isValidSignature']) {
    die('wrong auth');
}

$config = new ConfigReader();
echo json_encode($config->getDBSettings());