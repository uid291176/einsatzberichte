<?php 
/**
 * 
 * FactoryClass zum erzeugen der "Logger" Objekte
 * @author david
 *
 */
class Logger_Factory
{
		/**
		 * Objektinstanz
		 * @var LoggerController
		 */
		private static $instance;
		
		/**
		 * Neudeklaration verhindern
		 */
		private function __construct() {}
		
		private function __clone() {}
		
		/**
		 * statische Methode zur Rückgabe der Objektinstanz
		 * @param	array	Konfigurationsdaten
		 * @return 	Database
		 */
		public static function getLogger($loggerClass)
		{
			switch(strtolower($loggerClass)) 
			{
				case 'error':
					$logger = Logger_Error::getInstance();
					break;
				case 'database':
					$logger = Logger_Database::getInstance();
					break;
				default: break;
			}
			return $logger;
		}
}
?>