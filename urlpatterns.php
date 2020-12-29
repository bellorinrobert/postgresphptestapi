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

    $method = isset($_POST['method']) ? $_POST['method'] : '';
    $urlpatternListId = isset($_POST['urlpattern_list_id']) ? $_POST['urlpattern_list_id'] : '';
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $text = isset($_POST['urlpattern_list_text']) ? $_POST['urlpattern_list_text'] : '';
    $oldText = isset($_POST['old_urlpattern_list_text']) ? $_POST['old_urlpattern_list_text'] : '';

    if (empty($method)) {
        throw new \Exception('method is empty');
    }
    if (empty($urlpatternListId)) {
        throw new \Exception('urlpattern_list_id is empty');
    }
    if (empty($text)) {
        throw new \Exception('urlpattern_list_text is empty');
    }
    if ('update' == $method and (empty($text) or empty($oldText))) {
        throw new \Exception('text or old_text cant be empty in update method');
    }

    $processor = new UrlPatternProcessor($db, $urlpatternListId, $username);

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
