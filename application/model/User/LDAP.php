<?php 
/**
 * 
 * Erzeugt ein Benutzer Objekt des aktuell angemeldeten Benutzers
 * @author David
 *
 */
class User_LDAP
{
	
	/**
	 * bekommt die id des Users übergeben
	 * @var mixed
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
	 * enthält die Klasse für Hilfsfunktionen und debug
	 * @var object
	 */
	public $tools = null;
	
	/**
	 * Neudeklaration verhindern
	 */
	public function __construct($uid)
	{
		$this->uid = $uid;
		
		$this->tools = Helper_Functions::getInstance();
	}
	
	private function __clone() {}
	
	/**
	 * statische Methode zur Rückgabe der Objektinstanz
	 * @param	array	Konfigurationsdaten
	 * @return 	User
	 */
	public static function geInstance($uid) 
	{
		
		if(self::$instance === null) 
		{
			self::$instance = new self($uid);
		}
		return self::$instance;
	}
	
	/**
	 * Daten des Benutzers laden
	 */
	public function init()
	{
		
		$this->userData['username'] 	= $_SESSION['current_user']['username'];
		$this->userData['name'] 		= $_SESSION['current_user']['name'];
		$this->userData['vorname'] 		= $_SESSION['current_user']['vname'];
		$this->userData['email'] 		= $_SESSION['current_user']['mail'];
		
		$arrStrGroups 					= $_SESSION['current_user']['groups'];
		
		if ($this->tools->isSizedArray($arrStrGroups))
		{
			foreach ($arrStrGroups as $strGroup)
			{
				$group = explode(',', $strGroup);
				$group = str_replace('CN=', '', $group[0]);
					
				$arrOutputGroups[] = strtolower($group);
			}
			$this->userData['groups'] = $arrOutputGroups;
		}
		//$this->tools->debug($this->userData);
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
	 * gibt das SYS Admin Flag zurück
	 * mapping = AD Gruppe
	 * 		Einsatzberichte SysAdmins
	 * @return boolean
	 */
	public function isAdmin()
	{
		if (in_array('einsatzberichte sysadmins', $this->userData['groups'])) return true;
		else return false;;
	
	} // public function isAdmin()
	
	/**
	 * gibt ein array der AD Benutzerrechte für die Module zurück
	 * mapping = AD Gruppe <-> ModulID
	 * 		Rettungsdienst 			<-> rd:1
	 * 		Einsatzberichte 		<-> eb:1
	 * 		Einsatzberichte Admin 	<-> ebadm:1
	 * @return array
	 */
	public function getArrACL()
	{
		
		if (in_array('rettungsdienst', $this->userData['groups'])) $arrReturn[] = 'rd:1';
		else $arrReturn[] = '0';
		
		if (in_array('einsatzberichte', $this->userData['groups'])) $arrReturn[] = 'eb:1';
		else $arrReturn[] = '0';
		
		if (in_array('einsatzberichte admins', $this->userData['groups'])) $arrReturn[] = 'ebadm:1';
		else $arrReturn[] = '0';
		
		return $arrReturn;
	
	} // public function getArrACL()

	
}
?>