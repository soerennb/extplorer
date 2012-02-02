<?php
/** ensure this file is being included by a parent file */
if (!defined('_JEXEC') && !defined('_VALID_MOS')) die('Restricted access');
/**
 * This file allows to dynamically switch between file.system based mode and FTP based mode
 */
class ext_TextEncoding
{
	static function changeCharset($inputCharset, $outputCharset, $text)
	{
	    if ($inputCharset == $outputCharset) return $text;
		// Due to iconv bug when dealing with text with non ASCII encoding for last char, we use this workaround http://fr.php.net/manual/fr/function.iconv.php#81494
		if(function_exists("iconv"))
		{
		    
			return iconv($inputCharset, $outputCharset, $text);
		}else
		{
			$content = @htmlentities($text, ENT_QUOTES, $inputCharset);  
			return @html_entity_decode($content, ENT_QUOTES , $outputCharset);
		}
	}
	
	static function parseCharset($locale)
	{
		$test = explode("@", $locale);
		$locale = $test[0];		
		$encoding = substr(strrchr($locale, "."), 1);
		if (is_numeric($encoding))
		{
		    if (substr($encoding, 0, 2) == "12") // CP12xx are changed to Windows-12xx to allow PHP4 conversion
  			    $encoding = "windows-".$encoding;
  			else $encoding = "CP".$encoding; // In other cases, PHP4 won't work anyway, so use CPxxxx encoding (that iconv supports)
		} else if ($locale == "C")
		{   // Locale not set correctly, most probable error cause is /etc/init.d/apache having "LANG=C" defined
			// In any case, "C" is ASCII-7 bit so it's safe to use the extra bit as if it was UTF-8 
			$encoding = "UTF-8";
		}
		if (!strlen($encoding)) $encoding = "UTF-8";
		return $encoding;
	}
	
	static function getEncoding(){
	       // Get the current locale (expecting the filesystem is in the same locale, as the standard says)
	       return ext_TextEncoding::parseCharset(setlocale(LC_CTYPE, 0));
	}
	
	static function fromUTF8($filesystemElement, $test = false){
		if($test && !ext_TextEncoding::isUtf8($filesystemElement)){
			return $filesystemElement;
		}
		$enc = ext_TextEncoding::getEncoding();
	    return ext_TextEncoding::changeCharset("UTF-8", $enc, $filesystemElement);
	}
  
	/** This function is used when the server's PHP configuration is using magic quote */
    static function magicDequote($text)
    {
	    // If the PHP server enables magic quotes, remove them
	    if (get_magic_quotes_gpc())
	        return stripslashes($text);
	    return $text;  
    }
	                         
    static function fromPostedFileName($filesystemElement)
    {
	    return ext_TextEncoding::fromUTF8(ext_TextEncoding::magicDequote($filesystemElement));
	}
	
	static function toUTF8($filesystemElement, $test = true){
		if($test && ext_TextEncoding::isUtf8($filesystemElement)){
			return $filesystemElement;
		}
		$enc = ext_TextEncoding::getEncoding();
		return ext_TextEncoding::changeCharset($enc, "UTF-8", $filesystemElement);
	}
	
	static function isUtf8($string){
    	return preg_match('%^(?: 
	      [\x09\x0A\x0D\x20-\x7E]            # ASCII 
	    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte 
	    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs 
	    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte 
	    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates 
	    | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3 
	    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15 
	    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16 
			)*$%xs', $string);
    }
}