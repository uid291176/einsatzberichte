<?php 
/**
 * 
 * FactoryClass zum erzeugen der "Html" Objekte
 * @author david
 *
 */
class Html_Factory
{
		/**
		 * Objektinstanz
		 * @var HtmlController
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
		 * @return 	Html
		 */
		public static function getHtml($htmlClass)
		{
			switch(strtolower($htmlClass)) 
			{
				case 'forms':
					$html = Html_Forms::getInstance();
					break;
				default: break;
			}
			return $html;
		}
}
?>