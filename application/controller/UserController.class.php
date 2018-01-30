<?php
/**
 * 
 * Enter description here ...
 * @author uid291176
 *
 */
class UserController extends SystemController
{
	
	public function init()
	{
		parent::init();
		
		$this->title = _('Benutzerverwaltung');
	}
	
	
	/**
	 * zeigt die Benutzerübersicht an
	 */
	public function indexAction()
	{
		
		
		// dyn. "WEHRE" Erzeugung
		$where = 'WHERE 1 ';
		if (isset($_REQUEST['status'])) $where .= "AND `status` = '".$this->tools->q($_REQUEST['status'])."'";
		
		// dyn. "ORDER BY" Erzeugung
		$order = 'ORDER BY `id` ASC';
		if (isset($_REQUEST['order_by'])) $order = 'ORDER BY `'.$this->tools->q($_REQUEST['order_by']).'` ASC';
		
		//TODO OrderDirection noch dynamisieren...
		
		// Anzahl der berechtigten Nutzer
		$countUser = $this->db->fetchOne("SELECT COUNT(*) FROM `".TBL_USER."` ".$where." AND `deleted` = '0' ".$order."");
		
		// Anzahl der Seiten für die gewünschte Ergebnismenge
    	$pages = ceil(intval($countUser) / $this->pageEntrys);
    	
    	// Offset ab welchen die Datensätze aus der DB geholt werden
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $offset = ($_REQUEST['pPage'] - 1) * $this->pageEntrys;
    	else $offset = 0;
    	
    	// bis zu diesem Eintrag der Ergebnismenge werden die Daten gesammelt
    	if ($offset > 0) $limit = $_REQUEST['pPage'] * $this->pageEntrys;
    	else $limit = $this->pageEntrys;
    	
    	if (isset($_REQUEST['pPage']) && intval($_REQUEST['pPage']) > 0) $currentPage = intval($_REQUEST['pPage']);
    	else $currentPage = 1;
    	
    	// Array der Nutzer
		$arUser = $this->db->fetchAssocField("SELECT `id` FROM `".TBL_USER."` ".$where." AND `deleted` = '0' ".$order." LIMIT ".$limit." OFFSET ".$offset."");
		
		foreach ($arUser as $uid)
		{
			$this->view['data'][$uid] = new User_Handler($uid, $this->db);
			$this->view['data'][$uid]->init();
		}
		
		// set Pager
    	$PAGER = new MySQLi_Pager($pages, $this->pageEntrys, $this->pageRange);
    	$PAGER->setActivePage($currentPage);
    	$PAGER->setUrl(array(
				'k' 				=> $_REQUEST['k'], 					// Controller
				'action' 			=> $_REQUEST['action'], 			// Action
				'period' 			=> $_REQUEST['period'], 			// Zeitraum
				'order_by' 			=> $_REQUEST['order_by'], 			// Sortierung nach
				'order_direction' 	=> $_REQUEST['order_direction'], 	// Sortierrichtung
			));
			
		// Pager Ausgabe über OUTPUT BUFFER -> Template pager.phtml
    	if ($this->tools->isSizedArray($this->view['data'])) $this->view['pager'] = $PAGER->renderPager();
		
		// Anzahl User Administartoren
		$arCount = $this->db->fetchAssocField("SELECT COUNT(*) FROM `".TBL_USER."` WHERE 1 AND `status` = '1' AND `deleted` = '0'");
		$this->view['count']['admin'] = $arCount[0];
		
		// Anzahl User normal
		$arCount = $this->db->fetchAssocField("SELECT COUNT(*) FROM `".TBL_USER."` WHERE 1 AND `status` = '0' AND `deleted` = '0'");
		$this->view['count']['user'] = $arCount[0];
		
		$this->title = _('Benutzerverwaltung');
		
		$this->render();
		
	}// public function indexAction()
	
	/**
	 * zeigt eine Formular zum editieren eines Benutzers
	 */
	public function editAction()
	{
		$uid = $this->tools->q($_REQUEST['uid']);

		if (intval($uid) > 0)
		{
			$this->view['data'] = new User_Handler($uid, $this->db);
			$this->view['data']->init();
		}
		
		$this->render();
		
	}//public function editAction()
	
