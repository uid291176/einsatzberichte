<?php 
/**
 * 
 * Erzeugt ein Berichts- Objekt 
 * @author David
 */
class Einsatz_Bericht
{
	
	/**
	 * ID des Einsatzes
	 * @var string
	 */
	public $_eid = null;
	
	/**
	 * enthällt die Daten des Berichtes
	 * @var array
	 */
	public $_data = array();
	
	/**
	 * @var mysql object
	 */
	public $_db = null;
	
	/**
	 * Objektinstanz
	 * @var EinsatzObjekt
	 */
	private static $instance;
	
	/**
	 * enthält die Klasse für Hilfsfunktionen und debug
	 * @var object
	 */
	public $tools = null;
	
	/**
	 * constructor
	 * @param int $eID
	 */
	public function __construct($eID, $mysqlDB)
	{
		
		$this->_eid = $eID;
		$this->_db = $mysqlDB;
		
		$this->tools = Helper_Functions::getInstance();
		
	}
	
	/**
	 * läd die Daten des Einsatzes aus der DB (MySQL)
	 * erzeugt ein Array ('key' => 'value')
	 */
	public function init()
	{
		
	    $arrStammdaten = $this->_db->fetchRow("SELECT * FROM `".TBL_EINSAETZE."` WHERE `eid` = '".$this->_eid."'");
		
		$arrEavRaw = $this->_db->fetchAssoc("SELECT `attribut`, `value` FROM `".TBL_EINSAETZE_EAV."` WHERE `eid` = '".$this->_eid."'");
		

		if($this->tools->isSizedArray($arrEavRaw))
		{
		    foreach ($arrEavRaw as $data)
		    {
		        $arrEAV[$data['attribut']] = $data['value'];
		    }
		    
		    $this->_data = array_merge($arrStammdaten, $arrEAV);
		}
		else 
		{
		    
		    $this->_data = $arrStammdaten;
		    
		}
	
		
	} // public function init()
	
	
	/**
	 * get funktion für die Daten des Berichtes
	 */
	public function get($key)
	{
		if (array_key_exists($key, $this->_data)) return $this->_data[$key];
		else return '';
				
	} // public function get($key)
	
	public function set($key, $value)
	{
	    
	    $arrUpdate = array('attribut' => $key, 'value'    => $value);
	    
	} // public function set($key, $value)
	
	private function __clone() {}
	
