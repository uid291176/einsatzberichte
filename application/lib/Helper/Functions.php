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
	
	/**
	 * erzeugt aus einem Datums-Zeitangabe einen UNIX Timestamp (int)
	 * @param string $strDateTime
	 * format YYYY-MM-DD H:i:s
	 * @return int timestamp
	 */
	public function mkTimeStamp($strDateTime)
	{
	    $arrDateTime   = explode(' ', $strDateTime);
	    $arrDate       = explode('-', $arrDateTime[0]);
	    $arrTime       = explode(':', $arrDateTime[1]);
	    
	    return mktime(intval($arrTime[0]), intval($arrTime[1]), intval($arrTime[2]), intval($arrDate[1]), intval($arrDate[2]), intval($arrDate[0]));
	}
	
	/**
	 * @param string $strEw -> Ostwert
	 * @param string $strNw -> Nordwert
	 * @param string $strZone -> default 32U
	 * @return array 
	 */
	public function utm2geo($strEw, $strNw, $strZone = '32U')
	{
	    /* Copyright (c) 2006, HELMUT H. HEIMEIER
	     Permission is hereby granted, free of charge, to any person obtaining a
	     copy of this software and associated documentation files (the "Software"),
	     to deal in the Software without restriction, including without limitation
	     the rights to use, copy, modify, merge, publish, distribute, sublicense,
	     and/or sell copies of the Software, and to permit persons to whom the
	     Software is furnished to do so, subject to the following conditions:
	     The above copyright notice and this permission notice shall be included
	     in all copies or substantial portions of the Software.*/
	    
	    /* Die Funktion wandelt UTM Koordinaten in geographische Koordinaten
	     um. UTM Zone, Ostwert ew und Nordwert nw müssen gegeben sein.
	     Berechnet werden geographische Länge lw und Breite bw im WGS84 Datum.*/
	     
	     global $rw, $hw, $lp, $bp, $lw, $bw, $zone, $ew, $nw, $raster, $ew2, $nw2;
	     
	     // Längenzone zone, Ostwert ew und Nordwert nw im WGS84 Datum
	     if ($zone == "" || $ew == "" || $nw == "")
	     {
	       $zone = $strZone;
	       $ew = $strEw;
	       $nw = $strNw;
	       return;
	     }
	     $band = substr($zone,2,1);
	     $z1 = intval($zone);
	     $ew1 = intval($ew);
	     $nw1 = intval($nw);
	     
	     //WGS 84 Datum
	     //Große Halbachse a und Abplattung f
	     $a = 6378137.000;
	     $f = 3.35281068e-3;
	     
	     //Polkrümmungshalbmesser c
	     $c = $a/(1-$f);
	     
	     //Quadrat der zweiten numerischen Exzentrizität
	     $ex2 = (2*$f-$f*$f)/((1-$f)*(1-$f));
	     $ex4 = $ex2*$ex2;
	     $ex6 = $ex4*$ex2;
	     $ex8 = $ex4*$ex4;
	     
	     //Koeffizienten zur Berechnung der geographischen Breite aus gegebener
	     //Meridianbogenlänge
	     $e0 = $c*(pi()/180)*(1 - 3*$ex2/4 + 45*$ex4/64 - 175*$ex6/256 + 11025*$ex8/16384);
	     $f2 =    (180/pi())*(    3*$ex2/8 - 3*$ex4/16  + 213*$ex6/2048 -  255*$ex8/4096);
	     $f4 =               (180/pi())*(   21*$ex4/256 -  21*$ex6/256  +  533*$ex8/8192);
	     $f6 =                             (180/pi())*(   151*$ex6/6144 -  453*$ex8/12288);
	     
	     //Entscheidung Nord-/Süd Halbkugel
	     if ($band >= "N"|| $band == "")
	     $m_nw = $nw1;
	     else
	     $m_nw = $nw1 - 10e6;
	     
	     //Geographische Breite bf zur Meridianbogenlänge gf = m_nw
	     $sigma = ($m_nw/0.9996)/$e0;
	     $sigmr = $sigma*pi()/180;
	     $bf = $sigma + $f2*sin(2*$sigmr) + $f4*sin(4*$sigmr) + $f6*sin(6*$sigmr);
	     
	     //Breite bf in Radianten
	     $br = $bf * pi()/180;
	     $tan1 = tan($br);
	     $tan2 = $tan1*$tan1;
	     $tan4 = $tan2*$tan2;
	     
	     $cos1 = cos($br);
	     $cos2 = $cos1*$cos1;
	     
	     $etasq = $ex2*$cos2;
	     
	     //Querkrümmungshalbmesser nd
	     $nd = $c/sqrt(1 + $etasq);
	     $nd2 = $nd*$nd;
	     $nd4 = $nd2*$nd2;
	     $nd6 = $nd4*$nd2;
	     $nd3 = $nd2*$nd;
	     $nd5 = $nd4*$nd;
	     
	     //Längendifferenz dl zum Bezugsmeridian lh
	     $lh = ($z1 - 30)*6 - 3;
	     $dy = ($ew1-500000)/0.9996;
	     $dy2 = $dy*$dy;
	     $dy4 = $dy2*$dy2;
	     $dy3 = $dy2*$dy;
	     $dy5 = $dy2*$dy3;
	     $dy6 = $dy3*$dy3;
	     
	     $b2 = - $tan1*(1+$etasq)/(2*$nd2);
	     $b4 =   $tan1*(5+3*$tan2+6*$etasq*(1-$tan2))/(24*$nd4);
	     $b6 = - $tan1*(61+90*$tan2+45*$tan4)/(720*$nd6);
	     
	     $l1 =   1/($nd*$cos1);
	     $l3 = - (1+2*$tan2+$etasq)/(6*$nd3*$cos1);
	     $l5 =   (5+28*$tan2+24*$tan4)/(120*$nd5*$cos1);
	     
	     //Geographische Breite bw und Länge lw als Funktion von Ostwert ew
	     // und Nordwert nw
	    
	     
	     $arrReturn = array(
	         
	         'bw' => $bf + (180/pi()) * ($b2*$dy2 + $b4*$dy4 + $b6*$dy6),
	         'lw' => $lh + (180/pi()) * ($l1*$dy  + $l3*$dy3 + $l5*$dy5)
	     );
	     return $arrReturn;
	     
	} // public function utm2geo($strEw, $strNw, $strZone = '32U')
	
	
	/**
	 * berechnet die Entferung zwischen zwei gegeben Koordinaten (WGS84 dec)
	 * @param float $lat1
	 * @param float $lng1
	 * @param float $lat2
	 * @param float $lng2
	 * @param boolean $miles
	 * @return float
	 */
	public function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
	{
	    $pi80 = M_PI / 180;
	    $lat1 *= $pi80;
	    $lng1 *= $pi80;
	    $lat2 *= $pi80;
	    $lng2 *= $pi80;
	    
	    $r = 6372.797; // mean radius of Earth in km
	    $dlat = $lat2 - $lat1;
	    $dlng = $lng2 - $lng1;
	    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	    $km = $r * $c;
	    
	    return ($miles ? ($km * 0.621371192) : $km);
	    
	} // public function distance($lat1, $lng1, $lat2, $lng2, $miles = true)

}	
?>