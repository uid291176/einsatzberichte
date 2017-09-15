<?php 
/**
 * MySQLi Erweiterung um Zusatzfunktionalität
 * wie z.B. fetchAll, fetchOne 
 * @author David Grimmer
 * @since  21.11.2012
 */
class MySQLi_StandardObj extends MySQLi
{
	protected $conf;
	
	public $lastQuery;
	
	/**
	 * Helper Functions Class
	 * @var unknown
	 */
	public $tools = NULL;
	
    public function __construct($hostname, $username = '', $passwd = '', $dbname = '', $port = 3306, $socket = '', $conf)
    {	
    	parent::__construct($hostname, $username, $passwd, $dbname, $port, $socket);
    	$this->conf = $conf;
    	
    	$this->tools = Helper_Functions::getInstance();
    }
    
    /**
	 *	Kapselung der Query-Methode
     */
	public function Query($query)
    {
        $this->real_query($query); 
        return new MySQLi_ResultObj($this);
    }
    
    /**
     * Rückgabe einer einzelnen Zeile (Assoc)
     * @param 	string	Query
     * @return 	array
     */
    public function fetchOne($query)
    {
    	$query_result = $this->real_query($query);

   	 	if($query_result === false)
		{
			$this->tools->debug_mail($query."\n\n".$this->connect_error."\n\n".print_r(debug_backtrace(), true), $this->conf['global']['admin_mail']);
			throw new Exception('Fehler: ' . __FUNCTION__.": ".$this->error."\r\n".$query);			
		}
    	
    	$result = new MySQLi_ResultObj($this);
    	
    	$arReturn = false;
    	
   	 	if ($result->num_rows > 0)
		{
			$row = $result->fetch_row();
			$arReturn = $row[0];	
		}
		
		return $arReturn;
    }
    
    /**
     * Rückgabe einer gesamten Zeile (Assoc) 
     * @param $query
     * @return unknown_type
     */
    public function fetchRow($query)
    {

    	$query_result = $this->real_query($query);
   
   	 	if($query_result === false)
		{
			$this->tools->debug_mail($query."\n\n".$this->connect_error."\n\n".print_r(debug_backtrace(), true), $this->conf['global']['admin_mail']);
			throw new Exception('Fehler: ' . __FUNCTION__.": ".$this->error."\r\n".$query);
		}
    	
    	$result = new MySQLi_ResultObj($this);	
    	
    	$arReturn = array();
		
    	if($result->num_rows > 0) 
    	{
    		$arReturn = $result->fetch_assoc();	
    	}
    	return $arReturn;
    }
    
    /**
     * Rückgabe der gesamten Ergebnismenge
     * @param	string
     * @return	array
     */
    public function fetchAssoc($query, $idfield = "")
    {	
    	
    	$this->lastQuery = $query;
    	
    	$query_result = $this->real_query($query);

   	 	if($query_result === false)
		{
			$this->tools->debug_mail($query."\n\n".$this->connect_error."\n\n".print_r(debug_backtrace(), true), $this->conf['global']['admin_mail']);			
			throw new Exception('Fehler: ' . __FUNCTION__.": ".$this->error."\r\n".$query);
		}
		
    	$result = new MySQLi_ResultObj($this);
    	
    	$arReturn = array();
			
		if ($result->num_rows > 0)
		{
			while ($row = $result->fetch_assoc())
			{

				if ($idfield != "")
				{
					$arReturn[$row[$idfield]] = $row;
				}
				else
				{
					$arReturn[] = $row;
				}

			}	
		}
		return $arReturn;
    }
    
    /**
     * @param $strQuery
     * @param $strKeyField
     * @param $strValueField
     * @return array
     */
    public function fetchAssocField($query, $strKeyField = false, $strValueField = false)
    {
    	$query_result = $this->real_query($query);
    	
   	 	if($query_result === false)
		{
			$this->tools->debug_mail($query."\n\n".$this->connect_error."\n\n".print_r(debug_backtrace(), true), $this->conf['global']['admin_mail']);
			throw new Exception('Fehler: ' . __FUNCTION__.": ".$this->error."\r\n".$query);
		}
		
    	$result = new MySQLi_ResultObj($this);
    	
    	$arReturn = array();
		while ($row = $result->fetch_array())
		{
			if ($strKeyField != false && $strValueField != false)
				$arReturn[$row[$strKeyField]] = $row[$strValueField];
			else
				$arReturn[] = $row[0];
		}
			
		return $arReturn;	
    }
    
