<?php
/**
 * Project: MVC
 * File: /app/controllers/entry.php
 * Purpose: controller for the home of the app.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class EntryController extends BaseController
{
    //add to the parent constructor
    public function __construct($action, $urlValues) {
        parent::__construct($action, $urlValues);

        //create the model object
        require(MODELS . DS . 'entry.php');
        $this->model = new EntryModel();
    }

    //default method
    protected function index()
    {
        #$this->view->output($this->model->index());
        $this->view->output($this->model->index(), 'jumbotron');
    }

    protected function secutity()
    {
    	$this->model->security();
    }

    //default method
    protected function jsonEntry()
    {
    	$this->model->jsonEntry();
    }

    protected function jedit() {
    	$this->model->jedit();
    }

    protected function selectBankAccounts() {
    	$this->model->selectBankAccounts();
    }

    protected function selectTags() {
        $this->model->selectTags();
    }

    protected function selectPositionDropdown() {
    	$this->model->selectPositionDropdown();
    }
}

