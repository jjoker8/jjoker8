<?php
/*
 * Project: MVC
 * File: /app/models/config.php
 * Purpose: Application setup.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class ConfigModel  extends BaseModel {
	public function index(){
		$pageTitle = 'Application '. str_replace("Model", "urator", get_class($this));

		$this->viewModel->set('pageTitle', $pageTitle);

		return $this->viewModel;
	}
}

