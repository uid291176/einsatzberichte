<?php
/**
 * Logging der Datenbankoperationen (UPDATE, IMPORT)
 * @author David Grimmer
 * @since  21.11.2012
 */ 
class Logger_Database
{
	private static $instance = null;
	
	protected function __construct() {}
	
	private function __clone() {}
	
	public static function getInstance() 
	{
		if(self::$instance === null) 
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Methode für Logging
	 * @param	object	Datenbankobjekt
	 * @return	boolean
	 */
	public function log($db, $data) 
	{
		$db->ImportQuery(TBL_DATABASE_LOG, $data);
	}
}

?>