<?php

/*
 * File: /app/core/BaseModel.php
 *
 * Abstract class that serves as the Model in the MVC framework.
 *
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */
abstract class BaseModel
{

    protected $viewModel;
    protected $conn;

    protected $balance = 0;

    protected $referenced_tbl_name = [];
    protected $referenced_tbl_col = [];

    // german language specific terms
    protected $tbl_name = '';

    protected $tags = [];
    protected $tag_id = '';
    protected $tag_name = '';

    protected $bank_accounts = [];
    protected $account_id = '';
    protected $account_type = '';

    protected $position = [];
    protected $position_id = '';
    protected $position_desc = '';

    protected $recipients = [];
    protected $recipient_id = '';
    protected $recipient_creditor = '';

    protected $umbuchung_id = '';

    protected $data_entry = [];
    protected $entry_count = 0;

    protected $not_applicable = '';

    // currently unused
    protected $transfer = "Umbuchung";
    protected $debit = "Lastschrift";
    protected $credit = "Gutschrift";

    protected $last_update;

    protected $lectures;
    //

    // create the base and utility objects available to all models on model creation
    public function __construct()
    {
        $this->conn = Database::getInstance();
        $this->viewModel = new ViewModel();

        $this->commonViewData();
    }

    // establish viewModel data that is required for all views in this method (i.e. the main template)
    private function commonViewData()
    {
        $this->viewModel->set("token", Token::generate());

        // generic pageTitle derived from Model-Naming Scheme of the Application
        $host = explode('.', gethostbyaddr($_SERVER['SERVER_ADDR']));
        $this->viewModel->set("pageTitle", strtoupper($host[0]) . ' - '. strtolower(str_replace("Model", "", get_class($this))));

        if (get_class($this) != 'ConfigModel' && get_class($this) != 'HomeModel' && get_class($this) != 'Bank_AccountModel') {
			$this->not_applicable = $this->conn->executeUDF('SELECT get_recipient_id("N\/A")');

			$this->data_entry = $this->conn->findAll($this->getTableName());
			// $this->printData($this->conn->count());

			$this->createEntryForm();

			$this->bank_accounts = $this->conn->queryExecute('SELECT * FROM bank_account');
			$this->tags = $this->conn->queryExecute('SELECT * FROM tags');
		}
    }

    /**
     * Returns the table associated to this Model.
     * if self::$tbl_name is empty,
     * it will deduce the table name from the class name.
     *
     * @return string|mixed
     */
    protected final function getTableName()
    {
        if ($this->tbl_name == '') {
            $this->tbl_name = str_replace("model", '',strtolower(get_class($this)));
        } else {
            $this->tbl_name = $this->tbl_name;
        }
        return $this->tbl_name;
    }

    public function createEntryForm() {

    	$tbls = $this->conn->getKeyColumnUsage($this->getTableName());
    	$this->referenced_tbl_name = array_column($tbls, 'REFERENCED_TABLE_NAME');

    	// sort tables to match column order on the view page.
    	sort($this->referenced_tbl_name, 6);

    	$this->referenced_tbl_col = array_combine($this->referenced_tbl_name, $this->conn->getReferencedTableColumns($this->getTableName()));
    	$this->viewModel->set('referenced_tbl_col', $this->referenced_tbl_col);

    	// new entry form fields
    	foreach ($this->referenced_tbl_name as $value) {
    		if ($value == 'bank_account') {
    			$this->viewModel->set($value, $this->conn->queryExecute('SELECT id, type FROM ' . $value, PDO::FETCH_KEY_PAIR));
    		} else {
                $this->viewModel->set($value, $this->conn->queryExecute('SELECT * FROM ' . $value, PDO::FETCH_KEY_PAIR));
            }
    	}
    }

    /**
     * Description: Interprets an XML file into an object
     *
     * @param  filename
     * @return object, or FALSE on failure.
     */
    protected function lecturesMenu($xmlfile)
    {
        if (file_exists($xmlfile)) {
            //$lectures = array();
            $dom = simplexml_load_file($xmlfile);
            foreach ($dom->xpath("//directories/directory") as $value) {
                // to get the text data from a node in SimpleXML, just cast it to a string
                $lectures[] = array('id' => (string) $value->id, 'name' => (string) $value->name, 'action' => (string) $value->action);
            }

            $this->viewModel->set('lecturesMenu', $lectures);
        } else {
            exit('Failed to open ' . $xmlfile);
        }
    }

