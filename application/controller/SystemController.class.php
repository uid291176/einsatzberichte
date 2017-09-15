<?php 

/**
 * System controller 
 * hiervon erben alle anderen Controller
 * @package    wms::framework
 * @copyright  Copyright (c) 2012, David Grimmer <david_grimmer@t-online.de>
 * @license    
 */
abstract class SystemController
{
	/**
	 * zentrales DB Objekt
	 * @var object
	 */
	public $db = NULL;
	
	/**
	 * zentrales config Array
	 * @var array
	 */
	public $conf = array();
	
	/**
	 * PAGER Anzahl der Datensätze pro Seite
	 * @var int
	 */
	public $pageEntrys = 25;
	
	/**
	 * PAGER Anzahl der direkt wählbaren Seiten
	 * @var int
	 */
	public $pageRange = 2;
	
	/**
	 * Daten die an das View Template gehen
	 * @var array
	 */
	public $view = array();
	
	/**
	 * bekommt den aktuellen  
	 * Controller zugewiesen
	 * @var string
	 */
	public $controller = NULL;
	
	/**
	 * bekommt die aktuelle 
	 * Action zugewiesen
	 * @var string
	 */
	public $action = NULL;
	
	/**
	 * Helper Functions Class
	 * @var unknown
	 */
	public $tools = NULL;
	
	/**
	 * array der JS Files im Frontend
	 * @var mixed
	 */
	public $arrJsFiles = array();
	
	/**
	 * array der JS Files im Frontend
	 * @var mixed
	 */
	public $arrCssFiles = array();
	
	/**
	 * speichert den aktuell angemeldeten Benutzer
	 * @var object
	 */
	public $user = NULL;
	
	/**
	 * speichert die ID des aktuell angemeldeten Benutzers
	 * @var int
	 */
	public $user_id = NULL;
	
	/**
	 * Array der Menueeintraege
	 * @var array
	 */
	public $menu;
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function __construct($controller, $action)
	{
		if (intval($_SESSION['user_id'] > 0)) $this->user_id = intval($_SESSION['user_id']);

		$this->controller = $controller;
		$this->action = $action;
		
		$this->tools = Helper_Functions::getInstance();
	}
	
	/**
	 * Config auslesen und DB Objekt instanzieren
	 * 
	 */
	public function init()
	{
		global $arrMenu;
		
		if (file_exists(CONF_PATH).'/config.ini')
		{
			$this->conf = parse_ini_file(CONF_PATH.'/config.ini', true);	
		}
		
		//table prefix
		define('TBL_PREFIX', $this->conf['db']['tbl_prefix']);
		
		//global table definitions 
		require_once LIB_PATH.'/tables.php';
		
		//global menu configuration
		require_once LIB_PATH.'/menuconf.php';
		
		$this->db = MySQLi_Database::getInstance($this->conf);	
		$this->db->set_charset('utf8');
		
		$this->user = User_Handler::geInstance($this->user_id, $this->db);
		$this->user->init();
		
		$arConf = $this->db->fetchAssoc("SELECT * FROM `".TBL_CONFIG."` WHERE 1");
		foreach ($arConf as $config)
		{
			$this->conf['options'][$config['key']] = $config['value'];
		}
		
		/**
		 * zentrale Pager Einstellungen sofern in Verwendung
		 */
		
			// Anzahl der Datensätze pro Seite -> default = 25
	    	if (intval($this->getConf('page_entrys')) > 0) $this->pageEntrys = intval($this->getConf('page_entrys'));
	    	
	    	// Anzahl der direkt anklickbaren Seiten des Pagers -> default = 2
	    	if (intval($this->getConf('page_range')) > 0) $this->pageRange = intval($this->getConf('page_range'));
	    
	    /**
	     * ENDE zentrale Pager Einstellungen
	     */
		
		$this->menu = $arrMenu;

	} // public function init()
	
	
	/**
	 * zeichnet die Frontendausgabe
	 * @param string Name des zu verwendenten Layouts
	 * @param string Controllername	
	 * @param string Action
	 */
	public function render($layout = NULL, $controller = NULL, $action = NULL)
	{
		
		if ($controller === NULL) $controller = $this->controller;
		if ($action === NULL) $action = $this->action;
			
		$inc = VIEW_PATH.'/'.$controller.'/';
		
		if(!file_exists($inc.$action.'.phtml')) die(_('ERROR::The requested template-file: '.$action.'.phtml'.' doesnt exist!'));
		
		$inc .= $action.'.phtml';
		
		if ($layout === NULL) $layout = FULL;
		
		if ($layout === FULL)
		{
			//css laden
			$this->arrCssFiles[] = '/screen.css';
			
			//js laden
			$this->arrJsFiles[] = '/jquery/1.12.0/jquery.min.js';
			$this->arrJsFiles[] = '/jquery-ui/1.12.1/jquery-ui.min.js';
			$this->arrJsFiles[] = '/custom.js';
	
			require_once VIEW_PATH.'/'.FULL.'.phtml';
		}
		
		if ($layout == MINI)
		{
			//css laden
			$this->arrCssFiles[] = '/minimal.css';
			
			require_once VIEW_PATH.'/'.$layout.'.phtml';					
		}
	
	} // public function render($layout = NULL, $controller = NULL, $action = NULL)
	
