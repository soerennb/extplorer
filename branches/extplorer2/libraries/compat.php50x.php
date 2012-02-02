<?php
/**
 * PHP 5.0.x Compatibility functions
 */

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

if (!defined('FILE_USE_INCLUDE_PATH')) {
	define('FILE_USE_INCLUDE_PATH', 1);
}

if (!defined('FILE_APPEND')) {
	define('FILE_APPEND', 8);
}

/**
 * Replace file_put_contents()
 *
 * @category	PHP
 * @package	 PHP_Compat
 * @link		http://php.net/function.file_put_contents
 * @author	  Aidan Lister <aidan@php.net>
 * @version	 $Revision$
 * @internal	resource_context is not supported
 * @since	   PHP 5
 * @require	 PHP 4.0.1 (trigger_error)
 */
if (!function_exists('file_put_contents')) {
	function file_put_contents($filename, $content, $flags = null, $resource_context = null)
	{
		// If $content is an array, convert it to a string
		if (is_array($content)) {
			$content = implode('', $content);
		}

		// If we don't have a string, throw an error
		if (!is_scalar($content)) {
			trigger_error('file_put_contents() The 2nd parameter should be either a string or an array', E_USER_WARNING);
			return false;
		}

		// Get the length of date to write
		$length = strlen($content);

		// Check what mode we are using
		$mode = ($flags & FILE_APPEND) ?
					$mode = 'a' :
					$mode = 'w';

		// Check if we're using the include path
		$use_inc_path = ($flags & FILE_USE_INCLUDE_PATH) ?
					true :
					false;

		// Open the file for writing
		if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
			trigger_error('file_put_contents() failed to open stream: Permission denied', E_USER_WARNING);
			return false;
		}

		// Write to the file
		$bytes = 0;
		if (($bytes = @fwrite($fh, $content)) === false) {
			$errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',
							$length,
							$filename);
			trigger_error($errormsg, E_USER_WARNING);
			return false;
		}

		// Close the handle
		@fclose($fh);

		// Check all the data was written
		if ($bytes != $length) {
			$errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
							$bytes,
							$length);
			trigger_error($errormsg, E_USER_WARNING);
			return false;
		}

		// Return length
		return $bytes;
	}
}

/**
 * Replace stripos()
 *
 * @category	PHP
 * @package		PHP_Compat
 * @link		http://php.net/function.stripos
 * @author		Aidan Lister <aidan@php.net>
 * @version		$Revision$
 * @since		PHP 5
 * @require		PHP 4.0.0 (user_error)
 */
if (!function_exists('stripos')) {
	function stripos($haystack, $needle, $offset = null)
	{
		if (!is_scalar($haystack)) {
			user_error('stripos() expects parameter 1 to be string, ' .
				gettype($haystack) . ' given', E_USER_WARNING);
			return false;
		}

		if (!is_scalar($needle)) {
			user_error('stripos() needle is not a string or an integer.', E_USER_WARNING);
			return false;
		}

		if (!is_int($offset) && !is_bool($offset) && !is_null($offset)) {
			user_error('stripos() expects parameter 3 to be long, ' .
				gettype($offset) . ' given', E_USER_WARNING);
			return false;
		}

		// Manipulate the string if there is an offset
		$fix = 0;
		if (!is_null($offset)) {
			if ($offset > 0) {
				$haystack = substr($haystack, $offset, strlen($haystack) - $offset);
				$fix = $offset;
			}
		}

		$segments = explode(strtolower($needle), strtolower($haystack), 2);

		// Check there was a match
		if (count($segments) === 1) {
			return false;
		}

		$position = strlen($segments[0]) + $fix;
		return $position;
	}
}

/**
 * Ported PHP5 function to PHP4 for forward compatibility
 */
function clone($object) {
      return $object;
}
?>