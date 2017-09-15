<?php
	/**
	 * Singleton für Datenbankobjekt
	 * @author David
	 */
	class MySQLi_Database
	{
		/**
		 * Objektinstanz
		 * @var DbController
		 */
		private static $instance;
		
		/**
		 * Neudeklaration verhindern
		 */
		private function __construct() {  }
		
		private function __clone() {}
		
		/**
		 * statische Methode zur Rückgabe der Objektinstanz
		 * @param	array	Konfigurationsdaten
		 * @return 	Database
		 */
		public static function getInstance($conf)
		{
			if(self::$instance === null)
			{
				self::$instance = new MySQLi_StandardObj($conf['db']['host'], $conf['db']['username'], $conf['db']['password'], $conf['db']['database'], 3306, '', $conf);	
			}
			return self::$instance;
		}
	}

?>