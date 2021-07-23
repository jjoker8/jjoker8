<?php
/*
 * Project: MVC
 * File: /app/models/entry.php
 * Purpose: model for the entry controller.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class EntryModel extends BaseModel
{

	public function index()
	{
    	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dateRange'])) {
    		$this->data_entry = $this->findByDate([$_POST['from'], $_POST['to']]);
    	}
    	return $this->IsNewEntry() ? : $this->viewModel;
    }

    /**
     *
     */
    public function jsonEntry(){
    	echo json_encode($this->data_entry, 64);
    }

    public function jedit()
    {
    	$this->data_entry= $this->conn->findById($this->getTableName(), $_GET['id']);
    	echo json_encode($this->data_entry, 64);
    }

    /**
     * lists available bank accounts
     */
    public function selectBankAccounts(){
    	echo json_encode($this->bank_accounts);
    }

    /**
     *
     */
    public function selectTags() {
    	echo json_encode($this->tags);
    }

    public function selectPositionDropdown() {
    	echo json_encode($this->position);
    }
 }



