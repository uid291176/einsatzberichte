<?php
	//die(phpinfo());
	// nur Fehler anzeigen; (E_All) für alles
	error_reporting(E_ALL ^  E_NOTICE);
	ini_set('display_errors', true);
	
	//Sitzung einleiten
	session_start(); 
	
	//Pfaddefinitionen
	define('APP_PATH'		, 	dirname(__FILE__).'/../application');
	define('CONF_PATH'		,	APP_PATH.'/conf');
	define('CONT_PATH'		,	APP_PATH.'/controller');
	define('LIB_PATH'		, 	APP_PATH.'/lib');
	define('MOD_PATH'		, 	APP_PATH.'/model');
	define('VIEW_PATH'		, 	APP_PATH.'/view');
	
	//Rendermodi
	define('FULL'	,	'fullpage');
	define('MINI'	,	'minimal');
	define('AJAX'	,	'ajax');
	
	//Quotierte $_REQUEST-Objekte bereinigen
	if (get_magic_quotes_gpc()) 
	{ 
     	if (!empty($_GET))    { $_GET    = strip_mgcq_runtime($_GET);    } 
     	if (!empty($_POST))   { $_POST   = strip_mgcq_runtime($_POST);   }
     	if (!empty($_COOKIE)) { $_COOKIE = strip_mgcq_runtime($_COOKIE); }
	}
	
	// hier wird der Systemcontroller eingebunden
	require_once CONT_PATH.'/SystemController.class.php';
	
	/**
	 * Autoloader für Klassen mit definierter Pfad- und Namensangabe Handler/Objekt -> Klassen name Objekt_Handler
	 * @param 	string	$class
	 * @return 	mixed
	 */
	function classAutoLoader($class)
	{
		
		$classPathLib = LIB_PATH.'/'.preg_replace('/\_/', '/', $class).'.php';
		$classPathMod = MOD_PATH.'/'.preg_replace('/\_/', '/', $class).'.php';
		
		try
		{
			if(file_exists($classPathLib))
			{
				require_once $classPathLib;
				return true;
			}

			if(file_exists($classPathMod))
			{
				require_once $classPathMod;
				return true;
			}
	
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	spl_autoload_register('classAutoLoader');
	
	/**
	 * Login prüfen
	 */
	if (!isset($_SESSION['user_id']))
	{
		$controller = 'Login';
		$action = 'showLogin';
	}
	else 
	{
		
		/**
		 * Benutzer eingeloggt, Controller & action setzen
		 */
		if (isset($_REQUEST['k'])) $controller = $_REQUEST['k'];
		else $controller = 'Index';
		
		if (isset($_REQUEST['action'])) $action = $_REQUEST['action'];
		else $action = 'index';

	}

	// controller Klasse setzen
	
	$controller_class = $controller.'Controller';
	if (!file_exists(CONT_PATH.'/'.$controller_class.'.class.php')) die(_('Wrong Controller!'));
	else require_once CONT_PATH.'/'.$controller_class.'.class.php';

	try
	{		

		// Controller instanzieren und initialisieren
		$CTR = new $controller_class($controller, $action, $modul);	
		$CTR->init();

		if (!is_subclass_of($CTR, 'SystemController')) die(_('Controller could not be loaded!'));
	
		// action Methode setzen
		$action_method = $action.'Action';
		
		if (!in_array($action_method, get_class_methods(get_class($CTR)))) die(_('The requested Action doesnt exist!'));

		// Action Methode auf dem Controller aufrufen
		$CTR->$action_method();
		
	}
	catch (Exception $e)
	{
		$debug_msg = 'File: ' . $e->getFile() . "\n";
		$debug_msg .= 'Line: ' . $e->getLine() . "\n\n";
		$debug_msg .= 'Message: ' . $e->getMessage() . "\n\n";
		$debug_msg .= 'TraceString: ' . $e->getTraceAsString() . "\n\n"; 
		$debug_msg .= 'debug_backtrace: ' . print_r(debug_backtrace(), true);
	
	
		$arr_error_data = array(
				'uid' 	=> $_SESSION['uid'],
				'trace' => $debug_msg
			);

		$db = MySQLi_Database::getInstance($conf);	
		$logger = Logger_Factory::getLogger('error');
	 	$logger->log($db, $arr_error_data, $conf['global']['admin_mail']);
	}
	
?>