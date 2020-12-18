<?php

class DB
{
    protected $credentials;
    protected $conn;

    public function __construct($credentials)
    {
        $this->credentials = $credentials;
    }

    public function connect() {
        $options = "host={$this->credentials['db_host']}" .
            " port={$this->credentials['db_port']}".
            " dbname={$this->credentials['db_name']}" .
            " user={$this->credentials['db_user']}" .
            " password={$this->credentials['db_pass']}";

        try {
            $this->conn = pg_connect($options);
        } catch (Throwable $th) {
            throw new \Exception("Postgres error: connection failed");
        }

        return $this->conn;
    }

    public function getPassByUsername($username)
    {
        $query = "SELECT acl_user_pass 
            FROM liv2_acl_users 
            WHERE acl_user_name = '{$username}' 
            LIMIT 1";

        $rsData = pg_query($this->conn, $query);

        $rowCount = pg_num_rows($rsData);

        if (empty($rowCount)) {
            return '';
        }

        $rsArray = pg_fetch_all($rsData, PGSQL_ASSOC);

        return (count($rsArray) > 0) ? $rsArray[0]['acl_user_pass'] : '';
    }
}