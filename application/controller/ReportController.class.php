<?php 
/**
 * 
 * @author uid291176
 *
 */
class ReportController extends SystemController
{
	
	/**
	 * Oracle Datenbank Verbindungsklasse
	 * @var object
	 */
	public $oraDB = null;
	
	/**
	 * nach diesen Stichworten wird gefiltert
	 * @var string
	 */
	public $strStichwortFilter = "
		E.SUPERIOREVENTTYPE LIKE '%Brand%' OR
		E.SUPERIOREVENTTYPE LIKE '%Sonderlage/Ausnahmezustand%' OR
		E.SUPERIOREVENTTYPE LIKE '%Hilfeleistung%' OR
		E.SUPERIOREVENTTYPE LIKE '%Katastrophenschutz%'
	";
	
	/**
	 * Filterzeiträume
	 * @var array
	 */
	public $period = array();
	
	/**
	 * mapping der action zu dem DB Status
	 * @var array
	 */
	public $statusMap = array(
		'storno' 	=> 'cancelled',
		'finish' 	=> 'finished',
		'archiv' 	=> 'ad_acta',
		'alarm' 	=> 'alarmed'
	);
	
	/**
	 * Erzeugt die Liste der aktuellen einsätze für die Index Ansicht
	 */
	public function indexAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview')
		{
			$this->viewAction();
			
			return;
		}
		
		// Oracle DB Verbindungs-Objekt erzeugen
		$this->oraDB = new Oci8_Database();
    	
		// Zeitraum der Einsatzliste
    	if ($_REQUEST['date-select'] != '')
    	{
    		$arrPeriod['start'] = $this->tools->q($_REQUEST['date-select']).' 00:00:00';
    		$arrPeriod['end'] = $this->tools->q($_REQUEST['date-select']).' 23:59:59';
    	}
    	else 
    	{
    		$arrPeriod = $this->getPeriod($_REQUEST['period']);
    	}
    	
    	// Sortierung nach...[Spalte]
    	$strOrderBy = $this->setSQLOrderBy();    	
    	
    	// Aufsteigend / Absteigend
    	$strOrderDirection = $this->setSQLOrderDirection();
    	
    	// Tageszähler
    	$this->view['count']['day'] = $this->getCountData($_REQUEST['action'], 'day');
    	// Wochenzähler
    	$this->view['count']['week'] = $this->getCountData($_REQUEST['action'], 'week');
    	// Monatszähler
    	$this->view['count']['month'] = $this->getCountData($_REQUEST['action'], 'month');
    	
    	// Anzahl des aktuell gewählten Tages
    	$countEntrys = $this->getCurrentPeriodDataCount($_REQUEST['action'], $arrPeriod);
    	
    	// Anzahl der Seiten für die gewünschte Ergebnismenge
    	$pages = ceil($countEntrys / $this->pageEntrys);
    	
    	// Offset ab welchen die Datensätze aus der DB geholt werden
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $offset = ($_REQUEST['pPage'] - 1) * $this->pageEntrys;
    	else $offset = 0;
    	
    	// bis zu diesem Eintrag der Ergebnismenge werden die Daten gesammelt
    	if ($offset > 0) $limit = $_REQUEST['pPage'] * $this->pageEntrys;
    	else $limit = $this->pageEntrys;
    	
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $currentPage = intval($_REQUEST['pPage']);
    	else $currentPage = 1;
    	
    	// set Pager
    	$PAGER = new MySQLi_Pager($pages, $this->pageEntrys, $this->pageRange);
    	$PAGER->setActivePage($currentPage);
    	$arrUrlParam = array(
				'k' 				=> $_REQUEST['k'], 					// Controller
				'action' 			=> $_REQUEST['action'], 			// Action
				'period' 			=> $_REQUEST['period'], 			// Zeitraum
				'order_by' 			=> $_REQUEST['order_by'], 			// Sortierung nach
				'order_direction' 	=> $_REQUEST['order_direction'] 	// Sortierrichtung
			);

		// Wenn Datum übergeben	
		if (isset($_REQUEST['date-select']) && $_REQUEST['date-select'] != '') $arrUrlParam['date-select'] = $_REQUEST['date-select'];

		$PAGER->setUrl($arrUrlParam);
    	
