<?php
/*
 * Project: MVC
 * File: /app/core/ViewModel.php
 * Purpose: class for the optional data object returned by model methods which the controller sends to the view.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class ViewModel {

    //dynamically adds a property or method to the ViewModel instance
    public function set($name,$val) {
        $this->$name = $val;
    }

    //returns the requested property value
    public function get($name) {
        if (isset($this->{$name})) {
            return $this->{$name};
        } else {
            return null;
        }
    }
/*
    public function unset($name) {
    	if (isset($this->{$name})) {
    		unset($this->{$name});
    	}
    }
    */
}

