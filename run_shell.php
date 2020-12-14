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

$output = shell_exec("/opt/livigent/crt/opt/livigent/sbin/rpc_client reload_component '\"livigent-dispatcher\"'");

if (is_null($output)) {
    echo 'no output';
} else {
    echo $output;
}
