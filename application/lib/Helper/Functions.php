<?php 
/**
 * Helper Class for wms::framework
 *
 * @package    wms::framework
 * @copyright  Copyright (c) 2012, David Grimmer <david_grimmer@t-online.de>
 * @license    
 ***************************************************************************/

class Helper_Functions
{
	private static $instance = null;
	
	protected function __construct() {}
	
	private function __clone() {}
	
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Gibt die URL für eine interne Verlinkung zurück
	 * @param string $modul
	 * @param string $controller
	 * @param string $action
	 * @return string Fertige URL
	 */
	public function buildUrl($controller, $action, $arParam = array())
	{
			
		$arParamRequest = array();
		if ($this->isSizedString($controller)) $arParamRequest['k'] = $controller;
		if ($this->isSizedString($action)) $arParamRequest['action'] = $action;
			
		foreach ($arParam as $k => $v)
		{
			$arParamRequest[$k] = $v;
		}
			
		return '/index.php?'.http_build_query($arParamRequest);
			
	} // public static function buildUrl($modul, $controller, $action)
	
	/**
	 * standard debug function
	 * @param $value mixed
	 */
	public function debug($value)
	{

		if (isset($_COOKIE['enable_debug']) && $_COOKIE['enable_debug'] == '1')
		{
			if (is_array($value))
			{
				echo '<pre style="color:red;">';
				print_r($value);
				echo '</pre>';
			}
			else
			{
				echo '<pre style="color:red;">'.$value.'</pre>';
			}
		}
	}
	
	/**
	 * Wandelt Sonderzeichen in HTML-Codes um
	 * alias zu htmlspecialchars
	 * @param $string
	 */
	public function hspc($string)
	{
		return htmlspecialchars($string);
	}
	
	/**
	 * Prüfung ob befülltes Array vorhanden ist
	 * @param 	array	$array
	 * @return 	boolean
	 */
	public function isSizedArray($array)
	{
		if(is_array($array) && sizeof($array) > 0)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Prüfung ob String mit Laenge vorliegt
	 * @param 	array	$array
	 * @return 	boolean
	 */
	public function isSizedString($string, $size = 0, $equals = false)
	{
		if ($equals === true && is_string($string) && $string != '' && strlen($string) == $size)
		{
			return true;	
		}
		else if (is_string($string) && $string != '' && strlen($string) > $size)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * @param	array	Debugdaten
	 * @param	string	Adminmail (Mehrere müssen kommasepariert sein)
	 * @return	boolean
	 */
	public function debug_mail($data, $admin_mail = false)
	{
		
		if ($admin_mail == '') $admin_mail = 'david.grimmer@webmedienservice.de';
		
		$headers =  'MIME-Version: 1.0' . "\r\n"; 
		$headers .= 'From: einsatzberichte.local<info@phpdebug.local>' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n"; 
		
		if(is_string($admin_mail) && $admin_mail != '')
		{	
			if(preg_match('/,/', $admin_mail))
			{
				$admin_mails = explode(',', $admin_mail);
				
				foreach($admin_mails as $key=>$email)
				{
					$result = mail($email, 'WMS Framework debug_mail', print_r($data, true), $headers);		
				}
			}
			else
			{
				$result = mail($admin_mail, 'WMS Framework debug_mail', print_r($data, true), $headers);	
			}
			return $result;
		}
		return '';
	}
	
	/**
	 * Substitution der Mysql-Escape Funktion
	 * @param	string
	 * @return 	string
	 */
	public function q($value)
	{
 		return(addslashes($value)); 
	}
	
	/**
	 * gibt den aktuellen Wochentag zurück
	 */
	public function getDayOfWeek()
	{
		$week = array(
			'1' => 'Montag',
			'2' => 'Dienstag',
			'3' => 'Mittwoch',
			'4' => 'Donnerstag',
			'5' => 'Freitag',
			'6' => 'Samstag',
			'7' => 'Sontag'
		);
		
		return $week[date('N')];
	}
	
	/**
	 * @param string $strTime FORMAT dd.mm.yyyy hh:mm:ss
	 * @return string d-m-Y H:i:s
	 */
	public function toDBtime($strTime)
	{
		return str_replace('.', '-', $strTime);
	}
	
	public function dot2komma($string)
	{
		return str_replace('.', ',', $string);
	}

}	
?>