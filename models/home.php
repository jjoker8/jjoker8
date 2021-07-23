<?php
/*
 * Project: MVC
 * File: /app/models/home.php
 * Purpose: model for the home controller.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class HomeModel extends BaseModel
{
    //data passed to the home index view
    public function index()
    {
    	$this->viewModel->set("pageTitle", $this->viewModel->get("pageTitle"). "-Banking");

        return $this->viewModel;
    }

    public function security()
    {
    	$this->viewModel->set('pageTitle', 'Login');
    	$this->table_name = 'users';
    	return $this->viewModel;
    }
}
