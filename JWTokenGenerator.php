<?php

class JWTokenGenerator
{
    const SECRET_KEY = 'abcd123$';
    const INI_FILE = '/var/livigent/etc/livigent.ini';
    private $payload = array();

    public function __construct()
    {
        $this->payload = $this->getPayload();
    }

    private function getPayload()
    {
        $ini = parse_ini_file(self::INI_FILE, true);

        return array(
            'db_name' => $ini['General']['db_name'],
            'db_user' => $ini['General']['db_user'],
            'db_pass' => $ini['General']['db_pass'],
            'db_port' => $ini['General']['db_port']
        );
    }

    public function generate()
    {
        // Create token header as a JSON string
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));

        // Create token payload as a JSON string
        $payload = json_encode($this->payload);

        // Encode Header to Base64Url String
        $base64UrlHeader = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($header));

        // Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($payload));

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::SECRET_KEY, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($signature));

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }
}
