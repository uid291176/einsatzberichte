<?php 
/**
 * 
 * Enter description here ...
 * @author David Grimmer
 *
 */
class LoginController extends SystemController
{
	
	/**
	 * Zeigt das Login Formular und verarbeitet das Login
	 */
	public function showLoginAction()
	{
		
		$this->error = NULL;
		
		if (isset($_REQUEST['submit']))
		{
			$this->user_id = $this->db->fetchOne("SELECT 
												`id` 
											FROM 
												`".TBL_USER."` 
											WHERE
											 `password` = '".md5($_REQUEST['password'])."' AND
											 `username` = '".$_REQUEST['username']."' AND
											 `deleted` != '1'
										");
			
			if (!$this->user_id)
			{
				$this->error = _('Login failed!');
			}
			else
			{
				$_SESSION['user_id'] = intval($this->user_id);
				
				$this->user = User_Handler::geInstance($this->user_id, $this->db);
				
				if ($_REQUEST['k'] != 'Login' && isset($_REQUEST['k']) && $_REQUEST['action'] != 'showLogin' && isset($_REQUEST['action']))
				{
					
					//if (isset($_REQUEST['id'])) $param .= '&id='.$_REQUEST['id'];
					if (isset($_REQUEST['id'])) $this->redirect($_REQUEST['k'], $_REQUEST['action'], array('id' => $_REQUEST['id']));

				}
				else
				{	
					$this->redirect('Report', 'index'); die();
				}
				
			}
			
		}
			
		$this->title = _('Login');

		$this->render(MINI, 'Login', 'showLogin');
	
	}

	/**
	 *	Ausloggen
	 */
	public function logoutAction()
	{		 
		//Session löschen
		unset($_SESSION['user_id']);
		$this->redirect('Login', 'showLogin'); die();
		
	} 
}
?>