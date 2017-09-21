<?php
/**
 * Html Formularfelder
 * @author David Grimmer
 * @since  21.11.2012
 */ 
class Html_Forms
{
	
	public $tools = Null;
	
	private static $instance = null;
	
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
	 * zeichnet eine einzeiliges Eingabefeld
	 * @param string $name
	 * @param string $label
	 * @param string $value
	 * @param string $index
	 * @param array $arConf
	 * @param bool $display
	 */
	public function drawFormInput($name, $label, $value, $index = false, $arConf = false, $display = true) 
	{
		$content = '';
				
		if ($this->tools->isSizedString($index)) $id = preg_replace('/\[|\]/', '', $index).'_'.$name; else $id = $name;
		if ($this->tools->isSizedString($index)) $name = $index.'['.$name.']';
		
		$content .= '<div class="form_field input_field">';
		
		if ($this->tools->isSizedString($label)) $content .= '<label for="'.$name.'" '.(($arConf['bold-label'])? 'class="bold"':'').'>'.$label.':</label>';
		
		$content .= '<input type="'.((array_key_exists('type', $arConf))? $arConf['type']:'text').'" id="'.$id.'" name="'.$name.'" value="'.$this->tools->hspc($value).'" '.$this->parseFieldConf($arConf).' />';
		
		// fügt ein icon als font-icon dem content hinzu
		if (array_key_exists('font-ico', $arConf)) $content .= '<span class="fa '.$arConf['font-ico'].'"></span>';
		
		$content .= '</div>';
		
		if ($display) echo $content;
		else return $content;
	}
	
	/**
	 * zeichnet eine mehrzeiliges Eingabefeld
	 * @param string $name
	 * @param string $label
	 * @param string $value
	 * @param string $index
	 * @param array $arConf
	 * @param bool $display
	 */
	public function drawFormTextarea($name, $label, $value, $index = false, $arConf = false, $display = true)
	{
		$content = '';
	
		if ($this->tools->isSizedString($index)) $id = preg_replace('/\[|\]/', '', $index).'_'.$name; else $id = $name;
		if ($this->tools->isSizedString($index)) $name = $index.'['.$name.']';
	
		$content .= '<div class="form_field textarea_field">';
	
		if ($this->tools->isSizedString($label)) $content .= '<label for="'.$name.'" '.(($arConf['bold-label'])? 'class="bold"':'').'>'.$label.':</label>';
	
		$content .= '<textarea id="'.$id.'" name="'.$name.'" '.$this->parseFieldConf($arConf).'>'.$this->tools->hspc($value).'</textarea>';
	
		$content .= '</div>';
	
		if ($display) echo $content;
		else return $content;
	}
	
	/**
	 * zeichnet eine Checkboxfeld
	 * @param string $name
	 * @param string $label
	 * @param string $value
	 * @param string $index
	 * @param array $arConf
	 * @param bool $display
	 */
	public function drawFormCheckbox($name, $label, $value, $index = false, $arConf = false, $display = true) 
	{
		$content = '';
				
		if ($this->tools->isSizedString($index)) $id = preg_replace('/\[|\]/', '', $index).'_'.$name; else $id = $name;
		if ($this->tools->isSizedString($index)) $name = $index.'['.$name.']';
		
		$content .= '<div class="form_field checkbox_field">';
		
		if ($this->tools->isSizedString($label)) $content .= '<label for="'.$name.'" '.(($arConf['bold-label'])? 'class="bold"':'').'>'.$label.':</label>';
		
		$content .= '<input type="checkbox" id="'.$id.'" name="'.$name.'" value="'.$this->tools->hspc($value).'" '.$this->parseFieldConf($arConf).' />';
		
		$content .= '</div>';
		
		if ($display) echo $content;
		else return $content;
	}
	
