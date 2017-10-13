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
	 * enthält das MySQL DB Objekt
	 * @var object
	 */
	protected $mysqlDB = null;
	
	/**
	 * Einsatz ID (EVENT.ID)
	 * @var int
	 */
	protected $eID = null;
	
	/**
	 * User ID (Sachbearbeiter)
	 * @var int
	 */
	protected $userID = null;
	
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
	public function __construct($eID, $db, $uid)
	{
		$this->eID = $eID;
		$this->oraDB = new Oci8_Database();
		$this->mysqlDB = $db;
		$this->userID = $uid;
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
	public static function geInstance($eID, $db, $uid) 
	{
		
		if(self::$instance === null) 
		{
			self::$instance = new self($eID, $db, $uid);
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
		
		// Einsatzdetails (1)
		$sql['event'] = "SELECT
					E.INFO_TO_RESOURCES								AS resource_info,
					E.EVENTNUM										AS einsatz_nummer,
					E.ID											AS einsatz_id,
					E.SUPERIOREVENTTYPE 							AS einsatz_hauptstichwort,
					E.NAMEEVENTTYPE									AS einsatz_stichwort,
					E.STATUS										AS einsatz_status,
					to_char(E.STARTTIME,'YYYY-MM-DD hh24:mi:ss')	AS einsatz_beginn,
					to_char(E.ALARMTIME,'YYYY-MM-DD hh24:mi:ss')	AS einsatz_alarm
				FROM
					EVENT E
				WHERE
					E.ID = '".$this->eID."'";
		
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
		
		// Meldungsaufnahme (4)
		$sql['details_meldungs_aufnahme'] = "SELECT
					to_char(E.CALLTIME,'YYYY-MM-DD hh24:mi:ss')	AS anrufer_anrufzeit,
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
					to_char(ER.TIME_ALARM,'YYYY-MM-DD hh24:mi:ss') 				AS resource_alarm,
					to_char(ER.TIME_BEGIN_ALARM,'YYYY-MM-DD hh24:mi:ss') 		AS resource_alarm_beginn,
					to_char(ER.TIME_ON_THE_WAY,'YYYY-MM-DD hh24:mi:ss') 		AS resource_einsatz_uebernommen,
					to_char(ER.TIME_ARRIVED,'YYYY-MM-DD hh24:mi:ss') 			AS resource_ankunft,
					to_char(ER.TIME_FINISHED,'YYYY-MM-DD hh24:mi:ss') 			AS resource_einsatzende,
					to_char(ER.TIME_FINISHED_VIA_RADIO,'YYYY-MM-DD hh24:mi:ss') AS resource_frei_ueber_funk,
                    ET.CREW_MIN                                                 AS resource_besatzung_min,
                    ET.CREW_STD                                                 AS resource_besatzung
				FROM
					EVENT E,
					EVENT_RESOURCE ER,
					RESOURCES R,
                    RESOURCETYPE ET
				WHERE
					E.ID = ER.IDEVENT AND
					ER.IDRESOURCE = R.ID AND
					E.ID = '".$this->eID."' AND
                    ER.RESOURCETYPE_AT_ALARMTIME = ET.NAME
   				ORDER BY
   					R.CALL_SIGN";
		
		// Ressourcen Untereinsätze (6)
		$sql['ressourcen_untereinsatz'] = "SELECT
					ER.REMARK 													AS resource_organisation,
					ER.NAME_AT_ALARMTIME 										AS resource_kenner,
					ER.NAMESTATION_AT_ALARMTIME 								AS resource_home,
					to_char(ER.TIME_ALARM,'YYYY-MM-DD hh24:mi:ss') 				AS resource_alarm,
					to_char(ER.TIME_BEGIN_ALARM,'YYYY-MM-DD hh24:mi:ss') 		AS resource_alarm_beginn,
					to_char(ER.TIME_ON_THE_WAY,'YYYY-MM-DD hh24:mi:ss') 		AS resource_einsatz_uebernommen,
					to_char(ER.TIME_ARRIVED,'YYYY-MM-DD hh24:mi:ss') 			AS resource_ankunft,
					to_char(ER.TIME_FINISHED,'YYYY-MM-DD hh24:mi:ss') 			AS resource_einsatzende,
					to_char(ER.TIME_FINISHED_VIA_RADIO,'YYYY-MM-DD hh24:mi:ss') AS resource_frei_ueber_funk,
                    ET.CREW_MIN                                                 AS resource_besatzung_min,
                    ET.CREW_STD                                                 AS resource_besatzung
				FROM
					EVENT E,
					EVENT_RESOURCE ER,
					RESOURCES R,
                    RESOURCETYPE ET
				WHERE
					ER.IDEVENT = E.ID  AND
					ER.IDRESOURCE = R.ID AND
					E.IDMAIN = '".$this->eID."' AND
                    ER.RESOURCETYPE_AT_ALARMTIME = ET.NAME
   				ORDER BY
   					R.CALL_SIGN";
		
		// Einsatz Protokoll (7)
		$sql['einsatz_protokoll'] = "SELECT
					to_char(P.CREATION_DATE, 'YYYY-MM-DD hh24:mi') AS ZEIT,
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
		
		$this->getResourchen();
		$this->getResourchenUntereinsatz();
		$this->getEinsatzProtokoll();
		
		return $arrResult;	
		
		
	} // public function getData()
	
	/**
	 * sichert alle Daten aus dem ELS (ORACLE DB) nach MySQL DB
	 */
	public function presave()
	{
	    //$this->tools->debug($this->eData);
	    $arrImport = array(
	        
	        // Einsatz Stammdaten
	        'eid'                      => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	        'enr'                      => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	        'haupt_stichwort'          => $this->tools->q(trim($this->eData['event']['EINSATZ_HAUPTSTICHWORT'])),
	        'stichwort'                => $this->tools->q(trim($this->eData['event']['EINSATZ_STICHWORT'])),
	        'beginn'                   => $this->tools->mkTimeStamp(trim($this->eData['event']['EINSATZ_BEGINN'])),
	        'alarm'                    => $this->tools->mkTimeStamp(trim($this->eData['event']['EINSATZ_ALARM'])),
	        'typ'                      => $this->tools->q(trim($this->eData['event']['EINSATZ_HAUPTSTICHWORT'])),
	        'uid'                      => $this->userID,
	        'status'                   => 'edit',
	        
	        // Einsatzadresse
	        'adresse1'                 => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_STRASSE1'])),
	        'adresse2'                 => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_STRASSE2'])),
	        'ort'                      => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_ORT'])),
	        'ortsteil'                 => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_ORTSTEIL'])),
	        'region'                   => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_REGION'])),
	        'plz'                      => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_PLZ'])),
	        'land'                     => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_LAND'])),
	        'hnr'                      => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_HNR'])),
	        'objektname'               => $this->tools->q(trim($this->eData['place_of_action']['EINSATZ_OBJEKTNAME'])),
	        'bericht_abgeschlossen'    => 0,
	        
	        // Meldungsaufnahme
	        'anrufer_anrufzeit'        => $this->tools->mkTimeStamp(trim($this->eData['details_meldungs_aufnahme']['ANRUFER_ANRUFZEIT'])),
	        'anrufer_telefonnummer'    => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['ANRUFER_TELEFONNUMMER'])),
	        'anrufer_vorname'          => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['ANRUFER_VORNAME'])),
	        'anrufer_name'             => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['ANRUFER_NAME'])),
	        'peronal_vorname'          => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['PERSONAL_VORNAME'])),
	        'personal_name'            => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['PERSONAL_NAME'])),
	        'personal_kuerzel'         => $this->tools->q(trim($this->eData['details_meldungs_aufnahme']['PERSONAL_KUERZEL'])),

	    );
	    $this->mysqlDB->ImportQuery(TBL_EINSAETZE, $arrImport);

	    // Ressourcen Haupteinsatz
	    $arrImport = array();
	    foreach ($this->eData['details_ressourcen'] as $res)
	    {
	    
	        $arrImport = array(
	            
	            'eid'          => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	            'enr'          => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	            'organisation' => $this->tools->q(trim($res['RESOURCE_ORGANISATION'])),
	            'f_kenner'     => $this->tools->q(trim(substr($res['RESOURCE_KENNER'], 0, 12))),
	            'standort'     => $this->tools->q(trim($res['RESOURCE_HOME'])),
	            'alarm_beginn' => $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM_BEGINN'])),
	            'alarm'        => $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM'])),
	            'f3'           => (!empty($res['RESOURCE_EINSATZ_UEBERNOMMEN']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZ_UEBERNOMMEN'])):'0',
	            'f4'           => (!empty($res['RESOURCE_ANKUNFT']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_ANKUNFT'])):'0',
	            'f1'           => (!empty($res['RESOURCE_FREI_UEBER_FUNK']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_FREI_UEBER_FUNK'])):'0',
	            'f2'           => (!empty($res['RESOURCE_EINSATZENDE']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZENDE'])):'0'
	            
	        );
	        // ToDo Prüfung auf Dopplung einer Ressource in einem Einsatz (Fehleingabe Disponent)
	        $this->mysqlDB->ImportQuery(TBL_EINSAETZE_RESSOURCEN, $arrImport);

	        // Personal der Ressource
	        $arrImport = array(
	            
	            'eid'                  => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	            'enr'                  => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	            'f_kenner'             => $this->tools->q(trim(substr($res['RESOURCE_KENNER'], 0, 12))),
	            'e_zeit'               => (($this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZENDE'])) - $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM']))) / 60),
	            'aus_h'                => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'h'),
	            'aus_g'                => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'g'),
	            'aus_m'                => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'm'),
	            'aus_ff'               => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'ff'),
	            'eingesetzt_h'         => (string) '0',
	            'eingesetzt_g'         => (string) '0',
	            'eingesetzt_m'         => (string) '0',
	            'eingesetzt_ff'        => (string) '0'
	            
	        );
	        $this->mysqlDB->ImportQuery(TBL_EINSAETZE_PERSONAL, $arrImport);
	        
	        

	    }

	    // Ressourcen Untereinsatz
	    $arrImport = array();
	    foreach ($this->eData['ressourcen_untereinsatz'] as $res)
	    {
	        
	        $arrImport = array(
	            
	            'eid'          => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	            'enr'          => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	            'organisation' => $this->tools->q(trim($res['RESOURCE_ORGANISATION'])),
	            'f_kenner'     => $this->tools->q(trim(substr($res['RESOURCE_KENNER'], 0, 12))),
	            'standort'     => $this->tools->q(trim($res['RESOURCE_HOME'])),
	            'alarm_beginn' => $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM_BEGINN'])),
	            'alarm'        => $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM'])),
	            'f3'           => (!empty($res['RESOURCE_EINSATZ_UEBERNOMMEN']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZ_UEBERNOMMEN'])):'0',
	            'f4'           => (!empty($res['RESOURCE_ANKUNFT']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_ANKUNFT'])):'0',
	            'f1'           => (!empty($res['RESOURCE_FREI_UEBER_FUNK']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_FREI_UEBER_FUNK'])):'0',
	            'f2'           => (!empty($res['RESOURCE_EINSATZENDE']))? $this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZENDE'])):'0'
	            
	        );
	        // ToDo Prüfung auf Dopplung einer Ressource in einem Einsatz (Fehleingabe Disponent)
	        $this->mysqlDB->ImportQuery(TBL_EINSAETZE_RESSOURCEN, $arrImport);
	        
	        // Personal der Ressource 
	        $arrImport = array(
	            
	            'eid'              => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	            'enr'              => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	            'f_kenner'         => $this->tools->q(trim(substr($res['RESOURCE_KENNER'], 0, 12))),
	            'e_zeit'           => (($this->tools->mkTimeStamp(trim($res['RESOURCE_EINSATZENDE'])) - $this->tools->mkTimeStamp(trim($res['RESOURCE_ALARM']))) / 60),
	            'aus_h'            => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'h'),
	            'aus_g'            => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'g'),
	            'aus_m'            => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'm'),
	            'aus_ff'           => $this->getPersType($this->tools->q(trim($res['RESOURCE_BESATZUNG'])), $this->tools->q(trim($res['RESOURCE_KENNER'])), 'ff'),
	            'eingesetzt_h'     => (string) '0',
	            'eingesetzt_g'     => (string) '0',
	            'eingesetzt_m'     => (string) '0',
	            'eingesetzt_ff'    => (string) '0'
	            
	        );
	        $this->mysqlDB->ImportQuery(TBL_EINSAETZE_PERSONAL, $arrImport);
	        
	    }
	    
	    // Protokoll/ Lagefilm/ Rückmeldungen zur LST
	    $arrImport = array(
	        
	        'eid'          => $this->tools->q(trim($this->eData['event']['EINSATZ_ID'])),
	        'enr'          => $this->tools->q(trim($this->eData['event']['EINSATZ_NUMMER'])),
	        'zeit'         => $this->tools->mkTimeStamp(trim($this->eData['event']['EINSATZ_BEGINN'])),
	        'ersteller'    => $this->userID
	    );
	    
	    $strOut = '';
	    foreach ($this->eData['einsatz_protokoll'] as $msg)
	    {
	        $strOut .= '['.date('d.m.Y H:i:s', $this->tools->mkTimeStamp(trim($msg['ZEIT']))).']'.' -- '.$this->tools->q(trim($msg['INHALT'])).' -- '.'['.$this->tools->q(trim($msg['ERSTELLER'])).']'."\n\n";
	    }
	    
	    $arrImport['inhalt'] = $strOut;
	    
	    $this->mysqlDB->ImportQuery(TBL_EINSAETZE_PROTOKOLL, $arrImport);
	   
	    
	} // public function save()

	
	/**
	 * gibt nur die gesamten Fahrzeugdaten als ARRAY zurück
	 * @return array $arrReturn
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
	 * gibt nur die Fahrzeugdaten der Untereinsätze als ARRAY zurück.
	 * @return array 
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
	
	
	/**
	 * liefert die Zusammensetzung der Normbesatzung für die Fahrezuge der BF-EF
	 * @param unknown $strBesatzung
	 * @param unknown $strKenner
	 * @param string $strDienstGruppe
	 * @return string|unknown
	 */
	public function getPersType($strBesatzung, $strKenner, $strDienstGruppe = 'm')
	{
        
	    if(strpos($strKenner, '/')) $arrKenner = explode('/', $strKenner);

	    /**
	     * Alle Feuerwehren der Stadt Erfurt
	     */
	    if ($this->tools->isSizedArray($arrKenner))
	    {
	        switch ($arrKenner[0])
	        {
	            // BF Wache1
	            case '01':
	                
	                // ELW1
	                if ($arrKenner[1] == '11')
	                {
	                    if ($strDienstGruppe == 'h') return '0';
	                    if ($strDienstGruppe == 'g') return '1';
	                    if ($strDienstGruppe == 'm') return '1';
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                
	                // KdoW
	                elseif ($arrKenner[1] == '10')
	                {
	                    if ($strDienstGruppe == 'h') return '1';
	                    if ($strDienstGruppe == 'g') return '0';
	                    if ($strDienstGruppe == 'm') return '0';
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                else 
	                {
	                    if ($strDienstGruppe == 'h') return '0';
	                    if ($strDienstGruppe == 'g') return '0';
	                    if ($strDienstGruppe == 'm') return $strBesatzung;
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                break;
	                
	            // BF Wache2
	            case '02':
	                
	                // ELW1
	                if ($arrKenner[1] == '11')
	                {
	                    if ($strDienstGruppe == 'h') return '0';
	                    if ($strDienstGruppe == 'g') return '1';
	                    if ($strDienstGruppe == 'm') return '1';
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                
	                // KdoW
	                elseif ($arrKenner[1] == '10')
	                {
	                    if ($strDienstGruppe == 'h') return '1';
	                    if ($strDienstGruppe == 'g') return '0';
	                    if ($strDienstGruppe == 'm') return '0';
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                else 
	                {
	                    if ($strDienstGruppe == 'h') return '0';
	                    if ($strDienstGruppe == 'g') return '0';
	                    if ($strDienstGruppe == 'm') return $strBesatzung;
	                    if ($strDienstGruppe == 'ff') return '0';
	                }
	                break;
	            
	            // FF
	            default:
	                if ($strDienstGruppe == 'h') return '0';
	                if ($strDienstGruppe == 'g') return '0';
	                if ($strDienstGruppe == 'm') return '0';
	                if ($strDienstGruppe == 'ff') return $strBesatzung;
	                
	        }
	    
	    } // Alle Feuerwehren der Stadt Erfurt

	} // public function getPersType($strBesatzung, $strKenner, $strDienstGruppe = 'm')
	
}
?>