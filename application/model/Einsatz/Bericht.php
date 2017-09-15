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
	protected $_eid = null;
	
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
		
		foreach ($arrEavRaw as $data)
		{
			$arrEAV[$data['attribut']] = $data['value'];
		}
		
		$this->_data = array_merge($arrStammdaten, $arrEAV);
		
		$this->_data['resourcen'] = $this->getRessources();
		
		$this->_data['personal'] = $this->getPersonal();		
		
	} // public function init()
	
	/**
	 * get funktion für die Daten des Berichtes
	 */
	public function get($key)
	{
		if (array_key_exists($key, $this->_data)) return $this->_data[$key];
		else return _('ERROER::Ein Eintrag mit dem Key "'.$key.'" existiert für diesen Einsatz nicht');
				
	} // public function get($key)
	
	private function __clone() {}
	
	/**
	 * statische Methode zur Rückgabe der Objektinstanz
	 * @param	array	Konfigurationsdaten
	 * @return 	Einsatz
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
	 * sichert die Daten des Berichts in eine MySQL DB
	 */
	public function save()
	{
		
		$requestData 	= $_REQUEST['report'];
		$userID 		= $_REQUEST['user-id'];
		
		if($this->_db->fetchOne("SELECT `eid` FROM `".TBL_EINSAETZE."` WHERE `eid` = '".$this->_eid."'"))
		{
			
			// einen vorhandenen Bericht updaten
			$this->saveData($this->_eid, $requestData);
		}
		else 
		{
			// neuen Bericht in der MySQL DB anlegen
			$this->createReport($userID, $requestData);
		}
		
		// Einsatzmittel (Fahrzeuge) sichern
		$this->saveRessources($this->_eid, $requestData);
			
		// Personal zum Einsatz sichern
		$this->savePersonal($this->_eid, $requestData);
		
		return;
		
	} // public function save()
	
	/**
	 * legt einen neuen Einsatz im Berichtswesen an
	 * @param array $arrData
	 */
	public function createReport($uID, $arrData)
	{
		
		if ($arrData['einsatz_haupt_stichwort'] == 'Brand') $object = 'brandobjekt';
		if ($arrData['einsatz_haupt_stichwort'] == 'Hilfeleistung') $object = 'einsatzobjekt';
		
		$arrImport = array(
			'eid' 				=> $this->_eid,
			'enr' 				=> $this->tools->q($arrData['einsatz_nr']),
			'haupt_stichwort' 	=> $this->tools->q($arrData[$object]),
			'stichwort' 		=> $this->tools->q($arrData[$object.'_e-stelle']),
			'beginn' 			=> strtotime($this->tools->toDBtime($arrData['einsatz_beginn'])),
			'alarm' 			=> strtotime($this->tools->toDBtime($arrData['alarmzeit_datum'].' '.$arrData['alarmzeit_uhrzeit'])),
			'adresse' 			=> $this->tools->q($arrData[$object.'_adresse']),
			'ort' 				=> $this->tools->q($arrData[$object.'_ort']),
			'uid'				=> $uID,
			'status'			=> 'edit',
			'typ' 				=> $this->tools->q($arrData['einsatz_haupt_stichwort']),
		);

		$importID = $this->_db->ImportQuery(TBL_EINSAETZE, $arrImport);
		
		foreach ($arrData as $key => $value)
		{
			if ($this->tools->isSizedArray($value) || $key == 'eid' || $key == 'enr') continue;
			
			$this->_db->ImportQuery(TBL_EINSAETZE_EAV, array('attribut' => $this->tools->q($key), 'value' => $this->tools->q($value), 'eid' => $this->_eid, 'enr' => $this->tools->q($arrData['einsatz_nr'])));;
		}
		
		return $importID;
		
	} // public function createReport($arrData)
	
	/**
	 * aktualisiert die Berichtsdaten
	 * @param varchar (16) $eID
	 * @param array $arrData
	 */
	public function saveData($eID, $arrData)
	{
		
		$arrdbData 	= array();
		$arrImport 	= array();
		$arrUpdate 	= array();

		/**
		 * sichern der Berichtsdaten ohne ressourchen und personal
		 */
		foreach ($arrData as $key => $value)
		{
			// personal u. ressourcen array , eID und eNr. nicht berücksichtigen
			if ($this->tools->isSizedArray($value) || $key == 'eid' || $key == 'enr') continue;
			
			if ($this->_db->fetchOne("SELECT `attribut` FROM `".TBL_EINSAETZE_EAV."` WHERE `attribut` = '".$key."'"))
			{
				$arrUpdate = array(
					'attribut' => $this->tools->q($key),
					'value' => $this->tools->q($value)
				);
				$this->_db->UpdateQuery(TBL_EINSAETZE_EAV, $arrUpdate, "`attribut` = '".$this->tools->q($key)."' AND `eid` = '".$eID."'");
			}
			else 
			{
				$arrImport = array(
					'attribut' => $this->tools->q($key),
					'value' => $this->tools->q($value),
					'eid' => $this->_eid,
					'enr' => $this->tools->q($arrData['einsatz_nr'])
				);
				$this->_db->ImportQuery(TBL_EINSAETZE_EAV, $arrImport);
			}
		}
		
		return;
	
	} // public function saveData($eID, $arrData)
	
	/**
	 * sichert die Ressourcen (Fahrzeuge) des Einsatzes
	 * @param varchar (16) $eID
	 * @param array $arrData 
	 */
	public function saveRessources($eID, $arrData)
	{
		//$this->tools->debug($arrData);
		$arrdbData 	= array();
		$arrImport 	= array();
		$arrUpdate 	= array();
		
		/**
		 * sichern der ressourcen in eigener Tabelle
		 */
		foreach ($arrData['resourcen'] as $index => $r)
		{
				
			if ($index === '###index###') continue;
			
			$arrdbData = array(
					'eid' 		=> $eID,
					'enr' 		=> $this->tools->q($arrData['einsatz_nr']),
					'f_kenner' 	=> $this->tools->q($r['funkkenner']),
					'f3' 		=> ($this->tools->isSizedString($r['uebernommen_date']))? strtotime($this->tools->toDBtime($r['uebernommen_date']).' '.$this->tools->q($r['uebernommen_time'])):0,
					'f4' 		=> ($this->tools->isSizedString($r['ankunft_date']))? strtotime($this->tools->toDBtime($r['ankunft_date']).' '.$this->tools->q($r['ankunft_time'])):0,
					'f1' 		=> ($this->tools->isSizedString($r['frei_ueber_funk_date']))? strtotime($this->tools->toDBtime($r['frei_ueber_funk_date']).' '.$this->tools->q($r['frei_ueber_funk_time'])):0,
					'f2' 		=> ($this->tools->isSizedString($r['rueckkehr_date']))? strtotime($this->tools->toDBtime($r['rueckkehr_date']).' '.$this->tools->q($r['rueckkehr_time'])):0
			);
			
			$arrSelect = $this->_db->fetchRow("SELECT `f_kenner`, `f3` FROM `".TBL_EINSAETZE_RESSOURCEN."` WHERE `eid` = '".$eID."' AND `f_kenner` = '".$r['funkkenner']."'");
			
			if ($arrSelect['f_kenner'] == $arrdbData['f_kenner'])
			{
			    
			    if (intval($arrSelect['f3']) > intval($arrdbData['f3'])) return;
			    else $arrUpdate = $arrdbData;
				
			    $this->_db->UpdateQuery(TBL_EINSAETZE_RESSOURCEN, $arrUpdate, "`f_kenner` = '". $arrdbData['f_kenner']."' AND `eid` = '".$eID."'");
			}
			else
			{
				$arrImport = $arrdbData;
				
				$this->_db->ImportQuery(TBL_EINSAETZE_RESSOURCEN, $arrImport);
			}
				
		}

		return;
		
	} // public function saveRessources($eID, $arrData)
	
	/**
	 * sichert das Persanl des Einsatzes
	 * @param varchar (16) $eID
	 * @param array $arrData
	 */
	public function savePersonal($eID, $arrData)
	{
		
		$arrdbData 	= array();
		$arrImport 	= array();
		$arrUpdate 	= array();
		
		/**
		 * sichern des einsatzpersonals in eigener Tabelle
		 */
		foreach ($arrData['personal'] as $index => $p)
		{
				
			if ($index === '###index###') continue;
				
			$arrdbData = array(
					'eid' 				=> $eID,
					'enr' 				=> $this->tools->q($arrData['einsatz_nr']),
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
				
			if ($this->_db->fetchOne("SELECT `f_kenner` FROM `".TBL_EINSAETZE_PERSONAL."` WHERE `eid` = '".$eID."' AND `f_kenner` = '".$p['funkkenner']."'"))
			{
		
				$arrUpdate = $arrdbData;
				
				$this->_db->UpdateQuery(TBL_EINSAETZE_PERSONAL, $arrUpdate, "`eid` = '".$eID."' AND `f_kenner` = '".$p['funkkenner']."'");
			}
			else
			{
				$arrImport = $arrdbData;
				 
				$this->_db->ImportQuery(TBL_EINSAETZE_PERSONAL, $arrImport);
			}
				
		}

		return;
		
	} // public function savePersonal($eID, $arrData)
	
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
