<?php

//----------------------------------------------------------------------------------------------------------------------
abstract class Data_Access {
    protected $table = '';
    protected $pk_field = 'id';

	//--------------------------------------------------------------------------------------------------------------------
	protected function dbConnect() {

		// we'll move the DB credentials into an INI file in the next lesson and create an app setup class that 
		// defines all constants from an app_config database table.
		define("CONST_DB_HOST", "suleiman.db.elephantsql.com");  // update with the location of your MySQL host.
		define("CONST_DB_USERNAME", "blzsmbxf");
		define("CONST_DB_PASSWORD", "k_X_nbcV7kiPsQ0d7i1fRBYUPVKmecAg");
		define("CONST_DB_SCHEMA", "blzsmbxf");
		define("CONST_DB_PORT", 5432);
		define("CONST_DB_OPTIONS", "dbname=test user=lamb password=bar");

		// define("CONST_DB_HOST", "192.168.253.252");  // update with the location of your MySQL host.
		// define("CONST_DB_USERNAME", "livigent");
		// define("CONST_DB_PASSWORD", "XJ2PHD");
		// define("CONST_DB_SCHEMA", "livigent_ver_232_64");
		// define("CONST_DB_PORT", 9001);
		// define("CONST_DB_OPTIONS", "dbname=test user=lamb password=bar");
		

		// establish a database connection
		if (!isset($GLOBALS['dbConnection'])) {
			$options = "host=".CONST_DB_HOST." port=".CONST_DB_PORT." dbname=".CONST_DB_SCHEMA." user=".CONST_DB_USERNAME." password=".CONST_DB_PASSWORD;
			try {
				$GLOBALS['dbConnection'] = pg_connect($options);
				$responseArray = App_Response::getResponse('200');
				$responseArray['message'] = 'Database connection successful.';

			} catch (Throwable $th) {
				$responseArray = App_Response::getResponse('500');
				$responseArray['message'] = "Postgres error: connection failed";
			}
		}
		return $responseArray;
	}

	//--------------------------------------------------------------------------------------------------------------------
	protected function getResultSetArray($varQuery) {

		// attempt the query
        $rsData = pg_query($GLOBALS['dbConnection'], $varQuery);

		if (isset($GLOBALS['dbConnection']->errno) && ($GLOBALS['dbConnection']->errno != 0)) {
			// if an error occurred, raise it.
			$responseArray = App_Response::getResponse('500');
			$responseArray['message'] = 'Internal server error. Postgres error: ' . $GLOBALS['dbConnection']->errno . ' ' . $GLOBALS['dbConnection']->error;
		} else {
            // success
			$rowCount = pg_num_rows($rsData);
			
			if ($rowCount != 0) {
				// move result set to an associative array
                $rsArray = pg_fetch_all($rsData);
			
				// add array to return
				$responseArray = App_Response::getResponse('200');
				$responseArray['dataArray'] = $rsArray;
			
			} else {
				// no data returned
				$responseArray = App_Response::getResponse('204');
                $responseArray['message'] = 'Query did not return any results.';
			}
			
		}

		return $responseArray;
		
	}

	protected function insert($values)
    {
//        0 = PGSQL_EMPTY_QUERY
//        1 = PGSQL_COMMAND_OK
//        2 = PGSQL_TUPLES_OK
//        3 = PGSQL_COPY_TO
//        4 = PGSQL_COPY_FROM
//        5 = PGSQL_BAD_RESPONSE
//        6 = PGSQL_NONFATAL_ERROR
//        7 = PGSQL_FATAL_ERROR

	    $resource = pg_insert($GLOBALS['dbConnection'], $this->table, $values);

        $responseArray = array(
            'success' => true,
            "response" => "200",
            "responseDescription" => "The request has succeeded",
            'dataArray' => null
        );

        if (isset($GLOBALS['dbConnection']->errno) && ($GLOBALS['dbConnection']->errno != 0)) {
            // if an error occurred, raise it.
            $responseArray = App_Response::getResponse('500');
            $responseArray['message'] = 'Internal server error. Postgres error: ' . $GLOBALS['dbConnection']->errno . ' ' . $GLOBALS['dbConnection']->error;

            return $responseArray;
        }

        if ($resource === false) {
            $responseArray = App_Response::getResponse('500');
            $responseArray['message'] = 'Internal server error. Unable to insert data';
        } else {
            $responseArray['dataArray'] = $this->getLastInsertRow($this->pk_field);
            $responseArray['success'] = (pg_result_status($resource) == PGSQL_COMMAND_OK);
        }

        return $responseArray;
    }

    protected function getBy($values)
    {
        $resource = pg_select($GLOBALS['dbConnection'], $this->table, $values);

        $responseArray = array(
            'success' => true,
            "response" => "200",
            "responseDescription" => "The request has succeeded"
        );

        if ($resource) {
            $responseArray['dataArray'] = $resource;
        } else {
            $responseArray['dataArray'] = null;
            $responseArray['responseDescription'] = 'No records found';
        }

        return $responseArray;
    }

    protected function edit($values, $condition, $pkValue)
    {
        $resource = pg_update($GLOBALS['dbConnection'], $this->table, $values, $condition);

        $responseArray = array(
            'success' => true,
            "response" => "200",
            "responseDescription" => "The request has succeeded"
        );

        if (isset($GLOBALS['dbConnection']->errno) && ($GLOBALS['dbConnection']->errno != 0)) {
            // if an error occurred, raise it.
            $responseArray = App_Response::getResponse('500');
            $responseArray['message'] = 'Internal server error. Postgres error: ' . $GLOBALS['dbConnection']->errno . ' ' . $GLOBALS['dbConnection']->error;

            return $responseArray;
        }

        $currentRow = $this->getRowByPk($pkValue);

        if (is_null($currentRow)) {
            $responseArray['responseDescription'] = 'No records found';

            return $responseArray;
        }

        if ($resource) {
            $responseArray['dataArray'] = $currentRow;
        } else {
            $responseArray = App_Response::getResponse('500');
            $responseArray['message'] = 'Internal server error. Unable to update data';
        }

        return $responseArray;
    }

    protected function getRowByPk($pkValue)
    {
        $rsData = pg_query($GLOBALS['dbConnection'], "SELECT * 
            FROM {$this->table}
            WHERE {$this->pk_field} = {$pkValue}");

        $rowCount = pg_num_rows($rsData);

        if ($rowCount == 0) {
            return null;
        }

        $result = pg_fetch_all($rsData);

        return (count($result) > 0) ? $result[0] : null;
    }

    protected function getLastInsertRow()
    {
        $rsData = pg_query($GLOBALS['dbConnection'], "SELECT * FROM {$this->table} ORDER BY {$this->pk_field} DESC LIMIT 1");

        $rowCount = pg_num_rows($rsData);

        if ($rowCount == 0) {
            return null;
        }

        $result = pg_fetch_all($rsData);

        return (count($result) > 0) ? $result[0] : null;
    }

    protected function delete($pkValue)
    {
        $responseArray = array(
            'success' => true,
            "response" => "200",
            "responseDescription" => "The request has succeeded",
            'dataArray' => $this->getRowByPk($pkValue)
        );

        $values = array(
            $this->pk_field => $pkValue
        );

        $resource = pg_delete($GLOBALS['dbConnection'], $this->table, $values);

        if ($resource === false) {
            $responseArray = App_Response::getResponse('500');
            $responseArray['message'] = 'Internal server error. Unable to delete record';
            $responseArray['dataArray'] = null;
        } else {
            $responseArray['success'] = (pg_result_status($resource) == PGSQL_COMMAND_OK);
        }

        return $responseArray;
    }

}