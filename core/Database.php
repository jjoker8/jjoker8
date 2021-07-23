<?php

/**
 *
 * Description: Database Connector Singleton-Pattern
 *
 * @author Joshua Isooba <joshua@sysdesign.de>
 *
 */
class Database
{

    private static $_Instance;

    private $_pdo;
    private $_tables;
    private $_query;
    private $_results;
    private $_error = false, $_count = 0;
    private $_fromToDate;
    private $_dateOfWeek;

/*
    private $_key_column_usage;
    private $_referenced_table_name;
    private $_referenced_table_column;
*/
    /*
     * Get an Instance of the Database object
     * @return Instance
     */
    public static function getInstance()
    {
        if (!self::$_Instance) {
            // If no Instance then create one
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * @param const DB_NAME
     * @param const DB_USER
     * @param const DB_PASSWORD
     */
    private function __construct()
    {
    	$DSN = 'mysql:host='. $_SERVER['SERVER_ADDR'] .';port='. $_SERVER['DB_PORT'] .';dbname=' . $_SERVER['DB_NAME']. ';charset=utf8mb4';
        try {
        	$this->_pdo = new \PDO($DSN, $_SERVER['DB_USER'], $_SERVER['DB_PASS']);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

            // Debuggen
            //echo '<pre>' . print_r('Successfully Connected to Database: '. DB_NAME,1) . '</pre>';

        } catch (PDOException $e) {
            die("Oops, could not establish connecion, maybe the server is down for servicing... ! Please try later...");
        }
    }

    /**
     *
     * @return ArrayObject
     */
    public function getKeyPair($tbl, $params = [], $mode = PDO::FETCH_OBJ)
    {
        $this->_error = false;

        if ($this->_query = $this->_pdo->prepare($stmt)) {

            $argv = 1;
            if (count($params)) {
                foreach ($params as $value) {
                    $this->_query->bindValue($argv, $value);
                    $argv++;
                }
            }

            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll($mode);
                $this->_count   = $this->_query->rowCount();
            } else {
                $this->_error = true;
            }
        }

        return $this;
    }

    /**
     *
     * @param $stmt
     * @param array $params
     * @return Database
     */
    public function executeQuery($stmt, $params = [])
    {
        $this->_error = false;

        if ($this->_query = $this->_pdo->prepare($stmt)) {

            $argc = 1;
            if (count($params)) {
                foreach ($params as $value) {
                    $this->_query->bindValue($argc, $value);
                    $argc++;
                }
            }

            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count   = $this->_query->rowCount();
            } else {
                $this->_error = true;
            }
        }

        return $this;
    }

	/**
	 *
	 * @param $command
	 * @param $tbl
	 * @param $where
	 * @return Database|boolean
	 */
    public function buildQuery($command, $tbl, $where = [])
    {
        if (count($where) === 3) {
            // allowed operators. You can add more...
            $operators = array('=', '>', '<', '>=', '<=', '!=', 'AND');

            $field    = $where[0];
            $operator = $where[1];
            $value    = $where[2];

            if (in_array($operator, $operators)) {
                $stmt = "{$command} FROM {$tbl} WHERE {$field} {$operator} ?";
                // debug
                //echo '<br /><br />'. $stmt .' <br />';

                if (!$this->executeQuery($stmt, array($value))->error()) {
                    return $this;
                }
            }
        }
        return false;
    }