    	// http://use-the-index-luke.com/de/sql/partielle-ergebnisse/blaettern
    	$sql = "SELECT *
  				FROM	( SELECT 	
  							EVENTNUM,
  							ROWNUM AS RN,
  							ALARMTIME,
  							STARTTIME,
  							STATUS,
  							EID,
  							NAME,
  							TELNUMBER,
  							CITY,
  							CITY_DISTRICT,
  							STREET1,
  							HOUSENUMBER,
  							SUPERIOREVENTTYPE,
  							ADDROBJNAME,
  							NAMEEVENTTYPE
	           			FROM ( 
	           				SELECT
		  						E.EVENTNUM,
			    				to_char(E.ALARMTIME, 'DD.MM.YYYY hh24:Mi:SS') AS ALARMTIME,
			    				to_char(E.STARTTIME, 'DD.MM.YYYY hh24:Mi:SS') AS STARTTIME,
			    				E.STATUS AS STATUS,
			    				E.NAME,
  								E.TELNUMBER,
			    				E.ID AS EID,
			    				EP.CITY,
			    				EP.CITY_DISTRICT,
			    				EP.STREET1,
			    				EP.HOUSENUMBER,
			    				E.SUPERIOREVENTTYPE,
			    				EP.ADDROBJNAME,
			    				E.NAMEEVENTTYPE 
	                    	FROM
	                    		EVENT E,
	      						EVENTPOS EP
	                    	WHERE
		      					E.ID = EP.IDEVENT AND
		      					EP.USE = 'place_of_action' AND
		      					(
		      						".$this->strStichwortFilter."
		      					)
		      					AND
                                EP.CITY = 'Erfurt' AND 
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS') AND
                                E.STATUS = 'finished'
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";

    	// DB Abfrage    	
    	$arrResult = $this->oraDB->fetchAssoc($sql);
        
    	// Berichte die in Bearbeitung sind
    	$arrEventEdit = $this->db->FetchAssocField("SELECT `eid` FROM `".TBL_EINSAETZE."` WHERE 1 AND `status` = 'edit' OR `status` = 'finish'");
    	
    	// alle Berichte welche schon in Bearbeitung sind aus der Ergebnismenge entfernen
    	if ($this->tools->isSizedArray($arrResult))
    	{
    	    foreach ($arrResult as $index => $event)
    	    {
    	        // nur die Berichte die noch unbearbeitet sind
    	        if (in_array($event['EID'], $arrEventEdit)) continue;
    	        else $this->view['data'][] = $event;
    	    }
    	}
    	
    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render();
		
	} // public function indexAction()
	
	
	/**
	 * speichert die bearbeiteten Daten aus dem Einsatz
	 */
	public function saveAction()
	{
	    
	    $eid = $this->tools->q(trim($_REQUEST['eid']));
	    
	    // Stammdaten update
	    $arrUpdateStammdaten = array(
	        
	        'anrufer_name'             => $this->tools->q(trim($_REQUEST['anrufer_name'])),
	        'anrufer_vorname'          => $this->tools->q(trim($_REQUEST['anrufer_vorname'])),
	        'anrufer_telefonnummer'    => $this->tools->q(trim($_REQUEST['anrufer_telefonnummer']))
	        
	    );
	    $this->db->UpdateQuery(TBL_EINSAETZE, $arrUpdateStammdaten, "`eid` = '".$eid."'");
	    
        // EAV TAB update
	    if ($this->tools->isSizedArray($_REQUEST['report'])) $arrEAVData = $_REQUEST['report'];
		
		if(array_key_exists('resourcen', $arrEAVData)) unset ($arrEAVData['resourcen']);
		if(array_key_exists('personal', $arrEAVData)) unset ($arrEAVData['personal']);
		if(array_key_exists('long_description', $arrEAVData)) unset ($arrEAVData['long_description']);
		
		$B = Einsatz_Bericht::geInstance($eid, $this->db);
		$B->init();
		
		foreach ($arrEAVData as $k => $v)
		{ 
		    //$B->_data[$k] = $v;
		    $B->saveEAV2db($this->tools->q($k), $this->tools->q($v));
		}
		
		$B->saveRessources($_REQUEST['report']['resourcen']);
		$B->savePersonal($_REQUEST['report']['personal']);	
		$B->saveProtokoll($_REQUEST['report']['long_description'], $this->user->username);
		
		$this->setPosMessage(_('Daten wurden erfolgreich gespeichert'));
		
		$this->redirect($_REQUEST['k'], 'view', array('eid' => $B->_eid, 'tmpl' => $this->tools->q(trim($_REQUEST['tmpl'])))); die();
		
		
	} // public function saveAction()
	
	public function editAction()
	{
		
	    //$this->tools->debug($_REQUEST);
	    
	    // wurde ein Einsatz übergeben?
	    if (isset($_REQUEST['eid']))
	    {
	        
	        $eId = $this->tools->q(trim($_REQUEST['eid']));
	        
	        /**
	         * ein neuer Einsatz der noch nicht bearbeitet wurde wird hier in der MySQL DB angelegt
	         */
	        if ($_REQUEST['cmd'] == 'presave')
	        {
	            $E = Einsatz_Handler::geInstance($eId, $this->db, $this->user_id);
	            $E->init();
	            $E->presave();
	            
	            $this->setPosMessage(_('Daten zu dem Bericht wurden gespeichert'));
	            
	            $this->redirect($_REQUEST['k'], 'view', array('eid' => $eId, 'tmpl' => $this->tools->q(trim($_REQUEST['tmpl'])))); die();
	            
	        } // Umleitung zur Einzelansicht des neuen Berichts
	        
	        /**
	         * todo der PDF Aufruf
	         */
	        
	        // ENDE PDF
	        
	        // einen bereits übernommen Einsatz weiter bearbeiten Einsatz 
	        $this->redirect($_REQUEST['k'], 'view', array('eid' => $eId, 'tmpl' => $this->tools->q(trim($_REQUEST['tmpl'])))); die();
	        
	    }
	    
	    // Sortierung nach...[Spalte]
	    if (isset($_REQUEST['order_by']))
	    {
	        
	        switch ($_REQUEST['order_by'])
	        {
	            case 'enr':
	                $strOrderBy = 'enr';
	                break;
	                
	            case 'stichwort':
	                $strOrderBy = 'haupt_stichwort';
	                break;
	                
	            case 'beginn':
	                $strOrderBy = 'beginn';
	                break;
	                
	            case 'adresse':
	                $strOrderBy = 'adresse';
	                break;
	                
	            case 'uid':
	                $strOrderBy = 'uid';
	                break;
	                
	            default:
	                $strOrderBy = 'enr';
	                
	        }
	    }
	    else
	    {
	        $strOrderBy = 'enr';
	    }
	    
	    // Aufsteigend / Absteigend
	    $strOrderDirection = $this->setSQLOrderDirection();
	    
	    // Anzahl der Einträge für den Nutzer ermitteln
	    $countEntrys = $this->db->FetchOne("SELECT COUNT(*) FROM `".TBL_EINSAETZE."` WHERE 1 AND `uid` = '".$this->user_id."' AND `status` = 'edit'");
	    
	    // Anzahl der Seiten für die gewünschte Ergebnismenge
	    $pages = ceil($countEntrys / $this->pageEntrys);
	    
	    // Offset ab welchen die Datensätze aus der DB geholt werden
	    if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $offset = ($_REQUEST['pPage'] - 1) * $this->pageEntrys;
	    else $offset = 0;
	    
	    // bis zu diesem Eintrag der Ergebnismenge werden die Daten gesammelt
	    if ($offset > 0) $limit = $_REQUEST['pPage'] * $this->pageEntrys;
	    else $limit = $this->pageEntrys;
	    
	    if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $currentPage = intval($_REQUEST['pPage']);
	    else $currentPage = 1;
	    
	    // set Pager
	    $PAGER = new MySQLi_Pager($pages, $this->pageEntrys, $this->pageRange);
	    $PAGER->setActivePage($currentPage);
	    $arrUrlParam = array(
	        'k' 				=> $_REQUEST['k'], 					// Controller
	        'action' 			=> $_REQUEST['action'], 			// Action
	        'period' 			=> $_REQUEST['period'], 			// Zeitraum
	        'order_by' 			=> $_REQUEST['order_by'], 			// Sortierung nach
	        'order_direction' 	=> $_REQUEST['order_direction'] 	// Sortierrichtung
	    );
	    
	    $PAGER->setUrl($arrUrlParam);
	    
	    // http://use-the-index-luke.com/de/sql/partielle-ergebnisse/blaettern
	    $sql = "SELECT
							*
						FROM
							`".TBL_EINSAETZE."`
						WHERE 1 AND
							`uid` = '".$this->user_id."' AND
							`status` = 'edit'
                        ORDER BY
	      			        `".$strOrderBy."` ".$strOrderDirection."
						LIMIT ".$limit."
						OFFSET ".$offset."
					";
	    
	    $this->view['data'] = $this->db->FetchAssoc($sql);
	    
	    // Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
	    if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
	    
	    $this->render();
		
	}
	
	/**
	 * erzeugt die Liste der abgeschlossenen Einsätze
	 */
	public function finishAction()
	{
		
		if (isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'pdfreview') $this->pdfReViewAction();
    	
		// Zeitraum der Einsatzliste
    	if ($_REQUEST['date-select'] != '')
    	{
    		if (strpos($_REQUEST['date-select'], '.'))
    		{
    			// wurde eine "leserliches" Datum übergeben
    			$arrDate = explode('.', $_REQUEST['date-select']);
    			
    			$intStart = mktime(0, 0, 0, $arrDate[1], $arrDate[0], $arrDate[2]);
    			$intEnd = $intStart + (60 * 60 * 24);

    		}
    		else 
    		{
    			
    			// wurde ein Timestamp übergeben
    			$selDate = date('m-d-Y', $_REQUEST['date-select']);
    			$arrDate = explode('-', $selDate);
    			
    			$intStart = mktime(0, 0, 0, $arrDate[0], $arrDate[1], $arrDate[2]);
    			$intEnd = $intStart + (60 * 60 * 24);
    		}
    		
    		$arrPeriod['start'] = $intStart;
    		$arrPeriod['end'] = $intEnd;

    	}
    	else 
    	{
    	
    		$strZeitraum = $this->tools->q($_REQUEST['period']);
    		
    		switch ($strZeitraum)
    		{
    			// vom heutigen Tag beginnend ab 0:0:00 Uhr
    			case 'day':
    				$arrPeriod['start'] = mktime(0,0,0, date('n'), date('j'), date('Y')) - 86400;
    				$arrPeriod['end'] = time();
    				break;

    			// vom Beginn der aktuellen Woche (Mo) ab 0:00:00 Uhr	
    			case 'week':
    				$intStartToday = mktime(0,0,0, date('n'), date('j'), date('Y'));
    				$intDayOfWeek = intval(date('N') - 1);
    				
    				$arrPeriod['start'] = $intStartToday - $intDayOfWeek * 86400;
    				$arrPeriod['end'] = time();
    				break;

    			// vom Beginn des aktuellen Monats ab 0:00:00 Uhr	
    			case 'month':
    				$intStartToday = mktime(0,0,0, date('n'), date('j'), date('Y'));
    				$intDayOfMonth = intval(date('j') - 1);
    				
    				$arrPeriod['start'] = $intStartToday - $intDayOfMonth * 86400;
    				$arrPeriod['end'] = time();
    				break;
    				
    			default:
    				$arrPeriod['start'] = mktime(0,0,0, date('n'), date('j'), date('Y'));
    				$arrPeriod['end'] = time();

    		} 
    		
    	}
    	
    	
		// Sortierung nach...[Spalte]
    	if (isset($_REQUEST['order_by']))
    	{
    		
    		switch ($_REQUEST['order_by'])
    		{
    			case 'enr':
    				$strOrderBy = 'enr';
    				break;
    				
    			case 'stichwort':
    				$strOrderBy = 'haupt_stichwort';
    				break;
    				
    			case 'beginn':
    				$strOrderBy = $strOrderBy = 'beginn';
    				break;
    				
    			case 'adresse':
    				$strOrderBy = $strOrderBy = 'adresse';
    				break;
    				
    			case 'uid':
    				$strOrderBy = $strOrderBy = 'uid';
    				break;
    				
    			default:
    				$strOrderBy = 'enr';
    				
    		}
    	}
    	else 
    	{
    	    $strOrderBy = 'enr';
    	}
    	
    	// Aufsteigend / Absteigend
    	$strOrderDirection = $this->setSQLOrderDirection();
    	
    	// Tageszähler
    	$intStart = mktime(0,0,0, date('m'), date('d'), date('Y'));
    	$intEnd = time();
    	$sql = "SELECT COUNT(*) AS SUM FROM `".TBL_EINSAETZE."` WHERE `status` = 'finish' AND `beginn` BETWEEN '".$intStart."' AND '".$intEnd."'";
		
    	$this->view['count']['day'] = $this->db->fetchOne($sql); $countEntrys['day'] = $this->view['count']['day'];

    	// Wochenzähler
    	$intStartToday = mktime(0,0,0, date('m'), date('d'), date('Y'));
    	$intDayOfWeek = intval(date('N') - 1);		
    	$intStart = $intStartToday - $intDayOfWeek * 86400;
    	$intEnd = time();
    	$sql = "SELECT COUNT(*) AS SUM FROM `".TBL_EINSAETZE."` WHERE `status` = 'finish' AND `beginn` BETWEEN '".$intStart."' AND '".$intEnd."'";
    	
    	$this->view['count']['week'] = $this->db->fetchOne($sql); $countEntrys['week'] = $this->view['count']['week'];

    	// Monatszähler
    	$intStartToday = mktime(0,0,0, date('m'), date('d'), date('Y'));
    	$intDayOfMonth = intval(date('j') - 1);	
    	$intStart = $intStartToday - $intDayOfMonth * 86400;
    	$intEnd = time();
    	$sql = "SELECT COUNT(*) AS SUM FROM `".TBL_EINSAETZE."` WHERE `status` = 'finish' AND `beginn` BETWEEN '".$intStart."' AND '".$intEnd."'";
    	
    	$this->view['count']['month'] = $this->db->fetchOne($sql); $countEntrys['month'] = $this->view['count']['month'];
    	
    	// Anzahl des aktuell gewählten Tages
    	if (strpos($_REQUEST['date-select'], '.'))
    	{
    		// bei übergabe eines leserlichen Datums
    		$arrDate = explode('.', $_REQUEST['date-select']);
    		$intStart = mktime(0,0,0, $arrDate[1], $arrDate[0], $arrDate[2]);
    	}
    	else 
    	{
    		// bei Übergabe eines UNIX Timestamps
    		$intStart = mktime(0,0,0, date('m', $_REQUEST['date-select']), date('d', $_REQUEST['date-select']), date('Y', $_REQUEST['date-select']));
    	}
    	
    	$intEnd =  $intStart + (60 * 60 * 24);
    	$sql = "SELECT COUNT(*) AS SUM FROM `".TBL_EINSAETZE."` WHERE `status` = 'finish' AND `beginn` BETWEEN '".$intStart."' AND '".$intEnd."'";
    	
    	$countEntrys['date-select'] = $this->db->fetchOne($sql);
    	
    	// ermittelt die Anzahl der Seiten für die Listenansicht mit Pager
    	$selEntrys = 1;
    	if (isset($_REQUEST['date-select'])) $selEntrys = $countEntrys['date-select'];
    	if (isset($_REQUEST['period'])) $selEntrys = $countEntrys[$this->tools->q($_REQUEST['period'])];    	

    	// Anzahl der Seiten für die gewünschte Ergebnismenge
    	$pages = ceil($selEntrys / $this->pageEntrys);
    	
    	// Offset ab welchen die Datensätze aus der DB geholt werden
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $offset = ($_REQUEST['pPage'] - 1) * $this->pageEntrys;
    	else $offset = 0;
    	
    	// bis zu diesem Eintrag der Ergebnismenge werden die Daten gesammelt
    	if ($offset > 0) $limit = $_REQUEST['pPage'] * $selEntrys;
    	else $limit = $selEntrys;
    	//die($this->pageEntrys);
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $currentPage = intval($_REQUEST['pPage']);
    	else $currentPage = 1;
    	
    	// set Pager
    	$PAGER = new MySQLi_Pager($pages, $this->pageEntrys, $this->pageRange);
    	$PAGER->setActivePage($currentPage);
    	$arrUrlParam = array(
				'k' 				=> $_REQUEST['k'], 					// Controller
				'action' 			=> $_REQUEST['action'], 			// Action
				'period' 			=> $_REQUEST['period'], 			// Zeitraum
				'order_by' 			=> $_REQUEST['order_by'], 			// Sortierung nach
				'order_direction' 	=> $_REQUEST['order_direction'] 	// Sortierrichtung
			);

		// Wenn Datum übergeben	
		if (isset($_REQUEST['date-select']) && $_REQUEST['date-select'] != '') $arrUrlParam['date-select'] = $_REQUEST['date-select'];

		$PAGER->setUrl($arrUrlParam);
    	
    	// http://use-the-index-luke.com/de/sql/partielle-ergebnisse/blaettern
    	$sql = "SELECT 
					*
				FROM
					`".TBL_EINSAETZE."`
				WHERE 1 AND 
					`status` = 'finish' AND
					`beginn` BETWEEN '".$arrPeriod['start']."' AND '".$arrPeriod['end']."'
                ORDER BY
	      			`".$strOrderBy."` ".$strOrderDirection."
				LIMIT ".$limit."
				OFFSET ".$offset."
			";
		
		$this->view['data'] = $this->db->FetchAssoc($sql);

    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render();

		
	} // public function finishAction()
	
	public function searchAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
		// Ausstieg für die Suchanfragen die nur innerhalb der MySQL DB stattfindet
		if ($_REQUEST['res'] == 'mysql') return $this->mysqlSearchAction($this->tools->q($_REQUEST['search']), $_REQUEST['referer']);
		
		// Oracle DB Verbindungs-Objekt erzeugen
		$this->oraDB = new Oci8_Database();
		
		// Suchstring welcher übergeben wurde
		$like = false;
		
		if (isset($_REQUEST['search']) && strlen($_REQUEST['search']) >= 6) $like = $this->tools->q($_REQUEST['search']);
		else $this->setNegMessage(_('FEHLER::Keine Einsatznummer übergeben!'));

		if($like !== false)
		{
			$strOrderBy 		= $this->setSQLOrderBy();
			$strOrderDirection 	= $this->setSQLOrderDirection();
			
			$sql = "SELECT
		  				E.EVENTNUM,
			    		to_char(E.ALARMTIME, 'DD.MM.YYYY hh24:Mi:SS') AS ALARMTIME,
			    		to_char(E.STARTTIME, 'DD.MM.YYYY hh24:Mi:SS') AS STARTTIME,
			    		E.STATUS AS STATUS,
			    		E.ID AS EID,
			    		EP.CITY,
			    		EP.CITY_DISTRICT,
			    		EP.STREET1,
			    		EP.HOUSENUMBER,
			    		E.SUPERIOREVENTTYPE,
			    		EP.ADDROBJNAME,
			    		E.NAMEEVENTTYPE 
					FROM
						EVENT E,
						EVENTPOS EP
					WHERE
						E.ID = EP.IDEVENT AND
						EP.USE = 'place_of_action' AND
						(
							".$this->strStichwortFilter."
						) AND
						E.EVENTNUM LIKE '".$like."%'
					ORDER BY
						".$strOrderBy." ".$strOrderDirection."";
			
			//$this->view['data'] = $this->oraDB->fetchAssoc($sql);
			
			// DB Abfrage
			$arrResult = $this->oraDB->fetchAssoc($sql);
			
			// Berichte die in Bearbeitung sind
			$arrEventEdit = $this->db->FetchAssocField("SELECT `eid` FROM `".TBL_EINSAETZE."` WHERE 1 AND `status` = 'edit' OR `status` = 'finish'");
			 
			foreach ($arrResult as $index => $event)
			{
				// nur die Berichte die noch unbearbeitet sind
				if (in_array($event['EID'], $arrEventEdit)) continue;
				else $this->view['data'][] = $event;
			}
			
			if ($this->tools->isSizedArray($this->view['data'])) $this->setPosMessage(_('Suchergebnisse: '.count($this->view['data']).' Einsätze'));
			else $this->setNegMessage(_('Es wurden keine Einsätze zu Ihrer Suche gefunden!'));
		}
		else 
		{
			
			$this->redirect('Report', 'index'); die();
		}
		
		$this->render(FULL, 'Report', 'index');
	}
	
	
	/**
	 * generiert das Einsatzobjekt zur Detaildarstellung
	 */
	public function viewAction()
	{
        
		$eID = $this->tools->q($_REQUEST['eid']);
		
		if(intval($eID) > 0)
		{
			$B = Einsatz_Bericht::geInstance($eID, $this->db);
			$B->init();
		} 
		
		// PDF Ausgaben
		if ($_REQUEST['cmd'] == 'to-pdf')
		{
		    
		    if ($_REQUEST['status'] == 'finish')
		    {
		        // Abschluss des Berichts -> DB -> finisch
		        if ($_REQUEST['typ'] == 'brandbericht') $this->createPDF($eID, 'brandbericht');
		        if ($_REQUEST['typ'] == 'hilfeleistungsbericht') $this->createPDF($eID, 'hilfeleistungsbericht');
		        
		        $this->db->UpdateQuery(TBL_EINSAETZE, array('status' => 'finish', 'bericht_abgeschlossen' => time()), "`eid` = '".$eID."'");
		        
		        // Sprung zu allen abgeschlossenen Einsätze mit Beginn am übergebenen Datum
		        $this->redirect($_REQUEST['k'], 'finish', array('date-select' => $B->get('beginn')));
		    }
		    else
		    {
		        // Ausgabe Druckansicht PDF
		        if ($_REQUEST['typ'] == 'brandbericht') $this->createPDF($eID, 'brandbericht', false);
		        if ($_REQUEST['typ'] == 'hilfeleistungsbericht') $this->createPDF($eID, 'hilfeleistungsbericht', false);
		    }
		    
		}
		
		$this->view['data'] = $B;
		
		// hier wird die Art des Berichtes (Hilfeleistung oder Brandbericht unterschieden)
		$this->render(FULL, 'Report', $this->tools->q($_REQUEST['tmpl']));
		
	} // public function viewAction()
	
	
	/**
	 * gibt den gewählten Filterzeitraum zurück
	 * für Oracle DB Abfragen
	 * @return array
	 */
	public function getPeriod($strPeriod = false)
	{
		
		if ($strPeriod == false)
		{
			$start = date('d.m.Y').' 00:00:01';
			$end = date('d.m.Y H:i:s');
		}
		else
		{
			switch ($strPeriod)
			{
				case 'day': // übersicht der letzten 24 Stunden bezogen auf die aktuelle Zeit
					$start = date('d.m.Y H:i:s', (time() - 60 * 60 *24));
					$end = date('d.m.Y H:i:s');
					break;
					
				case 'week': // übersicht der aktuellen Woche bezogen auf die aktuelle Zeit
					$start = date('d.m.Y', (time() - 60 * 60 *24 * (date('N') - 1))). ' 00:00:00';
					$end = date('d.m.Y H:i:s');
					break;
					
				case 'month': // übersicht des aktuellen Monats bezogen auf die aktuelle Zeit
					$start = date('d.m.Y', (time() - 60 * 60 *24 * (date('j') - 1))). ' 00:00:00';
					$end = date('d.m.Y H:i:s');
					break;
					
				default: // Tagesübersicht beginnend jeweils 00:00 Uhr des aktuellen Tages
					$start = date('d.m.Y').' 00:00:00';
					$end = date('d.m.Y H:i:s');
				
			}
			
		} 
		
		return array(
			'start' => $start,
			'end'	=> $end
		);

	}
	
