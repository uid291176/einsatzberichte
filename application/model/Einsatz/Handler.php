<?php 
/**
 * 
 * Erzeugt ein Einsatz- Objekt 
 * @author David
 */
class Einsatz_Handler
{
	
	/**
	 * enthält das Oracle DB Objekt
	 * @var object
	 */
	protected $oraDB = null;
	
	/**
	 * Einsatz ID (EVENT.ID)
	 * @var int
	 */
	protected $eID = null;
	
	/**
	 * enthällt die Daten des Einsatzes
	 * @var array
	 */
	public $eData = array();
	
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
	public function __construct($eID)
	{
		$this->eID = $eID;
		$this->oraDB = new Oci8_Database();
	}
	
	/**
	 * Neudeklaration verhindern
	 */
	private function __clone() {}
	
	/**
	 * statische Methode zur Rückgabe der Objektinstanz
	 * @param	array	Konfigurationsdaten
	 * @return 	Einsatz
	 */
	public static function geInstance($eID) 
	{
		
		if(self::$instance === null) 
		{
			self::$instance = new self($eID);
		}
		return self::$instance;
	}
	
	/**
	 * Daten des Einsatzes laden
	 */
	public function init()
	{
		$this->tools = Helper_Functions::getInstance();
		
		$this->eData = $this->getData();
	}
	