	/**
	 * statische Methode zur Rückgabe der Objektinstanz
	 * @param	array	Konfigurationsdaten
	 * @return 	object Einsatz
	 */
	public static function geInstance($eID, $db)
	{
	
		if(self::$instance === null)
		{
			self::$instance = new self($eID, $db);
		}
		return self::$instance;
	}

	
	/**
	 * aktualisiert die Berichtsdaten
	 * @param string $strKey
	 * @param string $value
	 */
	public function saveEAV2db($strKey, $value)
	{
		
	    if ($this->_db->fetchOne("SELECT `attribut` FROM `".TBL_EINSAETZE_EAV."` WHERE `attribut` = '".$strKey."' AND `eid` = '".$this->_eid."'"))
		{
			$arrUpdate = array(
			    'attribut' => $strKey,
			    'value' => $value
			);
			$this->_db->UpdateQuery(TBL_EINSAETZE_EAV, $arrUpdate, "`attribut` = '".$strKey."' AND `eid` = '".$this->_eid."'");
		}
		else 
		{
			$arrImport = array(
			    'attribut' => $strKey,
			    'value' => $value,
				'eid' => $this->_eid,
				'enr' => $this->_data['enr']
			);
			$this->_db->ImportQuery(TBL_EINSAETZE_EAV, $arrImport);
		}
		
		return;
	
	} //public function saveEAV2db($strKey, $value)
	
	
	/**
	 * sichert die Ressourcen (Fahrzeuge) des Einsatzes
	 * @param array $arrData 
	 */
	public function saveRessources($arrData)
	{
	    
	    
		$arrdbData 	= array();
		$arrImport 	= array();
		$arrUpdate 	= array();
		
		/**
		 * sichern der ressourcen in eigener Tabelle
		 */
		foreach ($arrData as $index => $r)
		{
				
			if ($index === '###index###') continue;
			
			$arrdbData = array(
			        'eid' 		=> $this->_eid,
			        'enr' 		=> $this->_data['enr'],
					'f_kenner' 	=> $this->tools->q($r['funkkenner']),
					'f3' 		=> ($this->tools->isSizedString($r['uebernommen_date']))? strtotime($this->tools->toDBtime($r['uebernommen_date']).' '.$this->tools->q($r['uebernommen_time'])):0,
					'f4' 		=> ($this->tools->isSizedString($r['ankunft_date']))? strtotime($this->tools->toDBtime($r['ankunft_date']).' '.$this->tools->q($r['ankunft_time'])):0,
					'f1' 		=> ($this->tools->isSizedString($r['frei_ueber_funk_date']))? strtotime($this->tools->toDBtime($r['frei_ueber_funk_date']).' '.$this->tools->q($r['frei_ueber_funk_time'])):0,
					'f2' 		=> ($this->tools->isSizedString($r['rueckkehr_date']))? strtotime($this->tools->toDBtime($r['rueckkehr_date']).' '.$this->tools->q($r['rueckkehr_time'])):0
			);
			
			$arrSelect = $this->_db->fetchRow("SELECT `id`, `f_kenner`, `f3` FROM `".TBL_EINSAETZE_RESSOURCEN."` WHERE `eid` = '".$this->_eid."' AND `f_kenner` = '".$r['funkkenner']."'");
			
			if ($arrSelect['f_kenner'] == $arrdbData['f_kenner'])
			{
			    
			    $this->_db->UpdateQuery(TBL_EINSAETZE_RESSOURCEN, $arrdbData, "`id` = '". $arrSelect['id']."'");
			}
			else
			{
			    // Fahrzeug neu anlegen
				$arrImport = $arrdbData;
				
				$this->_db->ImportQuery(TBL_EINSAETZE_RESSOURCEN, $arrImport);
				
				// Peronaleintrag dazu erzeugen 
				$arrImportPers = array(
				    'eid'       => $arrImport['eid'],
				    'enr'       => $arrImport['enr'],
				    'f_kenner'  => $arrImport['f_kenner'],
				    'e_zeit'    => (intval($arrImport['f2']) - intval($arrImport['f3']) / 60),
				    
				    'aus_h'     => '0',
				    'aus_g'     => '0',
				    'aus_m'     => '0',
				    'aus_ff'    => '0',
				    
				    'eingesetzt_h'  => '0',
				    'eingesetzt_g'  => '0',
				    'eingesetzt_m'  => '0',
				    'eingesetzt_ff' => '0'
				    
				);
				$this->_db->ImportQuery(TBL_EINSAETZE_PERSONAL, $arrImportPers);
			}
				
		}
		
		return;
		
	} // public function saveRessources($arrData)
	