	/**
	 * gibt die gesamtzahl an Einsätzen der jeweiligen Periode anhand eines Referenzwortes ['day'; 'week'; 'month'] zurück
	 * für Oracle DB Abfragen
	 * @param string $strAction
	 * @param string $strPeriod
	 */
	public function getCountData($strAction = 'index', $strPeriod = 'day')
	{
		
		$arrPeriod = $this->getPeriod($strPeriod);
		
		// im Ergebnis wird auf den WERT "COUNTDAY" referenziert!!!
		$sql = "SELECT 
                    E.ID AS EID
      			FROM 
      				EVENT E,
      				EVENTPOS EP
      			WHERE";
		
		// wenn $_REQUEST['action'] leer
		if ($strAction == 'index')
		{
			$sql .= "
      				E.ID = EP.IDEVENT AND
      				EP.USE = 'place_of_action' AND
      				(
      					".$this->strStichwortFilter."
      				)
      				AND
                    EP.CITY = 'Erfurt' AND
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS') AND
                    E.STATUS = 'finished'";
		}
		else
		{
			$sql .= "
      				E.ID = EP.IDEVENT AND
      				(
      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
      				)
      				AND
      				EP.USE like 'place_of_action' AND
      				(
      					".$this->strStichwortFilter."
      				)
      				AND
                    EP.CITY = 'Erfurt' AND
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS') AND
                    E.STATUS = 'finished'";
			
		}

		$arrEids = $this->oraDB->fetchAssoc($sql);
		
		// Berichte die in Bearbeitung sind
		$arrEventEdit = $this->db->FetchAssocField("SELECT `eid` FROM `".TBL_EINSAETZE."` WHERE 1 AND `status` = 'edit' OR `status` = 'finish'");
		
		foreach ($arrEids as $index => $event)
		{
		    // nur die Berichte die noch unbearbeitet sind
		    if (in_array($event['EID'], $arrEventEdit)) continue;
		    else $arrReturn[] = $event;
		}
		
		return count($arrReturn);
 
	} // public function getCountData($strAction = 'index', $strPeriod = 'day')
	