	/**
	 * zeigt ein Formular zum Anlegen eines Benutzers
	 * @param string $data
	 */
	public function addAction()
	{
		
		$this->render();
		
	}// public function addAction()
	
	/**
	 * zeigt ein Formular zum Bearbeiten des angemeldeten Benutzers
	 * @param string $data
	 */
	public function profilAction()
	{
		
		$uid = $this->tools->q($_REQUEST['uid']);
		
		if (intval($uid) > 0)
		{
			$this->view['data'] = new User_Handler($uid, $this->db);
			$this->view['data']->init();
		}
		$this->render();
	
	}// public function profilAction()
	
	/**
	 * setzt einen Benutzer auf Status "deleted"
	 */
	public function deleteAction()
	{

		$uid = $this->tools->q($_REQUEST['uid']);
		$USER = new User_Handler($uid, $this->db);
		$USER->init();
		$USER->set('deleted', '1');
		$this->setPosMessage(_('Benutzer wurde entfernt!'));
		$this->redirect('User', 'index'); die();
		
	}//public function deleteAction()
	
	/**
	 * speichert die User Daten
	 */
	public function saveAction()
	{
		
		$updateID = $this->tools->q($_REQUEST['uid']);
		
		// Zugriffsrechte der Module sichern
		$arrACL = array();
		
		if ($this->tools->isSizedString($_REQUEST['rettungsdienst'])) $arrACL[] = 'rd:1';
		else $arrACL[] = '0';
		
		if ($this->tools->isSizedString($_REQUEST['einsatzberichte'])) $arrACL[] = 'eb:1';
		else $arrACL[] = '0';
		
		if ($this->tools->isSizedString($_REQUEST['einsatzberichte_admin'])) $arrACL[] = 'ebadm:1';
		else $arrACL[] = '0';
		
		$strACL = implode('|', $arrACL);
		
		// Benutzerdaten für "add/ update" Methoden
		$arDataRequest = array(
			'name' 			=> $this->tools->q($_REQUEST['name']),
			'vorname' 		=> $this->tools->q($_REQUEST['vorname']),
			'email' 		=> $this->tools->q($_REQUEST['email']),
			'username' 		=> $this->tools->q($_REQUEST['username']),
			'status' 		=> ($this->tools->isSizedString($_REQUEST['status']))? intval($this->tools->q($_REQUEST['status'])):0,
			'passwd1' 		=> $this->tools->q($_REQUEST['passwd1']),
			'passwd2' 		=> $this->tools->q($_REQUEST['passwd2']),
			'acl_module' 	=> $strACL
		);
		
		if (isset($_REQUEST['submit_save']) || isset($_REQUEST['submit_save_return']))
		{
			// User update als Admin
			if ($_REQUEST['cmd'] == 'edit' && intval($updateID) > 0)
			{
				$uid = $this->userUpdateAction($updateID, $arDataRequest);

				if (isset($_REQUEST['submit_save_return'])) $this->redirect('User', 'index');
				else $this->redirect('User', 'edit', array('uid' => $uid)); die();
			}
			
			// neuen Benutzer erzeugen
			if ($_REQUEST['cmd'] == 'create')
			{
				$this->userCreateAction($arDataRequest);
			}
			
			// Profildaten update als der jeweils angemeldete Benutzer
			if ($_REQUEST['cmd'] == 'profil')
			{
				$arDataRequest['status'] = $this->user->get('status'); // Admin Status zurücksichern
				$arDataRequest['acl_module'] = $this->user->get('acl_module'); // ACLs zurücksichern
				
				$uid = $this->userUpdateAction($updateID, $arDataRequest);
				
				$this->redirect('User', 'profil', array('uid' => $uid)); die();
				
			}
			
		}
		
	}//public function saveAction()
	
	/**
	 * führt Aktionen der Übersicht- Tabelle aus
	 */
	public function tableAction()
	{	
		
		if (isset($_REQUEST['submit_table_action']) &&
			$_REQUEST['submit_table_action'] == 'übernehmen')
		{
			
			/**
			 * construct für spätere multi- action Aufrufe
			 */
			switch ($_REQUEST['table-action'])
			{
				
				case 'del':
					
					if ($this->tools->issizedArray($_REQUEST['check']))
					{
						foreach ($_REQUEST['check'] as $uid)
						{
							$uid = $this->tools->q($uid);
							$USER = new User_Handler($uid, $this->db);
							$USER->init();
							$USER->set('deleted', '1');
						}
						$this->setPosMessage(_('Aktion wurde erfolgreich ausgeführt!'));
					}
					break;
			}
			$this->redirect('User', 'index');
		}
		
	} // public function contentAction()
	
