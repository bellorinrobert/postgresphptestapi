<?php

require_once 'DB.php';
require_once 'ConfigReader.php';
require_once 'JWToken.php';
require_once 'UrlPatternProcessor.php';

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

    // url processor part //

    $bodyRequest = \json_decode(file_get_contents('php://input'), true);

    if (is_null($bodyRequest)) {
        throw new \Exception('unable to json_decode request body, json is invalid');
    }
    if (!isset($bodyRequest['query'])) {
        throw new \Exception('No "query" in body params');
    }
    if (!isset($bodyRequest['data'])) {
        throw new \Exception('No "data" in body params');
    }

    $ruleLabel = $bodyRequest['query']['rule_label'];
    $method = $bodyRequest['data']['type'];
    $username = $bodyRequest['data']['username'];
    $text = $bodyRequest['data']['new_url'];
    $oldText = $bodyRequest['data']['old_url'];

    if (empty($method)) {
        throw new \Exception('method is empty');
    }
    if (empty($ruleLabel)) {
        throw new \Exception('rule_label is empty');
    }
    if (empty($text)) {
        throw new \Exception('new_url is empty');
    }
    if ('update' == $method and (empty($text) or empty($oldText))) {
        throw new \Exception('new_url or old_url cant be empty in update method');
    }

    $processor = new UrlPatternProcessor($db, $ruleLabel, $username);

    if ('insert' == $method) {
        $processor->insert($text);
    } elseif ('update' == $method) {
        $processor->update($oldText, $text);
    } elseif ('remove' == $method) {
        $processor->remove($text);
    }

    echo 'success';
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
