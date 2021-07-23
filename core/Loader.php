<?php
/*
 * Project: MVC
 * File: /app/core/Loader.php
 * Purpose: class which maps URL requests to controller object creation
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class Loader
{

    private $controllerName;
    private $controllerClass;
    private $action;
    private $urlValues;

    //store the URL request values on object creation
    public function __construct()
    {
        $this->urlValues = $_GET;

        if ($this->urlValues['controller'] == "") {
            $this->controllerName  = "home";
            $this->controllerClass = "HomeController";
        } else {
            //$this->IsAuthenticated();
            $this->controllerName  = strtolower($this->urlValues['controller']);
            $this->controllerClass = ucfirst(strtolower($this->urlValues['controller'])) . "Controller";
        }

        if ($this->urlValues['action'] == "") {
            $this->action = "index";
        } else {
            //$this->IsAuthenticated();
            $this->action = $this->urlValues['action'];
        }
    }

    // Redirect user to login form, if they are not authentication
    public function IsAuthenticated()
    {
        if (!isset($_SESSION['authenticated'])) {
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['PHP_SELF']), "/\\");
            header("Location: https://$host$path");
            exit;
        }
    }

    //factory method which establishes the requested controller as an object
    public function createController()
    {
        //check our requested controller's class file exists and require it if so
        if (file_exists(CONTROLLERS . DS  . $this->controllerName . '.php')) {
            require CONTROLLERS . DS . $this->controllerName . '.php';
        } else {
        	require CONTROLLERS . DS .'error.php';
            return new ErrorController("badurl", $this->urlValues);
        }

        //does the class exist?
        if (class_exists($this->controllerClass)) {
            $parents = class_parents($this->controllerClass);

            //does the class inherit from the BaseController class?
            if (in_array("BaseController", $parents)) {
                //does the requested class contain the requested action as a method?
                if (method_exists($this->controllerClass, $this->action)) {
                    return new $this->controllerClass($this->action, $this->urlValues);
                } else {
                    //bad action/method error
                	require CONTROLLERS . DS .'error.php';
                    return new ErrorController("badurl", $this->urlValues);
                }
            } else {
                //bad controller error
            	require CONTROLLERS . DS .'error.php';
                return new ErrorController("badurl", $this->urlValues);
            }
        } else {
            //bad controller error
        	require CONTROLLERS . DS .'error.php';
            return new ErrorController("badurl", $this->urlValues);
        }
    }
}
