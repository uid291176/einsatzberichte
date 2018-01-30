<?php 
/**
 * 
 * Enter description here ...
 * @author David Grimmer
 *
 */
class LoginController extends SystemController
{
	
	public function init()
	{
		parent::init();
	
		$this->title = _('Login');
	}
	
	/**
	 * Zeigt das Login Formular und verarbeitet das Login
	 */
	public function showLoginAction()
	{
		
		$this->error = NULL;
		
		if (isset($_REQUEST['submit']))
		{
			
			if ($this->getConf('use_ldap') == '1')
			{
				// ldap Nutzer Verwaltung (WINDOWS AD)
				if ($connect = ldap_connect('ldap://'.$this->getConf('ldap_address').'.'.$this->getConf('ldap_domain'), $this->getConf('ldap_port')))
				{
					
					$arrLdapUser = $this->getLdapUserData($connect);
						
					$_SESSION['current_user'] = $arrLdapUser;
						
					$this->user_id = $arrLdapUser['username'];

					ldap_close($connect);
					
				}
				
			}
			else 
			{
				
				// lokale Nutzerverwaltung
				$this->user_id = $this->db->fetchOne("SELECT
												`id`
											FROM
												`".TBL_USER."`
											WHERE
											 `password` = '".md5($_REQUEST['password'])."' AND
											 `username` = '".$_REQUEST['username']."' AND
											 `deleted` != '1'
										");
				
				
			}
			
			if (!$this->user_id)
			{
				$this->error = _('Login failed!');
			}
			else
			{

				$_SESSION['user_id'] = $this->user_id;
			
				if ($_REQUEST['k'] != 'Login' && isset($_REQUEST['k']) && $_REQUEST['action'] != 'showLogin' && isset($_REQUEST['action']))
				{
						
					//if (isset($_REQUEST['id'])) $param .= '&id='.$_REQUEST['id'];
					//if (isset($_REQUEST['id'])) $this->redirect($_REQUEST['k'], $_REQUEST['action'], array('id' => $_REQUEST['id']));
					
					$this->redirect($_REQUEST['k'], $_REQUEST['action'], array());
			
				}
				else
				{
					//$this->redirect('Index', 'index'); die();
					$this->redirect('Report', 'index'); die();
				}
			
			}
		}
		$this->render(MINI, 'Login', 'showLogin');
	
	}
	
	protected function getLdapUserData($connect)
	{
		
		ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
			
		$ldap_user 			= trim($_REQUEST['username']).'@'.$this->getConf('ldap_domain');
		$ldap_user_passwd 	= trim($_REQUEST['password']);
			
		if ($bind = @ldap_bind($connect, $ldap_user, $ldap_user_passwd))
		{
		
			$arrDomain = explode('.', $this->getConf('ldap_domain'));
			$dn = "DC=".$arrDomain[0].",DC=".$arrDomain[1];
		
			$person = trim($_REQUEST['username']);
			$fields = "(|(samaccountname=*$person*))";
		
			// Ergebnis der ersten LDAP Anfrage
			$search = ldap_search($connect, $dn, $fields);
			$result = ldap_get_entries($connect, $search);
		
			if ($this->tools->isSizedArray($result[0]))
			{
				$arrLdapUser = array(
		
						'displayname'			=> $result[0]['name'][0],
						'name'					=> $result[0]['sn'][0],
						'vname'					=> $result[0]['givenname'][0],
						'username'				=> $result[0]['samaccountname'][0],
						'mail'					=> $result[0]['mail'][0],
						'useraccountcontrol'	=> $result[0]['useraccountcontrol'][0]
				);
			}
		
			// ermitteln der Gruppen des jeweiligen users
			// source: https://samjlevy.com/php-ldap-membership/
		
			$output = $result[0]['memberof'];
			$token = $result[0]['primarygroupid'][0];
		
			array_shift($output);
		
			$dn = "CN=Users,DC=".$arrDomain[0].",DC=".$arrDomain[1];
		
			$results2 = ldap_search($connect, $dn, "(objectcategory=group)", array("distinguishedname","primarygrouptoken"));
			$entries2 = ldap_get_entries($connect, $results2);
		
			array_shift($entries2);
		
			// Loop through and find group with a matching primary group token
			foreach($entries2 as $e)
			{
				if($e['primarygrouptoken'][0] == $token)
				{
					// Primary group found, add it to output array
					$output[] = $e['distinguishedname'][0];
					// Break loop
					break;
				}
			}
			$arrLdapUser['groups'] = $output;
			
			return $arrLdapUser;
		}
		
	}

	/**
	 *	Ausloggen
	 */
	public function logoutAction()
	{		 
		//Session löschen
		unset($_SESSION['user_id']);
		unset($_SESSION['current_user']);
		
		$this->redirect('Login', 'showLogin'); die();
		
	} 
}
?>