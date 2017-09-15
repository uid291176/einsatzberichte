<?php 
/**
 * 
 * Config Controller der APP
 * @author David Grimmer
 *
 */
class ConfigController extends SystemController
{
	
	public function indexAction()
	{
		$this->render();
	}
	
	public function saveAction()
	{

		if (isset($_REQUEST['save_config']))
		{
			
			$arUpdate = array(
				'logo_img' 						=> $this->tools->q($_REQUEST['logo_img']),
				'upload_path' 					=> $this->tools->q($_REQUEST['upload_path']),
				'system_title' 					=> $this->tools->q($_REQUEST['system_title']),
				'page_entrys' 					=> $this->tools->q($_REQUEST['page_entrys']),
				'page_range' 					=> $this->tools->q($_REQUEST['page_range']),
				'laenge' 						=> $this->tools->q($_REQUEST['laenge']),
				'gewicht' 						=> $this->tools->q($_REQUEST['gewicht']),
				'waehrung' 						=> $this->tools->q($_REQUEST['waehrung']),
				'landkreis_kreisfreie_stadt' 	=> $this->tools->q($_REQUEST['landkreis_kreisfreie_stadt']),
				'gemeinde' 						=> $this->tools->q($_REQUEST['gemeinde']),
				'gemeinde_ident_nr' 			=> $this->tools->q($_REQUEST['gemeinde_ident_nr'])
			);
			
			foreach ($arUpdate as $attr=>$val)
			{
				$result = $this->db->FetchOne("SELECT `id` FROM `".TBL_CONFIG."` WHERE `key` = '".$attr."'");

				if (intval($result) > 0)
				{
					$this->db->UpdateQuery(TBL_CONFIG, array('value' => $val), "`id` = '".$result."'");
				}
				else 
				{
					$this->db->ImportQuery(TBL_CONFIG, array('key' => $attr, 'value' => $val));
				}
			}
			$this->setPosMessage(_('Daten wurden erfolgreich gespeichert!'));
		}
		$this->redirect('Config', 'index'); die();
	}

}
?>