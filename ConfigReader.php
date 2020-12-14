<?php

class ConfigReader
{
    const INI_FILE = '/var/livigent/etc/livigent.ini';
    private $data = array();

    public function __construct()
    {
        $this->data = parse_ini_file(self::INI_FILE, true);
    }

    public function getDBSettings()
    {
        return array(
            'db_name' => $this->data['General']['db_name'],
            'db_user' => $this->data['General']['db_user'],
            'db_pass' => $this->data['General']['db_pass'],
            'db_port' => $this->data['General']['db_port']
        );
    }
}
