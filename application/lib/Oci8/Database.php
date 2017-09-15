<?php

	/**
	 * Oracle Datenbankverbindungsklasse
	 * @author David
	 */
	class Oci8_Database
	{
		
		private $oracle_user 			= 'ERFURT_DATA_BIRT';
		private $oracle_passwd 			= 'siemens_2015';
		private $oracle_conn_string 	= '10.117.135.172/ELS416'; // connection string aus "tnsnames.ora"
		private $oracle_character_set 	= 'AL32UTF8';
		private $conn 					= null;
		
		public function __construct()
		{
			
			$this->conn = oci_pconnect($this->oracle_user, $this->oracle_passwd, $this->oracle_conn_string, $this->oracle_character_set);
			
			// Abbruch bei Fehlerhaften Connect
			if (!$this->conn) { $m = oci_error(); die($m['message']); }

		}
		
		/**
		 * Rückgabe der gesamten Ergebnismenge
		 * @param string $query
		 * @return array $return
		 */
		public function fetchAssoc($query)
		{
			
			$result = oci_parse($this->conn, $query);
	    	//die(var_dump(oci_execute($result)));
	    	if (oci_execute($result))
	    	{
	    		
		    	while( $row = oci_fetch_assoc($result) ) {
		    		
		    		$return[] = $row;
		    	}
	    	}
	    	else 
	    	{
	    		
	    		$return = _('ERROR::DB Abfrage "fetchAssoc" konnte nicht ausgeführt werden');
	    		
	    	}
	    	
	    	return $return;
	    	
		} // public function fetchAssoc($query)
		
		/**
		 * Rückgabe der Ergebnismenge eines Datensatzes (Zeile)
		 * @param string $query
		 * @return array $return
		 */
		public function fetchRow($query)
		{
			
			$result = oci_parse($this->conn, $query);
	    	//die(var_dump(oci_execute($result)));
	    	if (oci_execute($result))
	    	{
	    		
		    	while( $row = oci_fetch_assoc($result) ) {
		    		
		    		$return = $row;
		    	}
	    	}
	    	else 
	    	{
	    		
	    		$return = _('ERROR::DB Abfrage "fetchRow" konnte nicht ausgeführt werden');
	    		
	    	}
	    	
	    	return $return;
	    	
		} // public function fetchAssoc($query)
		
		/**
		 * gibt die Anzahl betroffener Zeilen/ Datensätze in der DB zurück
		 * @param string $query
		 * @return int Rows
		 */
		public function numRows($query)
		{
			$number_of_rows = 0;
			
			$result = oci_parse($this->conn, $query);
			
			oci_define_by_name($result, 'COUNTDAY', $number_of_rows);
			
			oci_execute($result);

			oci_fetch($result);
			
			if ($number_of_rows > 0) return $number_of_rows;
	    	else return '0';
	    	
		} // public function numRows($query)
		
		
		
	}

?>