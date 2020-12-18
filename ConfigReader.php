<?php

class ConfigReader
{
    const DB_INI_FILE = '/var/livigent/etc/livigent.ini';
    const CONFIG_INI_FILE = 'config.ini';

    public function __construct()
    {
    }

    public function getTokenSecret()
    {
        $data = parse_ini_file(self::CONFIG_INI_FILE, true);

        return $data['Token']['secret'];
    }

    public function getDBSettings()
    {
        $data =parse_ini_file(self::DB_INI_FILE, true);

        return array(
            'db_host' => $data['General']['db_host'],
            'db_name' => $data['General']['db_name'],
            'db_user' => $data['General']['db_user'],
            'db_pass' => $data['General']['db_pass'],
            'db_port' => $data['General']['db_port']
        );
    }
}