    /**
     *
     */
    protected function IsNewEntry() {
    	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_new_entry'])) {
    		// get the Gutschrift (deposit/credit) tag_id
    		$this->credit = $this->getId('tags', 'name', 'Gutschrift') ? : $this->getId('tags', 'name', 'credit');

    		// uses stored database function
    		//$this->account_id = $this->conn->GetEntityId('GetAccount_Id', $_POST['account_id']);
    		$this->account_id = $this->getId('bank_account', 'type', $_POST['account_id']);

    		// Add a position or recipient in the corresponding table dynamically
    		// if the user entered one on the form, that is not in the respective table.
    		$this->position_id = $this->getId('position', 'description', $_POST['position_id']);
    		$this->recipient_id = $this->getId('recipients', 'creditor', $_POST['recipient_id']);
    		$this->tag_id = $this->getId('tags', 'name', $_POST['tag_id']);

    		$this->tag_name = $this->conn->executeUDF('SELECT get_tag_name(' . $this->tag_id . ')');
    		$this->balance  = $this->conn->executeUDF('SELECT get_acct_balance(' . $this->account_id . ')');

    		// Preappend a minus sign, if withdrawal.
    		if ($this->tag_id != $this->credit) { // Gutschrift (deposit/credit)
    			$_POST['amount'] *= - 1;
    		}

    		// add entry to database
    		$this->createNewEntry();

    		// test
    		if (APP_DEV == true) {
	    		echo '<br /><div class="alert alert-danger" role="alert"><p>Just for debug mysql-DB uses english format.</p></div>';
	    		echo "Old Balance " . $_POST['account_id'] . ": " . $this->balance;
	    		$this->printData([
	    				$_POST['dueDate'],
	    				$this->account_id,
	    				$this->position_id,
	    				$this->recipient_id,
	    				$this->tag_id,
	    				money_format('%i', $_POST['amount']),
	    				money_format('%i', $_POST['amount'] + $this->balance)
	    		], 1);
	    		echo (APP_DEV == true ) ? "New Balance <pre>" . money_format('%i', $_POST['amount'] + $this->balance). "</pre>": false;
    		}

    		// double booking, if transfer is between internal accounts
    		// swap recipient and account type and tags
    		if ($this->position_id == $this->conn->get('position', ['description', '=', 'Umbuchung' ])->results()[0]->id) {
    			// get creditor field(text) i.e id of $_POST['recipient_id']
    			$this->recipient_creditor = $this->conn->executeUDF('SELECT get_recipient_creditor(' . $this->recipient_id. ')');
    			$this->recipient_id = $this->conn->executeUDF('SELECT get_recipient_id("N\/A")');
    			$this->account_id = $this->conn->get('bank_account', [ 'type', '=', $this->recipient_creditor])->results()[0]->id;	// swap accounts

    			$this->tag_id = $this->credit; // swap tags

    			// since it is a credit to the account in question, produce a positive value
    			$_POST['amount'] *= - 1;
    			$this->balance = $this->conn->executeUDF('SELECT get_acct_balance(' . $this->account_id . ')');

    			// add entry to database
    			$this->createNewEntry();

    			/* test */
    			echo (APP_DEV == true ) ? $this->printData([$_POST['dueDate'], $this->account_id, $this->position_id, $this->recipient_id, $this->tag_id, money_format( '%i', $_POST['amount']), money_format ('%i', $_POST['amount'] + $this->balance )],1) : false;
    		}

    		#echo "<script>alert('Data sent')</script>";
    		echo "<script>entryData.fetchData(offset, $('#pages option:selected').text())</script>";
    		#$this->Redirect();
    	}
    }

    /**
     *
     */
    protected function createNewEntry()
    {
        // amount and balance formated in english for entry in database
        $this->conn->insert('entry', array(
            'id' => NULL,
            'dueDate' => $_POST['dueDate'],
            'account_id' => $this->account_id,
            'position_id' => $this->position_id,
            'recipient_id' => $this->recipient_id,
            'tag_id' => $this->tag_id,
        	'amount' => $_POST['amount'],
        	'balance' => $_POST['amount'] + $this->balance
        ));
    }

    public function Redirect()
    {
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), "/\\");
        header("Location: http://$host$path/entry");
        exit;
    }

    /**
     *
     * @param array $dateRange
     */
    protected function findByDate($dateRange = []) {
    	return $this->conn->findByDate($this->getTableName(), $dateRange);
    }


    /**
     *
     * @param unknown $tbl
     * @param unknown $entity
     * @param unknown $value
     * @return unknown
     */
    protected function getId($tbl, $entity, $value)
    {
    	$results = $this->conn->get($tbl, [ $entity, '=', $value])->results()[0];

    	if ( !$results ) {
    		$this->conn->insert($tbl, [ 'id' => NULL, $entity => $value ]);
    		$results = $this->conn->get($tbl, [ $entity, '=', $value])->results()[0];
    	}

		return $results->id;
    }

    /**
     *
     * @param unknown $tbl
     * @return number
     */
    protected function getLastInsertID($tbl)
    {
        return $this->conn->getAutoIncrement($tbl)->first()->AUTO_INCREMENT-1;
    }

    /**
     *
     * @param unknown $account_type
     * @return number
     */
    protected function getBalance($account_type)
    {
        $last_insert_id = $this->conn->executeQuery('SELECT MAX(id) AS max_id FROM ' . $this->getTableName() . ' WHERE account_id= ?', [$account_type], PDO::FETCH_COLUMN)->first()->max_id;
        $last_balance = $this->conn->executeQuery('SELECT balance FROM ' . $this->getTableName() . ' WHERE account_id = ? AND id = ?', [$account_type, $last_insert_id], PDO::FETCH_COLUMN)->first();

        return $last_balance->balance ? : 0;

    }

    /**
     * use to debug your application
     */
    protected function getSessionVariables()
    {
        echo '<pre><p><strong>Session Variables</strong><br />' . print_r(['session_vars' => $_SESSION], TRUE) . '</pre>';
    }

    /**
     *
     * @param array $data
     */
    protected function printData($data)
    {
    	echo '<br /><div class="alert alert-danger" role="alert"><pre>' . print_r($data, 1) . '</pre></div>';
    }

    /**
     */
    protected function getRequiredFiles()
    {
        echo '<pre><p><strong>Required Files</strong><br />' . print_r(get_required_files(), 1) . '</pre>';
    }
}