	/**
	 * sammeln aller Daten zum Einsatz
	 * @return array
	 */
	protected function getData()
	{
		
		// DB Anfragen die mehrdimensionale Arrays zurückgeben sollen Bsp: Resourcen
		$arrMultiDimArrayDBRequest = array(
			'details_ressourcen',
			'einsatz_protokoll',
		    'ressourcen_untereinsatz'
		);
		
		// nimmt die Ergebnismengen auf
		$arrResult = array();
		
		// Transportdetails (1)
		$sql['details'] = "SELECT
					ET.NAME											AS patient_name,
					ET.FIRSTNAME									AS patient_vorname,
					ET.BIRTHDAY										AS patient_geburtsdatum,
					ET.ZIPCODE										AS patient_plz,
					ET.COUNTRY										AS patient_land,
					ET.REGION										AS patient_region,
					ET.CITY											AS patient_ort,
					ET.CITY_DISTRICT								AS patient_ortsteil,
					ET.STREET1 										AS patient_strasse1,
					ET.STREET2 										AS patient_strasse2,
					ET.HOUSENUMBER									AS patient_hnr,
					ET.END											AS transport_ende,
					E.INFO_TO_RESOURCES								AS resource_info,
					E.EVENTNUM										AS einsatz_nummer,
					E.ID											AS einsatz_id,
					E.SUPERIOREVENTTYPE 							AS einsatz_hauptstichwort,
					E.NAMEEVENTTYPE									AS einsatz_stichwort,
					E.STATUS										AS einsatz_status,
					to_char(E.STARTTIME,'dd.mm.yyyy hh24:mi:ss')	AS einsatz_beginn,
					to_char(E.ALARMTIME,'dd.mm.yyyy hh24:mi:ss')	AS einsatz_alarm
				FROM
					EVENT E,
					EVENT_TRANSPORT ET
				WHERE
					E.ID = '".$this->eID."' AND
					ET.IDEVENT = E.ID";
		
		// Einsatzadresse (2)
		$sql['place_of_action'] = "SELECT
					EP.ADDROBJNAME		AS einsatz_objektname,
					EP.COUNTRY 			AS einsatz_land,
					EP.ZIPCODE 			AS einsatz_plz,
					EP.CITY 			AS einsatz_ort,
					EP.CITY_DISTRICT 	AS einsatz_ortsteil,
					EP.REGION 			AS einsatz_region,
					EP.STREET1 			AS einsatz_strasse1,
					EP.STREET2 			AS einsatz_strasse2,
					EP.HOUSENUMBER 		AS einsatz_hnr
   				FROM
   					EVENTPOS EP
   				WHERE
   					EP.IDEVENT = '".$this->eID."' AND
   					EP.USE = 'place_of_action'";
		
		// Transportziel (3)
		$sql['destination'] = "SELECT
					EP.ADDROBJNAME		AS ziel_objektname, 
					EP.COUNTRY 			AS ziel_land,
					EP.ZIPCODE 			AS ziel_plz,
					EP.CITY 			AS ziel_ort,
					EP.CITY_DISTRICT 	AS ziel_ortsteil,
					EP.REGION 			AS ziel_region,
					EP.STREET1 			AS ziel_strasse1,
					EP.STREET2 			AS ziel_strasse2,
					EP.HOUSENUMBER 		AS ziel_hnr
   				FROM
   					EVENTPOS EP
   				WHERE
   					EP.IDEVENT = '".$this->eID."' AND
   					EP.USE = 'destination'";
		
		// Meldungsaufnahme (4)
		$sql['details_meldungs_aufnahme'] = "SELECT
					to_char(E.CALLTIME,'dd.mm.yyyy hh24:mi:ss')	AS anrufer_anrufzeit,
					E.TELNUMBER 								AS anrufer_telefonnummer,
					E.FIRSTNAME 								AS anrufer_vorname,
					E.NAME 										AS anrufer_name,
					RP.NAME										AS personal_name,
					RP.FIRSTNAME								AS personal_vorname,
					RP.SHORTNAME								AS personal_kuerzel
   				FROM
   					EVENT E,
   					RESOURCE_PERSONNEL RP
   				WHERE
   					E.ID = '".$this->eID."' AND
   					E.IDCALLTAKER = RP.IDRESOURCE";
		
		// Ressourcendetails (5)
		$sql['details_ressourcen'] = "SELECT
					ER.REMARK 													AS resource_organisation,
					ER.NAME_AT_ALARMTIME 										AS resource_kenner,
					ER.NAMESTATION_AT_ALARMTIME 								AS resource_home,
					ER.STATUS													AS resource_status,
					to_char(ER.TIME_ALARM,'dd.mm.yyyy hh24:mi:ss') 				AS resource_alarm,
					to_char(ER.TIME_BEGIN_ALARM,'dd.mm.yyyy hh24:mi:ss') 		AS resource_alarm_beginn,
					to_char(ER.TIME_ON_THE_WAY,'dd.mm.yyyy hh24:mi:ss') 		AS resource_einsatz_uebernommen,
					to_char(ER.TIME_ARRIVED,'dd.mm.yyyy hh24:mi:ss') 			AS resource_ankunft,
					to_char(ER.TIME_FINISHED,'dd.mm.yyyy hh24:mi:ss') 			AS resource_einsatzende,
					to_char(ER.TIME_FINISHED_VIA_RADIO,'dd.mm.yyyy hh24:mi:ss') AS resource_frei_ueber_funk,
					to_char(ER.TIME_RESERVATION,'dd.mm.yyyy hh24:mi:ss')		AS resource_reserviert,
					to_char(ER.PLANNED_ARRIVED,'dd.mm.yyyy hh24:mi:ss')			AS resource_geplante_ankunft
				FROM
					EVENT E,
					EVENT_RESOURCE ER,
					RESOURCES R
				WHERE
					E.ID = ER.IDEVENT AND
					ER.IDRESOURCE = R.ID AND
					E.ID = '".$this->eID."'
   				ORDER BY
   					R.CALL_SIGN";
		
		// Ressourcen Untereinsätze
		$sql['ressourcen_untereinsatz'] = "SELECT
					ER.REMARK 													AS resource_organisation,
					ER.NAME_AT_ALARMTIME 										AS resource_kenner,
					ER.NAMESTATION_AT_ALARMTIME 								AS resource_home,
					ER.STATUS													AS resource_status,
					to_char(ER.TIME_ALARM,'dd.mm.yyyy hh24:mi:ss') 				AS resource_alarm,
					to_char(ER.TIME_BEGIN_ALARM,'dd.mm.yyyy hh24:mi:ss') 		AS resource_alarm_beginn,
					to_char(ER.TIME_ON_THE_WAY,'dd.mm.yyyy hh24:mi:ss') 		AS resource_einsatz_uebernommen,
					to_char(ER.TIME_ARRIVED,'dd.mm.yyyy hh24:mi:ss') 			AS resource_ankunft,
					to_char(ER.TIME_FINISHED,'dd.mm.yyyy hh24:mi:ss') 			AS resource_einsatzende,
					to_char(ER.TIME_FINISHED_VIA_RADIO,'dd.mm.yyyy hh24:mi:ss') AS resource_frei_ueber_funk,
					to_char(ER.TIME_RESERVATION,'dd.mm.yyyy hh24:mi:ss')		AS resource_reserviert,
					to_char(ER.PLANNED_ARRIVED,'dd.mm.yyyy hh24:mi:ss')			AS resource_geplante_ankunft
				FROM
					EVENT E,
					EVENT_RESOURCE ER,
					RESOURCES R
				WHERE
					ER.IDEVENT = E.ID  AND
					ER.IDRESOURCE = R.ID AND
					E.IDMAIN = '".$this->eID."'
   				ORDER BY
   					R.CALL_SIGN";
		
		// Einsatz Protokoll (6)
		$sql['einsatz_protokoll'] = "SELECT
					to_char(P.CREATION_DATE, 'dd.mm.yyyy hh24:mi') AS ZEIT,
					P.REMARK AS INHALT,
					P.CREATED_BY AS ERSTELLER
				FROM
					ERFURT_DATA.EVENT E,
					ERFURT_DATA.PROTOCOL_OF_EVENT PE,
					ERFURT_DATA.PROTOCOL P
				WHERE
					E.ID = '".$this->eID."' AND
					E.ID = PE.IDEVENT AND
					P.ID = PE.IDPROTOCOL AND
					P.TYPE = 'protocol_manual'
				ORDER BY
					P.CREATION_DATE";
		
		foreach ($sql as $index => $q)
		{
			
			/**
			 * per "in_array" eine selektierung der Abfragen zwischen fetchRow u. fetAssoc
			 * default ist fetchRow
			 */
			if (in_array($index, $arrMultiDimArrayDBRequest)) $arrResult[$index] = $this->oraDB->fetchAssoc($q);
			else $arrResult[$index] = $this->oraDB->fetchRow($q);
			
		}
		
		return $arrResult;	
		
		
		
	} // public function getData()
	
