<?php 
/*
 * This file is part of the "Another" suite of products.
 *
 * (c) 2020 Another, LLC
 *
 */

if ((!defined('CONST_INCLUDE_KEY')) || (CONST_INCLUDE_KEY !== 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486')) {
	// if accessing this class directly through URL, send 404 and exit
	// this section of code will only work if you have a 404.html file in your root document folder.
	header("Location: /404.html", TRUE, 404);
	echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
	die;
}

//----------------------------------------------------------------------------------------------------------------------
class App_API_InterceptionRule extends Data_Access {

    protected $table = 'liv2_filtering_interceptionrule';
	protected $object_view_name = 'liv2_filtering_interceptionrule';
	protected $pk_field = 'irule_id';

	//----------------------------------------------------------------------------------------------------
	public function __construct() {
        // attempt database connection
        $res = $this->dbConnect();
        
        // if we get anything but a good response ...
        if ($res['response'] != '200') {
            echo "Houston? We have a problem.";
            die;
        }
	}

	//----------------------------------------------------------------------------------------------------
	public function getData($params)
    {
        if (count($params) > 0) {
            return $this->getBy($params);
        }

		// build the query
		$query = "SELECT * FROM " . $this->object_view_name;
		
		$res = $this->getResultSetArray($query);
		
		// if nothing comes back, then return a failure
		if ($res['response'] !== '200') {
			$responseArray = App_Response::getResponse('403');
		} else {
			$responseArray = $res;
		}
		
		// send back what we got
		return $responseArray;

	}

	public function addData($params)
    {
        if (empty($params['source'])) {
            return App_Response::getResponse('400');
        }

        $params['weight'] = $this->getMaxWeight() + 1;

        return $this->insert($params);
    }

    public function updateData($params)
    {
        if (empty($params[$this->pk_field])) {
            return App_Response::getResponse('400');
        }

        $id = (int) $params[$this->pk_field];

        unset($params[$this->pk_field]);

        $condition = array(
            $this->pk_field => $id
        );

        if (!empty($params['weight'])) {
            $weightRow = $this->getBy(array(
                'weight' => $params['weight']
            ));

            if (
                !is_null($weightRow['dataArray'])
                and count($weightRow['dataArray']) > 0
                and $weightRow['dataArray'][0][$this->pk_field] != $id
            ) {
                $query = "UPDATE {$this->table} SET weight = weight + 1 WHERE weight >= {$params['weight']}";
                pg_query($GLOBALS['dbConnection'], $query);
            }
        }

        return $this->edit($params, $condition, $id);
    }

    public function deleteData($params)
    {
        if (empty($params[$this->pk_field])) {
            return App_Response::getResponse('400');
        }

        $id = (int) $params[$this->pk_field];

        return $this->delete($id);
    }

    private function getMaxWeight()
    {
        $query = "SELECT MAX(weight) FROM " . $this->table;

        $res = $this->getResultSetArray($query);

        if ($res['success'] and count($res['dataArray']) > 0) {
            return (int) $res['dataArray'][0]['max'];
        }

        return 0;
    }
}
