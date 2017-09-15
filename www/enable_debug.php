<?php 
 /**
  * 
  * setzt ein debug Cookie
  * @var unknown_type
  */
 $result = setcookie('enable_debug', 1, time()+2592000);  /* verfällt in 30 Tagen */
 
 if ($result == true)
 {
 	echo '<span style="color:green">debug COOKIE wurde gesetzt!</span>';
 }
?>