	/**
	 * gibt die Anzahl der Datensätze für die aktuell gewählte Periode array([start], [stop]) zurück
	 * für Oracle DB Abfragen
	 * @param string $strAction
	 * @param array $arrPeriod
	 */
	public function getCurrentPeriodDataCount($strAction = 'index', $arrPeriod = array())
	{
		
		// im Ergebnis wird auf den WERT "COUNTDAY" referenziert!!!
		$sql = "SELECT 
	    			COUNT(*) AS COUNTDAY
      			FROM 
      				EVENT E,
      				EVENTPOS EP
      			WHERE";
		
		// wenn $_REQUEST['action'] leer
		if ($strAction == 'index')
		{
			
			$sql .= "
      				E.ID = EP.IDEVENT AND
      				EP.USE = 'place_of_action' AND
      				(
      					".$this->strStichwortFilter."
      				)
      				AND
                    EP.CITY = 'Erfurt' AND
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')";

		}
		else
		{

			$sql .= "
      				E.ID = EP.IDEVENT AND
      				(
      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
      				)
      				AND
      				EP.USE like 'place_of_action' AND
      				(
      					".$this->strStichwortFilter."
      				)
      				AND
                    EP.CITY = 'Erfurt' AND
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')";
			
		}
		
		return $this->oraDB->numRows($sql);
	
	} // public function getCurrentPeriodDataCount($strAction = 'index', $arrPeriod = array())
	
