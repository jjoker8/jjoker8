<?php
/*
 * Project: MVC
 * File: /app/core/Validator.php
 * Purpose: abstract class from which models extend.
 * Author: Joshua Isooba <joshua@sysdesign.de>
 */
class Token
{
    public static function generate()
    {
        return md5(uniqid());
    }

    /**
     * CSRF Protection - used to generate  a unique Token that only
     * that page knows, so another user else where cannot redirect to
     * that page, because the token will always be checked.
     * The oken is generated inside of the login form in a hidden field.
     */
    public static function generateRandomToken($length = 32)
    {
        if (!isset($length) || intval($length) <= 8) {
            $length = 32;
        }

        // requires PHP 7
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        } else {
            // uses PHP 5 and above
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
    
    // checks if token exists in the session
    // if token equals the session in question
    public static function check($token)
    {
    	//$token
    }
}
