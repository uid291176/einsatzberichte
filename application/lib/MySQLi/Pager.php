<?php
/**
 * Klasse zur Nutzung der Paging-Funktion für Arrays
 * @author 	david
 * @since	2016-03-04
 */ 
class MySQLi_Pager 
{
	/**
	 * Instanzobjekt
	 * @var MySQLi_Pager
	 */
	private $instance;
	
	/**
	 * Anzahl der dargestellten Seiten 
	 * zu denen direkt navigiert werden kann
	 * @var int
	 */
	protected $_range;
	
	/**
	 * Anzahl der Seiten für die gesamte Datenmenge
	 */
	protected $_pages;
	
	/**
	 * Anzahl Einträge je Seite
	 */
	protected $_entrys;
	
	/**
	 * aktuelle Seite
	 * @var string 
	 */
	public $_actpage;
	
	/**
	 * enthält die URLs der Seiten
	 * @var string url
	 */
	protected $_url;
	
	/**
	 * enthält das HTML des gesamten Pagers
	 * @var text
	 */
	protected $_markup;
	
	// debug Tool, Hilfsfunktionen
	public $tools;
	
	public function __construct($intPages, $intPageEntrys, $intPageRange)	
	{
		
		$this->_pages 	= $intPages;
		$this->_range 	= $intPageRange;
		$this->_entrys 	= $intPageEntrys;
				
		$this->tools = Helper_Functions::getInstance();
		
	}
	
	/**
	 * aktuelle Seite die angezeigt wird
	 * @param unknown_type $page
	 */
	public function setActivePage($page)
	{
		$this->_actpage = $page;
		
	} // public function setActivePage($page)	
	
	/**
	 * setzt die Parameter der URLs (Pager Links)
	 * @param array $arParamRequest
	 */
	public function setUrl($arrParamRequest)
	{
		$this->_url = '/index.php?';
		$this->_url .= http_build_query($arrParamRequest);
		
	} // public function setUrl($arrParamRequest)
	
	/**
	 * zeichnet den Pager an entsprechender Stelle
	 * return string (HTML)
	 */
	public function renderPager()
	{
		
		if ($this->_pages <= 1) return '';
		
		ob_start();
		include VIEW_PATH.'/pager.phtml';
		
		$inc = ob_get_contents();
		
		ob_end_clean();
		
		return $inc;
		
	} // public function renderPager()

}