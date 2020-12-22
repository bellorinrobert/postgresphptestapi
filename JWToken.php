<?php

class JWToken
{
    private $config;

    public function __construct()
    {
        $this->config = new ConfigReader();
    }

    private function getBase64UrlSignature($base64UrlHeader, $base64UrlPayload)
    {
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            $this->config->getTokenSecret(),
            true
        );

        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($signature));
    }

    public function encrypt($payload)
    {
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
        $payload = json_encode($payload);
        $base64UrlHeader = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($header));
        $base64UrlPayload = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($payload));
        $base64UrlSignature = $this->getBase64UrlSignature($base64UrlHeader, $base64UrlPayload);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function decrypt($token)
    {
        $jwtArr = array_combine(array('header', 'payload', 'signature'), explode('.', $token));

        $myHash = $this->getBase64UrlSignature($jwtArr['header'], $jwtArr['payload']);

        return array(
            'isValidSignature' => ($myHash == $jwtArr['signature']),
            'payload' => base64_decode($jwtArr['payload']),
            'token_hash' => $jwtArr['signature']
        );
    }
}
