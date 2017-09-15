<?php
/**
 * Logging der Fehler
 * @author David Grimmer
 * @since  21.11.2012
 */ 
class Logger_Error
{
	private static $instance = null;
	
	public $tools = Null;
	
	protected function __construct() { $this->tools = Helper_Functions::getInstance(); }
	
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
	public function log($db, $data, $admin_mail = '') 
	{
		$db->ImportQuery(TBL_ERROR_LOG, $data);
		$this->tools->debug_mail($data, $admin_mail);
	}
}

?>