<?php 
/**
 * 
 * @author uid291176
 *
 */
class IndexController extends SystemController
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
		E.SUPERIOREVENTTYPE LIKE 'Rettung' OR
      	E.SUPERIOREVENTTYPE LIKE 'Krankentransport' OR
      	E.SUPERIOREVENTTYPE LIKE 'Hilfeleistung'
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
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";
    	
    	// DB Abfrage    	
    	$this->view['data'] = $this->oraDB->fetchAssoc($sql);
    	
    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();

		$this->render();
		
	} // public function indexAction()
	
	/**
	 * erzeugt die Liste der stornierten Einsätze
	 */
	public function stornoAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
		      					(
			      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
			      				)
			      				AND
		      					EP.USE = 'place_of_action' AND
		      					(
		      						".$this->strStichwortFilter."
		      					)
		      					AND
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";
    	
    	//$this->tools->debug($sql);
    	
    	// DB Abfrage    	
    	$this->view['data'] = $this->oraDB->fetchAssoc($sql);

    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render(FULL, 'Index', 'index');
		
	} // public function stornoAction()
	
	/**
	 * erzeugt die Liste der beendeten Einsätze
	 */
	public function finishAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
		      					(
			      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
			      				)
			      				AND
		      					EP.USE = 'place_of_action' AND
		      					(
		      						".$this->strStichwortFilter."
		      					)
		      					AND
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";
    	
    	//$this->tools->debug($sql);
    	
    	// DB Abfrage    	
    	$this->view['data'] = $this->oraDB->fetchAssoc($sql);

    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render(FULL, 'Index', 'index');
		
	} // public function finishAction()
	
	/**
	 * erzeugt die Liste der abgeschlossenen Einsätze
	 */
	public function archivAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
		      					(
			      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
			      				)
			      				AND
		      					EP.USE = 'place_of_action' AND
		      					(
		      						".$this->strStichwortFilter."
		      					)
		      					AND
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";
    	
    	//$this->tools->debug($sql);
    	
    	// DB Abfrage    	
    	$this->view['data'] = $this->oraDB->fetchAssoc($sql);

    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render(FULL, 'Index', 'index');
		
	} // public function archivAction()
	
	/**
	 * erzeugt die Liste der aktuell alarmierten Kräfte Rettungsdienst
	 */
	public function alarmAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
		      					(
			      					E.STATUS = '".$this->statusMap[$_REQUEST['action']]."'
			      				)
			      				AND
		      					EP.USE = 'place_of_action' AND
		      					(
		      						".$this->strStichwortFilter."
		      					)
		      					AND
		      					E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
		      					E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')
		      				ORDER BY
	      						".$strOrderBy." ".$strOrderDirection."
	                		) E
          				WHERE
          					ROWNUM <= '".$limit."'
       					)
 					WHERE RN > '".$offset."'";
    	
    	//$this->tools->debug($sql);
    	
    	// DB Abfrage    	
    	$this->view['data'] = $this->oraDB->fetchAssoc($sql);

    	// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
    	
		$this->render(FULL, 'Index', 'index');
		
	} // public function alarmAction()
	
	public function searchAction()
	{
		
		if ($_REQUEST['cmd'] == 'detailview') $this->viewAction();
		
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
			
			$this->view['data'] = $this->oraDB->fetchAssoc($sql);
		}
		else 
		{
			$this->redirect('Index', 'index'); die();
		}

		$this->render(FULL, 'Index', 'index');
	}
	
	/**
	 * generiert das Einsatzobjekt zur Detaildarstellung
	 */
	public function viewAction()
	{
		
		// Einsatz ID (EVENT.ID)
		$eID = 0;
		$eID = $this->tools->q($_REQUEST['eid']);
		
		if(intval($eID) > 0)
		{
			$this->view['data'] = Einsatz_Handler::geInstance($eID);
			$this->view['data']->init();
		} 
		
		$this->render(FULL, 'Index', 'view');
		
	} // public function viewAction()
	
	/**
	 * gibt den gewählten Filterzeitraum zurück
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
	 * @param string $strAction
	 * @param string $strPeriod
	 */
	public function getCountData($strAction = 'index', $strPeriod = 'day')
	{
		
		$arrPeriod = $this->getPeriod($strPeriod);
		
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
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')";
			
		}

		return $this->oraDB->numRows($sql);
 
	} // public function getCountData($strAction = 'index', $strPeriod = 'day')
	
	/**
	 * gibt die Anzahl der Datensätze für die aktuell gewählte Periode array([start], [stop]) zurück
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
      				E.STARTTIME > to_date('".$arrPeriod['start']."', 'DD.MM.YYYY hh24:Mi:SS') AND
      				E.STARTTIME < to_date('".$arrPeriod['end']."', 'DD.MM.YYYY hh24:Mi:SS')";
			
		}
		
		return $this->oraDB->numRows($sql);
	
	} // public function getCurrentPeriodDataCount($strAction = 'index', $arrPeriod = array())
	
	/**
	 * setzt Spalte der Sortierung
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
	
}
?>