    /**
     * INSERT - Methode
     * @param 	string	Tabellenname
     * @param 	array	Array (Schlüssel - Wert)
     * @return 	int
     */
    public function ImportQuery($table, $data)
    {
    	$strQuery = "INSERT INTO `".$table."` SET ";
			
		foreach ($data as $k => $v)
		{
			if ($v != "NOW()" && $v != "NULL" && !preg_match('/CONCAT(.*?)/', $v) && !preg_match('/FROM_UNIXTIME(\d*)/', $v))
			$v = "'".addslashes($v)."'";
				
			$strQuery .= "`".$k."` = ".$v.", ";
		} 

		$result = $this->real_query(substr($strQuery, 0, -2));
		
		$i_id = $this->insert_id;
		
		//Loggging der einzelnen Fehler
    	if($result === false && $table != TBL_ERROR_LOG && $table != TBL_DATABASE_LOG)
		{ 
			$debug_msg = 'MySQL-Fehler:' . $this->error . "\n\n" . 'QUERY: ' . substr($strQuery, 0, -2);
			
			if (!isset($_SESSION['uid']))
			{
				$uid = $this->conf['db']['username'];
			}
			else 
			{
				$uid = intval($_SESSION['uid']);
			}
			
			$arr_errors_data = array('uid' 	 => $uid,
									 'trace' => $this->escape_string($debug_msg));

			$logger = Logger_Factory::getLogger('ERROR');
			$logger->log($this, $arr_errors_data, $this->conf['global']['admin_mail']);
		}
		
   		//Logging der erfolgreich abgesetzten Statements
		if($table != TBL_DATABASE_LOG && $table != TBL_ERROR_LOG)
		{
			if (!isset($_SESSION['uid']))
			{
				$uid = $this->conf['db']['username'];
			}
			else 
			{
				$uid = intval($_SESSION['uid']);
			}
			
			$arr_database_data = array('uid'		=> $uid,
									   'tablename'	=> $this->escape_string($table),
								   	   'method' 	=> $this->escape_string('INSERT'),
		                           	   'data'	 	=> $this->escape_string(print_r($data, true)),
		                           	   'sqlquery' 	=> $this->escape_string(substr($strQuery, 0, -2)));
			
			$logger = Logger_Factory::getLogger('DATABASE');
			$logger->log($this, $arr_database_data);
		}
		
		return $i_id;
    }
    
    /**
     * UPDATE - Methode
     * @param 	string	Tabellenname
     * @param 	array 	Array (Schlüssel - Wert)
     * @param 	string	WHERE - Bedingung
     * @return 	
     */
    public function UpdateQuery($table, $data, $where)
    {
    	
   	 	$strQuery = "UPDATE `".$table."` SET ";
			
		foreach ($data as $k => $v)
		{
			if ($v != "NOW()" && $v != "NULL" && !preg_match('/CONCAT(.*?)/', $v))
			$v = "'".addslashes($v)."'";
			
			$strQuery .= "`".$k."` = ".$v.", ";
		}
				
		$result = $this->query(substr($strQuery, 0, -2)." WHERE ".$where);	
		
    	//Loggging der einzelnen Fehler
    	if((strlen($this->error) > 0 || $result === false) && $table != TBL_ERROR_LOG && $table != TBL_DATABASE_LOG)
		{ 
			
			throw new Exception('Fehler: ' . __FUNCTION__.": ".$this->error."\r\n".$query);
			
			$debug_msg = 'MySQL-Fehler:' . $this->error . "\n\n" . 'QUERY: ' . substr($strQuery, 0, -2) . " WHERE ".$where;

			if (!isset($_SESSION['uid']))
			{
				$uid = $this->conf['db']['username'];
			}
			else 
			{
				$uid = intval($_SESSION['uid']);
			}
			
			$arr_errors_data = array('uid' 	 => $uid,
									 'trace' => $this->escape_string($debug_msg));

			$logger = Logger_Factory::getLogger('ERROR');
			$logger->log($this, $arr_errors_data, $conf['global']['admin_mail']);
									
		}
		
   		//Logging der erfolgreich abgesetzten Statements
		if($table != TBL_DATABASE_LOG && $table != TBL_ERROR_LOG)
		{
			if (!isset($_SESSION['uid']))
			{
				$uid = $this->conf['db']['username'];
			}
			else 
			{
				$uid = intval($_SESSION['uid']);
			}
			
			$arr_database_data = array('uid'		=> $uid,
									   'tablename'	=> $this->escape_string($table),
								   	   'method' 	=> $this->escape_string('UPDATE'),
		                           	   'data'	 	=> $this->escape_string(print_r($data, true)),
		                           	   'sqlquery' 	=> $this->escape_string(substr($strQuery, 0, -2)." WHERE ".$where));
			
			$logger = Logger_Factory::getLogger('DATABASE');
			$logger->log($this, $arr_database_data);
		}
		
		return $result;
    }
    
    /**
     *	Rückgabe der definierten ENUM-Werte einer Spalte
     *	@param	string
     *	@param	string
     *	@return	array
     */
	public function getEnumValues($table, $field)
	{
		$row = $this->fetchRow("SHOW COLUMNS FROM $table LIKE '$field'");
		return(explode("','", preg_replace("/.*\('(.*)'\)/", "\\1", $row['Type'])));
	}
	
	/**
	 * gibt Statusinformationen zur MySQL Verbindung zurück
	 * @return array
	 */
	public function getConnectState()
	{
		return $this->get_connection_stats();
	}
	
	/**
	 * ändert die Struktur der DB
	 * @param unknown $sql
	 */
	public function dbDelta($sql)
	{
		return $this->query($sql);
	}
}
?>