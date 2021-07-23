<?php

/**
 * File: app/core/Calendar.php
 * @author Joshua Isooba
 *
 */
class Calendar
{
	static function validateMySQLDate($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	/**
	 *
	 * @param string $format
	 * @param DateTime $date
	 * @return string|boolean
	 */
	private static function getWorkingDay($format = 'Y-m-d', $date)
    {
    	$dayOfWeek = date('l', strtotime($date));

    	switch ($dayOfWeek) {
    		case "Saturday":
    			return date($format, strtotime($date. '-1 days'));
    		break;

    		case "Sunday":
    			return date($format, strtotime($date. '-2 days'));
    		break;

    		default:
    			return date($format, strtotime($date));
    		break;
    	}
    	return false;
    }

	/**
	 *
	 * @param DateTime $date
	 * @param string $format
	 * @return boolean|string
	 */
    static function get_first_working_day($date = NULL, $format = 'Y-m-01')
    {
    	// First day of the month.
    	if ($date) {
    		return Calendar::getWorkingDay($format, $date);
    	}
    	return Calendar::getWorkingDay($format, date($format, strtotime('first day of this month')));
    }

    /**
     *
     * @param DateTime $date
     * @param string $format
     * @return boolean|string
     */
    static function get_last_working_day($date = NULL, $format = 'Y-m-d')
    {
    	// Last day of the month.
    	if ($date) {
    		return Calendar::getWorkingDay($format, $date);
    	}
    	return Calendar::getWorkingDay($format, date($format, strtotime('last day of this month')));
    }
}