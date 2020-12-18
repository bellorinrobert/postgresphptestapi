<?php

require_once 'DB.php';
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

$tokenPayload = \json_decode($result['payload'], true);

if (empty($tokenPayload)) {
    die('empty payload');
}

$usernameToken = $tokenPayload['data']['username'];
$passwordToken = $tokenPayload['data']['password'];

if (empty($usernameToken) or empty($passwordToken)) {
    die('empty name or password from token');
}

try {
    $config = new ConfigReader();

    $db = new DB($config->getDBSettings());
    $db->connect();

    $encryptedDBPass = $db->getPassByUsername($usernameToken);

    $solt = getSolt($encryptedDBPass);

    $passwordTokenEncrypted = encryptPass($solt, $passwordToken);

    if ($encryptedDBPass != $passwordTokenEncrypted) {
        throw new \Exception('wrong password');
    }

    echo json_encode($config->getDBSettings());
} catch (\Exception $err) {
    echo $err->getMessage();
}

// functions
function getSolt($encryptedDBPass) {
    $explodedPass = explode('$', $encryptedDBPass);
    return $explodedPass[1];
}

function encryptPass($solt, $pass) {
    return 'sha1$' . $solt . '$' . sha1($solt . $pass);
}
