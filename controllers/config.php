<?php
/**
 * Project: MVC
 * File: /app/controllers/config.php
 * Purpose: controller for the config of the app.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class ConfigController extends BaseController
{
    //add to the parent constructor
    public function __construct($action, $urlValues) {
        parent::__construct($action, $urlValues);

        //create the model object
        require(MODELS . DS . 'config.php');
        $this->model = new ConfigModel();
    }

    //default method
    protected function index()
    {
    	// use /app/views/Config/index.php
        //$this->view->output($this->model->index(), null);

    	// use default template
    	$this->view->output($this->model->index());
    }

    protected function createDb(){

    }
}

