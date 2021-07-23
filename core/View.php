<?php
/*
 * Project: MVC
 * File: /app/core/View.php
 * Purpose: class for the view object.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */

class View {

    protected $viewFile;

    /**
     *
     * @param class $controllerClass
     * @param function $action
     * establishes view location on object creation
     */
    public function __construct($controllerClass, $action) {
        $controllerName = str_replace("Controller", "", $controllerClass);
        $this->viewFile = VIEWS . DS . $controllerName . DS . $action . '.php';
    }

    //output the view
    public function output($viewModel, $template = 'maintemplate') {

        $templateFile = VIEWS . DS. $template.'.php';

        if (file_exists($this->viewFile)) {
            if ($template) {
                //include the full template
                if (file_exists($templateFile)) {
                    require($templateFile);
                } else {
                    require(VIEWS . DS . 'error' . DS . 'badtemplate.php');
                }
            } else {
                //we're not using a template view so just output the method's view directly
                require($this->viewFile);
            }
        } else {
        	require(VIEWS . DS . 'error' . DS . 'badview.php');
        }

    }
}

