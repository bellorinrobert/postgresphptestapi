<?php

//----------------------------------------------------------------------------------------------------------------------
abstract class Data_Access {
  
	//--------------------------------------------------------------------------------------------------------------------
	protected function dbConnect() {

		// we'll move the DB credentials into an INI file in the next lesson and create an app setup class that 
		// defines all constants from an app_config database table.
		// define("CONST_DB_HOST", "suleiman.db.elephantsql.com");  // update with the location of your MySQL host.
		// define("CONST_DB_USERNAME", "blzsmbxf");
		// define("CONST_DB_PASSWORD", "k_X_nbcV7kiPsQ0d7i1fRBYUPVKmecAg");
		// define("CONST_DB_SCHEMA", "blzsmbxf");
		// define("CONST_DB_PORT", 5432);
		// define("CONST_DB_OPTIONS", "dbname=test user=lamb password=bar");

		define("CONST_DB_HOST", "192.168.253.252");  // update with the location of your MySQL host.
		define("CONST_DB_USERNAME", "livigent");
		define("CONST_DB_PASSWORD", "XJ2PHD");
		define("CONST_DB_SCHEMA", "livigent_ver_232_64");
		define("CONST_DB_PORT", 9001);
		define("CONST_DB_OPTIONS", "dbname=test user=lamb password=bar");
		

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
			echo "dfsdfsdf";
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

}