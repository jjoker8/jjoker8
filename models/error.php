<?php
/* 
 * Project: MVC
 * File: /app/models/error.php
 * Purpose: model for the error controller.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class ErrorModel extends BaseModel
{    
    //data passed to the bad URL error view
    public function badURL()
    {
        $this->viewModel->set("pageTitle","Error - Bad URL");
        return $this->viewModel;
    }
}