	/**
	 * aktualisiert die Daten eines Benutzers
	 * @param int $uid
	 * @param array $data
	 */
	protected function userUpdateAction($uid, $data)
	{
		
		$USER = new User_Handler($uid, $this->db);
		$USER->init();
		$USER->set('name', $data['name']);
		$USER->set('vorname', $data['vorname']);
		$USER->set('email', $data['email']);
		$USER->set('status', $data['status']);
		$USER->set('acl_module', $data['acl_module']);

		$passwd = $data['passwd1'];
		
		if ($data['passwd2'] != '' && $data['passwd2'] == $passwd)
		{
			$USER->set('clear', $passwd);
			$USER->set('password', md5($passwd));
			
			$this->setPosMessage(_('Ein neues Passwort wurde gespeichert!'));
		}
		else 
		{
			$this->setPosMessage(_('Daten wurden erfolgreich gespeichert!'));
		}
		
		return $uid;
		
	}// protected function userUpdateAction($uid, $data)
	
	/**
	 * erzeugt einen neuen Benutzer im System
	 * @param array $data
	 */
	protected function userCreateAction($data)
	{
		$arError = array();
		
		// Benutzername schon vergeben
		$userExist = $this->db->fetchAssocField("SELECT `username` FROM `".TBL_USER."` WHERE `username` = '".$data['username']."'");
		if ($data['username'] == $userExist[0])
		{
			$arError['username_exist'] = true;
			$this->setNegMessage(_('Ein Benutzer mit dem Benutzername <i><strong>'.$data['username'].'</strong></i> existiert schon.'));
		}
		
		// Validierung email
		if ($this->tools->isSizedString($data['email']) && strpos($data['email'], '@') > 0)
		{
			// E-Mail schon im System
			$emailExist = $this->db->fetchAssocField("SELECT `email` FROM `".TBL_USER."` WHERE `email` = '".$data['email']."'");
			if ($data['email'] == $emailExist[0])
			{
				$arError['email_exist'] = true;
				$this->setNegMessage(_('Ein Benutzer mit der E-Mail <i><strong>'.$data['email'].'</strong></i> existiert schon.'));
			}
			
			$importEmail = $data['email'];
		}
		else 
		{
			$arError['email_empty'] = true;
			$this->setNegMessage(_('Die angegebene E-Mail Adresse ist ungültig.'));
		}
		
		$arImport = array(	'name'			=> $data['name'],
							'vorname'		=> $data['vorname'],
							'username' 		=> $data['username'],
							'email'			=> $importEmail,
							'status'		=> $data['status'],
							'acl_module' 	=> $data['acl_module'],
							'deleted'		=> '0'
					);
		
		if (strlen($arImport['username']) < 5)
		{	
			$arError['username_len'] = true;
			$this->setNegMessage(_('Der angegebene Benutzername muss mind. 5 Zeichen lang sein!'));
		}
		
		$passwd = $data['passwd1'];
		
		if ($data['passwd2'] == $passwd)
		{
			$arImport['clear'] = $passwd;
			$arImport['password'] = md5($passwd);
		}
		else
		{
			$arError['diff_passwd'] = true;
			$this->setNegMessage(_('Die eingegebenen Passwörter stimmen nicht überein!'));
		}
		
		if ($this->tools->isSizedArray($arError))
		{
			
			$this->view['data'] = $data;
			$this->view['data']['error'] = $arError;
			
			$this->render(NULL, 'User', 'add'); die();
		}
		else 
		{
			$this->setPosMessage(_('Neuer Benutzer wurden erfolgreich angelegt!'));
			$uid = $this->db->ImportQuery(TBL_USER, $arImport);
			
			$this->redirect('User', 'edit', array('uid' => $uid)); die();
		}	
			
	}//protected function userCreateAction($data)

}

?>