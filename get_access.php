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

$expireTimestamp = strtotime('+' . $tokenPayload['expiresIn'], $tokenPayload['iat']);

if ($expireTimestamp == -1 or $expireTimestamp === false) {
    die('unable to parse expire date: +' . $tokenPayload['expiresIn']);
}

$expires = date('Y-m-d H:i:s', $expireTimestamp);
$current = date('Y-m-d H:i:s');

if ($current > $expires) {
    die('token expired');
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

    $salt = getSalt($encryptedDBPass);

    $passwordTokenEncrypted = encryptPass($salt, $passwordToken);

    if ($encryptedDBPass != $passwordTokenEncrypted) {
        throw new \Exception('wrong password');
    }

    echo json_encode($config->getDBSettings());
} catch (\Exception $err) {
    echo $err->getMessage();
}

// functions
function getSalt($encryptedDBPass) {
    $explodedPass = explode('$', $encryptedDBPass);
    return $explodedPass[1];
}

function encryptPass($salt, $pass) {
    return 'sha1$' . $salt . '$' . sha1($salt . $pass);
}
