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
	 * bekommt das DB Objekt übergeben
	 * @var object
	 */
	protected $db = null;
	
	/**
	 * bekommt die id des Users übergeben
	 * @var int
	 */
	protected $uid = null;
	
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
		$this->db = $db;
		$this->uid = $uid;
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
													* 
												FROM 
													`".TBL_USER."` 
												WHERE
												 `id` = '".$this->uid."' AND
												 `deleted` != '1'
											");
	}
	
	/**
	 * liefert den Inhalt von $key des Benutzers
	 * @param $key string
	 */
	public function get($key)
	{
		if (array_key_exists($key, $this->userData)) return $this->userData[$key];
		else return 'key '.$key.' does not exists!';
	}
	
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
	}
	
	public function isAdmin()
	{
		if ($this->userData['status'] == 1) return true;
		else return false;
	}
	
}
?>