	/**
	 * sichert das Persanl des Einsatzes
	 * @param array $arrData
	 */
	public function savePersonal($arrData)
	{
		
		$arrdbData 	= array();
		$arrImport 	= array();
		$arrUpdate 	= array();
		
		/**
		 * sichern des einsatzpersonals in eigener Tabelle
		 */
		foreach ($arrData as $index => $p)
		{
				
			if ($index === '###index###') continue;
				
			$arrdbData = array(
    			    'eid' 		=> $this->_eid,
    			    'enr' 		=> $this->_data['enr'],
					'f_kenner' 			=> $this->tools->q($p['funkkenner']),
					'e_zeit'			=> intval($p['einsatzzeit_day']) * 24 * 60 + intval($p['einsatzzeit_std']) * 60 + intval($p['einsatzzeit_min']),
					'aus_h'				=> ($this->tools->isSizedString($p['ausgerueckt_hoeherer']))? intval($this->tools->q($p['ausgerueckt_hoeherer'])):0,
					'aus_g'				=> ($this->tools->isSizedString($p['ausgerueckt_gehobener']))? intval($this->tools->q($p['ausgerueckt_gehobener'])):0,
					'aus_m'				=> ($this->tools->isSizedString($p['ausgerueckt_mittlerer']))? intval($this->tools->q($p['ausgerueckt_mittlerer'])):0,
					'aus_ff'			=> ($this->tools->isSizedString($p['ausgerueckt_ff']))? intval($this->tools->q($p['ausgerueckt_ff'])):0,
					'eingesetzt_h'		=> ($this->tools->isSizedString($p['eingesetzt_hoeherer']))? intval($this->tools->q($p['eingesetzt_hoeherer'])):0,
					'eingesetzt_g'		=> ($this->tools->isSizedString($p['eingesetzt_gehobener']))? intval($this->tools->q($p['eingesetzt_gehobener'])):0,
					'eingesetzt_m'		=> ($this->tools->isSizedString($p['eingesetzt_mittlerer']))? intval($this->tools->q($p['eingesetzt_mittlerer'])):0,
					'eingesetzt_ff'		=> ($this->tools->isSizedString($p['eingesetzt_ff']))? intval($this->tools->q($p['eingesetzt_ff'])):0,	
			);
				
			if ($this->_db->fetchOne("SELECT `f_kenner` FROM `".TBL_EINSAETZE_PERSONAL."` WHERE `eid` = '".$this->_eid."' AND `f_kenner` = '".$p['funkkenner']."'"))
			{
		
				$arrUpdate = $arrdbData;
				
				$this->_db->UpdateQuery(TBL_EINSAETZE_PERSONAL, $arrUpdate, "`eid` = '".$this->_eid."' AND `f_kenner` = '".$p['funkkenner']."'");
			}
			else
			{
			    
			    die('darf nie vorkommen!!! model->Einsatz->Bericht->savePersonal::Import');
				$arrImport = $arrdbData;
				 
				$this->_db->ImportQuery(TBL_EINSAETZE_PERSONAL, $arrImport);
			}
				
		}

		return;
		
	} // public function savePersonal($arrData)
	
	public function saveProtokoll($strData, $strUserName)
	{
	    $strProtokoll = $strData;
	    
	    if ($this->_db->fetchOne("SELECT `id` FROM `".TBL_EINSAETZE_PROTOKOLL."` WHERE `eid` = '".$this->_eid."'"))
	    {
	        $arrUpdate = array(
	            'inhalt' => $strProtokoll,
	            'ersteller' => $strUserName
	        );
	        $this->_db->UpdateQuery(TBL_EINSAETZE_PROTOKOLL, $arrUpdate, "`eid` = '".$this->_eid."'");
	    }
	    else
	    {
	        $arrImport = array(
	            'eid' => $this->_eid,
	            'enr' => $this->_data['enr'],
	            'inhalt' => $strProtokoll,
	            'ersteller' => $strUserName
	        );
	        $this->_db->ImportQuery(TBL_EINSAETZE_PROTOKOLL, $arrImport);
	    }
	    
	    return;
	    
	}
	
	public function getRessources()
	{
		$arrReturn = $this->_db->FetchAssoc("SELECT * FROM `".TBL_EINSAETZE_RESSOURCEN."` WHERE `eid` = '".$this->_eid."'");
		
		return $arrReturn;
	}
	
	public function getPersonal()
	{
		$arrReturn = $this->_db->FetchAssoc("SELECT * FROM `".TBL_EINSAETZE_PERSONAL."` WHERE `eid` = '".$this->_eid."'");
		
		return $arrReturn;
	}
	
	public function getProtokoll()
	{
	    
	    $arrProtokoll = $this->_db->FetchRow("SELECT * FROM `".TBL_EINSAETZE_PROTOKOLL."` WHERE `eid` = '".$this->_eid."'");

	    return $arrProtokoll['inhalt'];
	}
	
	public function getEinsatzZeit($aus, $ende)
	{

		if ($aus < 1) return array('sek' => 0, 'min' => 0, 'std' => 0, 'tag' => 0);
		if ($ende < 1) return array('sek' => 0, 'min' => 0, 'std' => 0, 'tag' => 0);
		
		$timeDiff = intval($ende - $aus);

		if ($timeDiff > 0)
		{
			$tag  = floor($timeDiff / (3600 * 24));
			$std  = floor($timeDiff / 3600 % 24);
			$min  = floor($timeDiff / 60 % 60);
			$sek  = floor($timeDiff % 60);
			
			return array('sek' => $sek, 'min' => $min, 'std' => $std, 'tag' => $tag);
		}
		
	}
	
}
?>
