<?php 
unset($_SESSION['UserFullName'], $_SESSION['UserGroup'], $_SESSION['LoginSuccess'],$_SESSION['UserName'],$_SESSION['LoginTries']);
defined('_VALID_SSC') or die('Restricted access');
echo message('You should now be logged out...').'<br /><br />';
?>