    /**
     * specialisation for the buildQery function with PDO::FETCH option
     *
     * @param $query $sqlQuery
     * @param $mode  FetchMode ist obligatorisch, Vorgabe is PDO::FETCH_OBJ
     * @return PDOException
     */
    public function queryExecute($query, $mode = PDO::FETCH_OBJ)
    {
        $this->_query = $this->_pdo->prepare($query);

        try {
            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll($mode);
                $this->_count   = $this->_query->rowCount();
                return $this->_results;
            }
        }
        catch (PDOException $e) {
            return $e->getMessage(); // return exception
        }

    }

    /**
     *
     * @param $query
     * @return array|column 0
     */
    public function executeUDF($query)
    {
        $this->_query = $this->_pdo->prepare($query);

        try {
            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetch(PDO::FETCH_COLUMN, 0);
                $this->_count   = $this->_query->rowCount();
                return $this->_results;
            }
        }
        catch (PDOException $e) {
            return $e->getMessage(); // return exception
        }
    }

    /**
     *
     * @param $udf
     * @param $field
     * @return array|column_id
     */
    public function GetEntityId($udf, $field)
    {
        return $this->executeUDF('SELECT ' . $udf .'("'. $field . '")');
    }

    public function GetColumn_Id($tbl, $field, $value)
    {
        $retval = $this->buildQuery('SELECT', $tbl, [$field, '=', $value]);
        if ($retval->_count = 0) {
            $this->insert($tbl, array('id' => NULL, $field => $value));
        }
        return $retval->_results[0]->id;
    }

    /**
     *
     * @param string $tbl_name
     * @param PDO::FETCHMODE $mode optional, default $mode = PDO::FETCH_OBJ
     */
    public function queryBuilder($tbl_name, $mode = PDO::FETCH_OBJ)
    {
        $this->_key_column_usage = $this->getKeyColumnUsage($tbl_name);

        // Tabellenspalten holen und mit 't0' Prefix, also SQL-Variable versehen  (PHP >= 5.3+):
        // Für  (PHP4) preg_filter durch preg_replace ersetzen.
        $this->_column_name = preg_filter('/^/', 't0.', $this->getColumns($tbl_name));

        if (count($this->_key_column_usage) > 0) {

            // turn these into arrays
            $this->_referenced_table_name = [];
            $this->_referenced_table_column = [];

            for ($i = 0; $i < count($this->_key_column_usage); $i++) {
                foreach ($this->getReferencedColumns($this->_key_column_usage[$i]->REFERENCED_TABLE_NAME, $tbl_name) as $value1) {
                    array_push($this->_column_name, 't' . ($i + 1) . '.' . $value1);
                }
            }

            for ($i = 0; $i < count($this->_key_column_usage); $i ++) {
                array_push($this->_referenced_table_name, $this->_key_column_usage[$i]->REFERENCED_TABLE_NAME . ' t' . ($i + 1));
                array_push($this->_referenced_table_column, ' t0.' . $this->_key_column_usage[$i]->COLUMN_NAME . ' = t' . ($i + 1) . '.' . $this->_key_column_usage[$i]->REFERENCED_COLUMN_NAME);
            }

            $query = 'SELECT ' . implode(', ', $this->_column_name) . ' FROM ' . $tbl_name . ' t0 LEFT JOIN (' . implode(', ', $this->_referenced_table_name) . ') ON (' . implode(' AND', $this->_referenced_table_column) . ')';
            // debug
            //echo '<pre>' . print_r(str_replace(" LEFT", "<br \/>LEFT", $query),1) . '</pre>'; exit;

            // which class method called me (this mehod) ?
            $caller_func = debug_backtrace()[1]['function'];
            switch ($caller_func) {
                case 'findByMonth':
                    // last working day of previous month
                    $last_wday_previous_month = date('Y-m-d', strtotime('last day of previous month'));
                    $query .= ' WHERE dueDate BETWEEN "'. $this->GetLastWorkingDay(1) .'" AND "' . $this->GetLastWorkingDay(-1) .'" ORDER BY t0.id DESC';

                    // debug
                    //echo '<br><br><pre>' . $query . '</pre>';
                    //echo '<pre>' . print_r($this->getReferencedColumns('bank_account',$tbl_name),1) .'</pre>'; exit();
                    break;

                case  'findAll':
                	$query .= ' ORDER BY t0.id DESC';
                	break;

                case 'findById':
                	$query .= ' WHERE t0.id='. $this->id;
                	break;

                case 'findByDate':
                	$query .= ' WHERE dueDate BETWEEN '. $this->_fromToDate .' ORDER BY t0.id DESC';
                	break;

                case 'update':
                    $query = 'UPDATE ' . $tbl_name . ' LEFT JOIN ' . $this->_referenced_table_column;
                    break;

                case 'findByWeek':
                	$query .= ' WHERE (SELECT WEEK(dueDate) = (SELECT WEEK('. "'" . $this->_dateOfWeek ."'" .'))) ORDER BY id DESC';
                	#echo $query;
                	break;

                case 'get_last_insert_id':
                    echo $query;
                    break;

                default:
                    break;
            }
        } else {

            $query = 'SELECT ' . implode(', ', $this->_column_name) . ' FROM ' . $tbl_name . ' t0 ';
        }
        return $this->queryExecute($query);
    }

    /**
    * @param string $tbl
     * @return PDOException
     */
    public function findByMonth($tbl)
    {
        return $this->queryBuilder($tbl);
    }

    /**
     * @param string $tbl
     * @return PDOException
     */
    public function findAll($tbl)
    {
    	return $this->queryBuilder($tbl);
    }

    /**
     * @param string $tbl_name
     * @param integer $id
     * @return PDOException
     */
    public function findById($tbl_name, $id){
    	$this->id = $id;
    	return $this->queryBuilder($tbl_name, $id);
    }

    /**
     *
     * @param string $tbl_name
     * @param array $fromToDate
     * @return PDOException
     */
    public function findByDate($tbl_name, $fromToDate) {
    	$this->_fromToDate = '"' . implode('" AND "',$fromToDate).'"';
		return $this->queryBuilder($tbl_name);
    }

    /**
     *
     * @param unknown $tbl
     * @param unknown $date
     * @return array|unknown
     */
    function findByWeek($tbl, $date = NULL)
    {
    	$this->_dateOfWeek = $date ?? date('Y-m-d', strtotime('now'));
		// debug
    	#$this->printData($this->_dateOfWeek);

    	return $this->queryBuilder($tbl);
    }

    /**
     *
     * @param table name $tbl
     * @param array $where
     * @return Database|boolean
     */
    public function get($tbl, $where)
    {
        return $this->buildQuery('SELECT *', $tbl, $where);
    }

    /**
     * Usage: tableName, array('field', '=', 'value')
     * @param string $tbl
     * @param array $where
     * @return Database|boolean
     *
     * short example:
     * $db = Database::getInstance()->delete('users', array('username', '=', 'admin'));
     *
     * long example:
     * $db = Database::getInstance();
     * $db->delete('users', array('username', '=', 'admin'));
     */
    public function delete($tbl, $where)
    {
        return $this->buildQuery('DELETE', $tbl, $where);
        //ALTER TABLE $tbl AUTO_INCREMENT=14;
    }

	/**
 	 * Usage: tableName, array('field' => 'value', ...)
 	 *
 	 * @param string $tbl
 	 * @param array $fields
 	 * @return boolean
 	 *
 	 * short example:
 	 * $db = Database::getInstance()->insert('users', array('username' => 'admin'));
 	 *
 	 * long example:
 	 * $db = Database::getInstance();
 	 * $db->insert('users', array('username => admin',));
 */
    public function insert($tbl, $fields = [])
    {
        $keys   = array_keys($fields);
        $values = '';

        $i = 1;
        foreach ($fields as $field) {
            $values .= '?';
            if ($i < count($fields)) {
                $values .= ', ';
            }
            $i++;
        }

        /**
         *
         * @var Ambiguous $stmt
         */
        $stmt = "INSERT INTO " . $tbl . "(`" . implode('`, `', $keys) . "`) VALUES({$values})";

        if (!$this->executeQuery($stmt, $fields)->error()) {
            return true;
        }
        return false;
    }

	/**
	 *
	 * @return first from results
	 */
    public function first()
    {
        return $this->results()[0];
    }

    /**
     * @param string $tbl      -   table namae
     * @param int    $id       -   tbl primary key
     * @param array  $fields   -   array("field => newvalue")
     *
     * @return none;
     */
    public function update($tbl, $id, $fields)
    {
        $val = '';
        $i   = 1;

        foreach ($fields as $key => $value) {
            $val .= "{$key} = ?";
            if ($i < count($fields)) {
                $val .= ', ';
            }
            $i++;
        }

        $stmt = "UPDATE {$tbl} SET {$val} WHERE id = {$id}";
		$this->printData($stmt);exit("update func");
        if (!$this->executeQuery($stmt, $fields)->error()) {
            return true;
        }
        return false;

    }

    public function error()
    {
        return $this->_error;
    }

    public function count()
    {
        return $this->_count;
    }

    public function results()
    {
        return $this->_results;
    }

    private function getCaller()
    {
        $trace = debug_backtrace();
        $name  = $trace[2]['function'];
        return empty($name) ? 'global' : $name;
    }

    /**
     * get first working day of current month
     *
     * current month = 0, default
     * previous month += 1, 2, 3, ...
     *
     * @param number $_month
     *
     * @return Ambigous <mixed, string, boolean>
     */
    private function GetFirstWorkingDay($_month = 1)
    {
        $dql = 'SELECT LAST_DAY(CURDATE() - INTERVAL ' . $_month . ' MONTH) - INTERVAL
                (
                    CASE WEEKDAY(LAST_DAY(CURDATE() - INTERVAL  ' . $_month . '  MONTH))
                        WHEN 5 THEN 1
                        WHEN 6 THEN 2
                       ELSE 0
                    END
                )   DAY';

        return $this->queryExecute($dql);
    }

    /**
     * get last working day of current month
     *
     * current month = 0, default
     * previous month += 1, 2, 3, ...
     *
     * @param number $_month
     *
     * @return Ambigous <mixed, string, boolean>
     */
    private function GetLastWorkingDay($_month = 0) // default previous month
    {
        $this->_error = false;

        $dql = 'SELECT LAST_DAY(CURDATE() - INTERVAL ' . $_month . ' MONTH) - INTERVAL
                (
                    CASE WEEKDAY(LAST_DAY(CURDATE() - INTERVAL  ' . $_month . '  MONTH))
                        WHEN 5 THEN 1
                        WHEN 6 THEN 2
                       ELSE 0
                    END
                )   DAY';

        if ($this->_query = $this->_pdo->prepare($dql)) {
            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $this->_error = true;
            }
        }
        return $this->_results[0];
    }

    /**
     *
     * @param string $tbl_name
     * @return Database
     */
    public function getKeyColumnUsage($tbl_name, $mode = PDO::FETCH_OBJ) {
        $query = 'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME="' . $tbl_name . '"
                AND REFERENCED_TABLE_SCHEMA IS NOT NULL
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND REFERENCED_COLUMN_NAME IS NOT NULL
                AND CONSTRAINT_SCHEMA=(SELECT DATABASE())';

        return $this->queryExecute($query, $mode);
    }

	/**
	 *
	 * @param string $refTable
	 * @param string $tbl_name
	 * @return associative column array
	 *
	 * hides sensitive information - iban and bic
	 */
	private function getReferencedColumns($refTable, $tbl_name) {
		$query = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=(SELECT DATABASE())
			AND COLUMN_NAME != "iban" AND COLUMN_NAME != "bic"
            AND TABLE_NAME="' . $refTable . '"
                AND COLUMN_NAME NOT IN (
                SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=(SELECT DATABASE())
                AND TABLE_NAME="' . $tbl_name . '")';

		return $this->queryExecute($query, PDO::FETCH_COLUMN);
	}


    /**
     *
     * @param string $tbl_name
     * @return associative column array
     */
    public function getColumns($tbl_name)
    {
        $query = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME="' .$tbl_name. '"
                AND TABLE_SCHEMA=(SELECT DATABASE())
                AND COLUMN_NAME NOT IN (
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME="'.$tbl_name.'"
                    AND INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME IS NOT NULL
                )';
        //echo '<pre>' . print_r($this->executeQuery($query, PDO::FETCH_COLUMN), 1) . '</pre>'; exit();
        return $this->queryExecute($query, PDO::FETCH_COLUMN);
    }

    /**
     *
     * @param  $tbl_name
     * @return Database
     */
    public function getReferencedTableColumns($tbl_name)
    {
        $query = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=(SELECT DATABASE())
            AND TABLE_NAME="' . $tbl_name .'"
            AND COLUMN_KEY != "PRI"
            AND DATA_TYPE="SMALLINT"';

        return $this->queryExecute($query, PDO::FETCH_COLUMN);
    }

    public function getAutoIncrement($tbl)
    {
        return $this->executeQuery('SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCsHEMA = ?', [$tbl, DB_NAME]);
    }

    /**
     *
     * @param array $data
     */
    private function printData($data)
    {
    	echo '<br /><div class="alert alert-info" role="alert"><pre>' . print_r($data, 1) . '</pre></div>';
    }

    /**
     * Magic methode to avoid multiple connection.
     * P.S. has to be empty in order for this to work.
     */
    private function __clone()
    {}

    /**
     * Standard Funktion
     * Verbindungs-Objekt automatische zerstören
     */
    public function __destruct()
    {
        $this->_pdo = null;
        // Debuggen
        // echo '<pre>' . print_r('Datenbank '. Config::get('mysql/db') .' geschlossen !', 1) . '</pre>';
    }
}