	/**
	 * setzt das Icon in der Listendarstellung (index.phtml)
	 * @param string $strEventType
	 * @return string
	 */
	public function getEventIcon($strEventType)
	{

		$arrTypesMap = array(
			'Brand EF'						=> 'fa-fire',
			'Brand'							=> 'fa-fire',
			'Sonderlage/Ausnahmezustand'	=> 'fa-caret-square-o-up',
			'Hilfeleistung EF'				=> 'fa-wrench',
			'Gefahrguteinsatz EF'			=> 'fa-exclamation-triangle',
			'BMA'							=> 'fa-fire',
			'Hilfeleistung'					=> 'fa-wrench',
			'Katastrophenschutz'			=> 'fa-caret-square-o-up'
		);
		
		return $arrTypesMap[$strEventType];
		
	} // public function getEventIcon($strEventType)
	
	/**
	 * setzt die Icon Farbe in der Listendarstellung (index.phtml)
	 * @param string $strEventType
	 * @return string
	 */
	public function getIconColor($strEventType)
	{

		$arrTypesMap = array(
			'Brand EF'						=> 'brand-css',
			'Brand'							=> 'brand-css',
			'Sonderlage/Ausnahmezustand'	=> 'kat-css',
			'Hilfeleistung EF'				=> 'th-css',
			'Gefahrguteinsatz EF'			=> 'kat-css',
			'BMA'							=> 'brand-css',
			'Hilfeleistung'					=> 'th-css',
			'Katastrophenschutz'			=> 'kat-css'
		);
		
		return $arrTypesMap[$strEventType];
		
	} // public function getIconColor($strEventType)
	