	public function renderMenu()
	{
		
		ob_start();
		
		require_once VIEW_PATH.'/mainmenu.phtml';
		
		$out = ob_get_contents();
		
		ob_clean();
		
		return $out;
		
	}
	
	/**
	 * Redirect-Funktion zu x-beliebigen Controller -> Aktion -> Übergabeparameter
	 * @param 	string	$controller
	 * @param 	string	$action
	 * @param 	string	$param
	 * @return	none
	 */
	public function redirect($controller, $action, $param = array())
	{
		header('Location:'.$this->tools->buildUrl($controller, $action, $param));
	}
	
	/**
	 * Gibt den Wert der mit [$key] bezeichneten configvariable zurück
	 * @param unknown_type $key
	 */
	public function getConf($key)
	{
		
		foreach ($this->conf as $config)
		{
			
			if (array_key_exists($key, $config))
			{
				$value = $config[$key];
			} 
				
			if (empty($value))
			{
				$value =  _('Fehler, eine Option mit dem KEY "'.$key.'" ist nicht vorhanden!');
			}

		}
		
		return $value;
		
        
	}// END public function getConf($key)
	
	/**
	 * sreibt eine negative Meldung für
	 * die Benutzerausgabe in die Session
	 * @param string $message
	 */
	public function setNegMessage($message)
	{	
		$_SESSION['NegMessage'][] = $message;	
	}
	
	/**
	 * sreibt eine positive Meldung für
	 * die Benutzerausgabe in die Session
	 * @param $message
	 */
	public function setPosMessage($message)
	{	
		$_SESSION['PosMessage'][] = $message;	
	}
		
	/**
	 * Ausgabe der evtl. in der SESSION hinterlegten Benutzerhinweise
	 * @return none
	 */
	public function drawMessages()
	{	
		if ($this->tools->isSizedArray($_SESSION['PosMessage']))
		{
			echo '<ul>';
			foreach ($_SESSION['PosMessage'] as $m)
			{
				echo '<li class="message message-ok"><span class="fa fa-check"></span>&nbsp;'.$m.'</li>';
			}
			echo '</ul>';
		}
			
		if ($this->tools->isSizedArray($_SESSION['NegMessage']))
		{
			echo '<ul>';
			foreach ($_SESSION['NegMessage'] as $m)
			{
				echo '<li class="message message-error"><span class="fa fa-times"></span>&nbsp;'.$m.'</li>';
			}
			echo '</ul>';
		}
		unset($_SESSION['NegMessage']);
		unset($_SESSION['PosMessage']);
			
	}
}
?>