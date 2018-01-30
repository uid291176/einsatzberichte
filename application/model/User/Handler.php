<?php 
/**
 * 
 * Erzeugt ein Benutzer Objekt des aktuell angemeldeten Benutzers
 * @author David
 *
 */
class User_Handler
{
	
	/**
	 * MySQL DB Objekt
	 * @var object
	 */
	protected $db = null;
	
	/**
	 * bekommt die id des Users übergeben
	 * @var mixed
	 */
	protected $uid = '';
	
	/**
	 * enthällt die Daten des angemeldeten Benutzers
	 * @var array
	 */
	public $userData = array();
	
	/**
	 * Objektinstanz
	 * @var UserObjekt
	 */
	private static $instance;
	
	/**
	 * Neudeklaration verhindern
	 */
	public function __construct($uid, $db)
	{
		$this->uid = $uid;
		$this->db = $db;
		
		$this->tools = Helper_Functions::getInstance();
	}
	
	private function __clone() {}
	
	/**
	 * statische Methode zur Rückgabe der Objektinstanz
	 * @param	array	Konfigurationsdaten
	 * @return 	User
	 */
	public static function geInstance($uid, $db) 
	{
		
		if(self::$instance === null) 
		{
			self::$instance = new self($uid, $db);
		}
		return self::$instance;
	}
	
	/**
	 * Daten des Benutzers laden
	 */
	public function init()
	{
		
		$this->userData = $this->db->fetchRow("SELECT 
													`id`, `username`, `name`, `vorname`, `email`, `status`, `deleted`, acl_module 
												FROM 
													`".TBL_USER."` 
												WHERE
												 `id` = '".intval($this->uid)."' AND
												 `deleted` != '1'
											");
		
		$arrACLs = explode('|', $this->userData['acl_module']);
		
		if (in_array('rd:1', $arrACLs)) $this->userData['rettungsdienst'] = '1';
		else $this->userData['rettungsdienst'] = false;
		
		if (in_array('eb:1', $arrACLs)) $this->userData['einsatzberichte'] = '1';
		else $this->userData['einsatzberichte'] = false;
		
		if (in_array('ebadm:1', $arrACLs)) $this->userData['einsatzberichte_admin'] = '1';
		else $this->userData['einsatzberichte_admin'] = false;
		
	} // public function init()
	
	/**
	 * liefert den Inhalt von $key des Benutzers
	 * @param $key string
	 */
	public function get($key)
	{
		if (array_key_exists($key, $this->userData)) return $this->userData[$key];
		else return 'key '.$key.' does not exists!';
		
	} // public function get($key)
	
	/**
	 * speichert einen neuen Wert für den aktuellen Benutzer
	 * @param string $key
	 * @param string $val
	 */
	public function set($key, $val)
	{
		
		if (array_key_exists($key, $this->userData))
		{
			$arUpdtae = array(
				$key => $val
			);
			$this->db->UpdateQuery(TBL_USER, $arUpdtae, "`id` = '".$this->uid."'");
		}
		
	} // public function set($key, $val)
	
	/**
	 * gibt das Admin Flag zurück
	 * @return boolean
	 */
	public function isAdmin()
	{
		if ($this->userData['status'] == 1) return true;
		else return false;
		
	} // public function isAdmin()
	
	/**
	 * gibt ein array der Benutzerrechte für die Module zurück
	 * @return array
	 */
	public function getArrACL()
	{
		return explode('|', $this->userData['acl_module']);
		
	} // public function getArrACL()
	
	
}
?>