	/**
	 * gibt den Berichtstyp zurück
	 * @param string $strEventType
	 * @return string
	 */
	public function getReportType($strEventType)
	{
		
		$arrTypesMap = array(
				'Brand EF'						=> 'Brandbericht',
				'Brand'							=> 'Brandbericht',
				'Sonderlage/Ausnahmezustand'	=> 'Hilfeleistungsbericht',
				'Hilfeleistung EF'				=> 'Hilfeleistungsbericht',
				'Gefahrguteinsatz EF'			=> 'Hilfeleistungsbericht',
				'BMA'							=> 'Brandbericht',
				'Hilfeleistung'					=> 'Hilfeleistungsbericht',
				'Katastrophenschutz'			=> 'Hilfeleistungsbericht'
		);
		
		return $arrTypesMap[$strEventType];
		
	} // public function getReportType($strEventType)
	
	
	
	/**
	 * setzt Spalte der Sortierung für Oracle DB Abfragen
	 * @return string
	 */
	protected function setSQLOrderBy()
	{
		// default Sortierung
    	$strOrderBy = 'EVENTNUM';
    	
    	if(isset($_REQUEST['order_by']))
    	{
	    	switch($_REQUEST['order_by'])
	    	{
	    		case 'enum': // Einsatznummer
	    			$strOrderBy = 'UPPER(EVENTNUM)';
	    			break;
	    		
	    		case 'stichwort': // Stichwort
	    			$strOrderBy = 'UPPER(NAMEEVENTTYPE)';
	    			break;
	    			
	    		case 'start': // Einsatzbeginn
	    			$strOrderBy = 'UPPER(STARTTIME)';
	    			Break;
	    			
	    		case 'city_district': // Stadtteil
	    			$strOrderBy = 'UPPER(CITY_DISTRICT)';
	    			Break;
	    			
	    		case 'street1': // Strasse
	    			$strOrderBy = 'UPPER(STREET1)';
	    			Break;
	    			
	    		default:
	    			$strOrderBy = 'UPPER(EVENTNUM)';
	    			break;
	    	}
    	}
    	return $strOrderBy;
    	
	} // protected function setSQLOrderBy()
	