	/**
	 * liefert den Inhalt von $key des Einsatzes
	 * @param $key string
	 */
	public function get($key)
	{
		
		// Rückgabe default setting
		$return = false;
		
		foreach ($this->eData as $group => $data)
		{
			if ($group == 'details_ressourcen') continue;
			if ($group == 'ressourcen_untereinsatz') continue;
			if ($group == 'einsatz_protokoll') continue;
			
			foreach ($data as $k => $v)
			{
				if($k == $key) $return =  $v; 
			}
		}
		
		if ($return === false) return _('Diese Eigenschaft existiert für diesen Einsatz nicht');
		else return $return; 
		
	} // public function get($key)
	
	/**
	 * gibt nur die Fahrzeugdaten als array zurück.
	 */
	public function getResourchen()
	{
	    
	    if ($this->tools->isSizedArray($this->eData['ressourcen_untereinsatz']))
	    {
	        
	        $arrReturn = array_merge($this->eData['details_ressourcen'], $this->eData['ressourcen_untereinsatz']);
	        
	    }
	    else
	    {
	        
	        $arrReturn = $this->eData['details_ressourcen'];
	        
	    }
	    
	    return $arrReturn;
		
	} // public function getResourchen()
	
	/**
	 * gibt nur die Fahrzeugdaten der Untereinsätze als array zurück.
	 */
	public function getResourchenUntereinsatz()
	{
	    return $this->eData['ressourcen_untereinsatz'];
	    
	} // public function getResourchenUntereinsatz()
	
	/**
	 * gibt Rückmeldungen als array zurück.
	 */
	public function getEinsatzProtokoll()
	{
		$out = '';
		if ($this->tools->isSizedArray($this->eData['einsatz_protokoll']))
		{
			foreach ($this->eData['einsatz_protokoll'] as $msg)
			{
				$out .= $msg['ZEIT']."\n".$msg['INHALT']."\n\n";
			}
		}
		return $out;
		
	} // public function getEinsatzProtokoll()
	
	/**
	 * liefert die Differenzen zweier Zeiten
	 * @param unknown $strDateTimeStart
	 * @param unknown $strDateTimeEnde
	 * @param string $mod [FULL = return int timestamp]; [MIN = return int Minuten]; [STD = return int Stunden]; [DAY = return int Tage]
	 */
	public function getTimeDiff($strDateTimeStart, $strDateTimeEnde, $mod = 'FULL')
	{
		
		// gibt 0 zurück wenn keine Diff ermittelt werden kann
		if ($strDateTimeStart == '') return 0; if ($strDateTimeEnde == '') return 0;
		
		$intDiff = (strtotime($strDateTimeEnde) - strtotime($strDateTimeStart));
		
		$intTage 	= intval($intDiff / (60 * 60 * 24)); 	// Tage
		$intMin 	= intval($intDiff / 60); 				// Minuten
		$intStd 	= intval($intMin / 60); 				// Stunden
		
		// die berechneten Stunden aus den Minuten wieder "rausnehmen"
		if ($intMin > 59) $intMin = intval($intMin) - 60;
		
		//$this->tools->debug($intMin);
		switch ($mod)
		{
			case 'DAY':
				return $intTage;
				break;
			
			case 'STD':
				return $intStd;
				break;
				
			case 'MIN':
				return $intMin;
				break;
				
			default:
				return $intDiff;
		}
		
	} // public function getTimeDiff($strDateTimeStart, $strDateTimeEnde, $mod = 'FULL')
	
}
?>