	/**
	 * zeichnet einen Submit Button
	 * @param string $name
	 * @param string $value
	 * @param string $index
	 * @param array $arConf
	 * @param bool $display
	 */
	public function drawFormSubmit($name, $value, $index = false, $arConf = false, $display = true) 
	{
		$content = '';
				
		if ($this->tools->isSizedString($index)) $id = preg_replace('/\[|\]/', '', $index).'_'.$name; else $id = $name;
		if ($this->tools->isSizedString($index)) $name = $index.'['.$name.']';
		
		$content .= '<div class="form_field submit_field">';
		
		$content .= '<input type="submit" id="'.$id.'" name="'.$name.'" value="'.$this->tools->hspc($value).'" '.$this->parseFieldConf($arConf).' />';
		
		$content .= '</div>';
		
		if ($display) echo $content;
		else return $content;
	}
	
	/**
	 * zeichnet ein Passwort Feld
	 * @param string $name
	 * @param string $value
	 * @param string $index
	 * @param array $arConf
	 * @param bool $display
	 */
	public function drawFormPasswd($name, $label, $index = false, $arConf = false, $display = true) 
	{

		$content = '';
				
		if ($this->tools->isSizedString($index)) $id = preg_replace('/\[|\]/', '', $index).'_'.$name; else $id = $name;
		if ($this->tools->isSizedString($index)) $name = $index.'['.$name.']';
		
		$content .= '<div class="form_field passwd_field">';
		
		if ($this->tools->isSizedString($label)) $content .= '<label for="'.$name.'" '.(($arConf['bold-label'])? 'class="bold"':'').'>'.$label.':</label>';
		
		$content .= '<input type="password" id="'.$id.'" name="'.$name.'" '.$this->parseFieldConf($arConf).' />';
		
		$content .= '</div>';
		
		if ($display) echo $content;
		else return $content;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param $method
	 * @param $action
	 * @param $enctype
	 * @param $arConf
	 */
	public function drawFormStart($method = 'post', $action, $enctype = '', $arConf = false)
	{
		$content = '';
		
		($enctype != '')? $enctype = 'enctype="'.$enctype.'" ':$enctype = ' ';
		
		$content = '<form method="'.$method.'" action="'.$action.'" '.$enctype.''.$this->parseFieldConf($arConf).'>';
		
		return $content;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function drawFormEnde()
	{
		$content = '';
		
		$content = '</form>';
		
		return $content;
	}
	
	/**
	 * Parst den Konfigurationsarray 
	 * @param $arConf
	 */
	public function parseFieldConf($arConf)
	{
		
		if (!$this->tools->isSizedArray($arConf)) return '';
		
		if ($this->tools->isSizedString($arConf['onchange'])) $content .= ' onchange="'.$arConf['onchange'].'" ';
		if ($this->tools->isSizedString($arConf['onkeyup'])) $content .= ' onkeyup="'.$arConf['onkeyup'].'" ';
		if ($this->tools->isSizedString($arConf['style'])) $content .= ' style="'.$arConf['style'].'" ';
		if ($this->tools->isSizedString($arConf['class'])) $content .= ' class="'.$arConf['class'].'" ';
		if ($this->tools->isSizedString($arConf['rows'])) $content .= ' rows="'.$arConf['rows'].'" ';
		if ($this->tools->isSizedString($arConf['col'])) $content .= ' cols="'.$arConf['col'].'" ';	
		if ($this->tools->isSizedString($arConf['placeholder'])) $content .= ' placeholder="'.$arConf['placeholder'].'" ';
		if (array_key_exists('disabled', $arConf) && $arConf['disabled'] === true) $content .= ' disabled="disabled" ';
		if (array_key_exists('checked', $arConf) && $arConf['checked'] === true) $content .= ' checked="checked" ';
		if (array_key_exists('readonly', $arConf) && $arConf['readonly'] === true) $content .= ' readonly="readonly" ';

		return $content;
		
	} // function parseFieldConf($arConf)
}

?>