	/**
	 * Richtung (Aufsteigend/ Absteigend) nach welcher die gefundene Ergebnismenge sortiert wird
	 * @return string
	 */
	protected function setSQLOrderDirection()
	{	
		// default Richtung der Sortierung (aufsteigend)
    	$strOrderDirection = 'ASC';
    	
    	if (isset($_REQUEST['order_direction']))
    	{
	    	switch($_REQUEST['order_direction'])
	    	{
	    		case 'desc': // absteigend 
	    			$strOrderDirection = 'DESC';
	    			break;
	    		
	    		case 'asc': // aufsteigend 
	    			$strOrderDirection = 'ASC';
	    			break;
	    			
	    		default: // aufsteigend
	    			$strOrderDirection = 'ASC';
	    			break;
	    	}
    	}
    	return $strOrderDirection;
    	
	} // protected function setSQLOrderDirection()
	
	public function createPDF($eID, $tmpl = 'brandbericht', $archiv = true)
	{
		
		$desPath = $_SERVER['DOCUMENT_ROOT'].$this->getConf('upload_path');
		
		$B = new Einsatz_Bericht($eID, $this->db);
		$B->init();
		//$this->view['data'] = $B;
		
		// TCPDF Library laden
		require_once(LIB_PATH.'/Tcpdf/tcpdf.php');
		
		// Erstellung des PDF Dokuments
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// Dokumenteninformationen
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor($this->user->get('vorname').' '.$this->user->get('name'));
		$pdf->SetTitle(ucfirst($tmpl).' Thüringen');
		$pdf->SetSubject(ucfirst($tmpl).' Thüringen');
		
		// Header und Footer Informationen
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		// remove default header/footer
		$pdf->setPrintHeader(false);
		//$pdf->setPrintFooter(false);
		
		// Auswahl des Font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		// Auswahl der MArgins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		// Automatisches Autobreak der Seiten
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		// Image Scale
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		// Schriftart
		$pdf->SetFont('helvetica', '', 8);
		
		// Neue Seite
		$pdf->AddPage();
		
		ob_start();
		
		require_once VIEW_PATH.'/Report/'.$tmpl.'.phtml';
		
		$html = ob_get_contents();
		
		ob_clean();
		
		// Fügt den HTML Code in das PDF Dokument ein
		$pdf->writeHTML($html, true, false, true, false, '');
		
		// Statistik BMA anhängen
        if ($B->get('brandobjekt_bma_vorhanden') == 'ja')
        {
            // Neue Seite
            $pdf->AddPage();
            
            ob_start();
            
            require_once VIEW_PATH.'/Report/bma_statistik.phtml';
            
            $html = ob_get_contents();
            
            ob_clean();
            
            // Fügt den HTML Code in das PDF Dokument ein
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        
        // gesonderter Bericht 
        if (strlen(trim(nl2br($B->getProtokoll()))) > 1024)
        {
            // Neue Seite
            $pdf->AddPage();
            
            ob_start();
            
            require_once VIEW_PATH.'/Report/ext_einsatzbericht.phtml';
            
            $html = ob_get_contents();
            
            ob_clean();
            
            // Fügt den HTML Code in das PDF Dokument ein
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        
		
		$pdfName = $B->get('enr').'.pdf';
		
		//Ausgabe der PDF
		if ($archiv === true)
		{

			//Variante 1: PDF im Filesystem ablegen:			
			$arrReportFolders = scandir($desPath);
			
			if (in_array(date('Y-m-d', $B->get('beginn')), $arrReportFolders))
			{
				
				$pdf->Output($desPath.'/'.date('Y-m-d', $B->get('beginn')).'/'.$pdfName, 'F');
			}
			else 
			{
				mkdir($desPath.'/'.date('Y-m-d', $B->get('beginn'))); // Ordner mit dem Datum des Einsatzbeginns anlegen
				$pdf->Output($desPath.'/'.date('Y-m-d', $B->get('beginn')).'/'.$pdfName, 'F');
			}
			return;

		}
		else 
		{
		   
			//Variante 2: PDF direkt an den Benutzer senden:
			$pdf->Output($pdfName, 'I');
		}
		
	} // public function createPDF($eID, $tmpl = 'brandbericht', $archiv = false)
	
	
	/**
	 * Suchfunction welche nur innerhalb der MySQL DB sucht
	 * nutzbar nur für editierte abgeschlossene und archivierte Einsätze
	 * @param unknown_type $eid
	 */
	public function mysqlSearchAction($strSearch, $strReferer)
	{
		
		$like = false;
		if (strlen($strSearch) >= 6) $like = $strSearch;
		else $this->setNegMessage(_('FEHLER::Keine Einsatznummer übergeben!'));

		$sql = "SELECT * FROM `".TBL_EINSAETZE."` WHERE 1 AND `enr` LIKE '".$like."%' AND `status` = '".$strReferer."'";
		
		if ($like) $result = $this->db->fetchAssoc($sql);
		
		if ($this->tools->isSizedArray($result))
		{
			$this->view['data'] = $result;
			
			$this->setPosMessage(_('Suchergebnisse: '.count($result).' Einsätze'));
			
			$this->render(FULL, $this->controller, $strReferer);
			
			die();
		}
		
		$this->setNegMessage(_('Es wurden keine Einsätze zu Ihrer Suche gefunden!'));
		
		$this->redirect($this->controller, $strReferer); 
		
	} // public function mysqlSearchAction($strSearch, $strAction)
	
	/**
	 * gibt den Bericht als PDF aus
	 * diese Funktion wird nur zum Anzeigen der abgeschlossenen (finish) oder archivierten (archiv) Einsätze genutzt
	 * an dieser Stelle werden keine PDFs erzeugt
	 * @param bool $download = false (im Browser ausgeben) = true (als Download anbieten) 
	 */
	public function pdfReViewAction($download = false)
	{
		
		$filename = 'Einsatzbericht_';
		$filename .= $this->tools->q($_REQUEST['enr']);
		
		$file = $_SERVER['DOCUMENT_ROOT'];
		$file .= $this->getConf('upload_path').'/';
		$file .= $_REQUEST['date-folder'].'/';
		$file .= $this->tools->q($_REQUEST['enr']);
		
		// als Download anbieten oder im Browser (standard) ausgeben
		if ($download)
		{
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');
			readfile($file.'.pdf');
		}
		else 
		{
			header('Content-Type: application/pdf');
			$pdf = file_get_contents($file.'.pdf');
			die($pdf);
		}

		
	} // public function pdfReViewAction()
	
}
?>