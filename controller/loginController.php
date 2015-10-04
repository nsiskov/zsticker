<?php
define("REMEMBERME",     "31536000");
define("FIELD_USERNAME",     "username");
define("FIELD_PASSWORD",     "password");
define("FIELD_REMEMBERME",     "rememberMe");

require_once 'abstractController.php';
require_once 'model/user.php';
require_once 'model/album.php';
class LoginController extends AbstractController {
  
  function requiresLogin() {
    return false;
  }
  
	function processInternal($urlRewrite) {
		$showError = false;
		if (isset($_POST['login'])) {
			$username = $_POST[FIELD_USERNAME];
			$password = $_POST[FIELD_PASSWORD];
			$user = $this->login($username, $password);
			if ($user != null) {
				$_SESSION['user'] = $user;
				if (isset($_POST[FIELD_REMEMBERME])) {
					$this->handleRememberMe($user);
				}
			} else {
				$showError = true;
			}
		}
		
		if (isset($_GET['logout']) && parent::isLoggedIn()) {
			//unset cookie
			setcookie(COOKIE_HASH, "", 0);
			unset($_SESSION['user']);
		}

		if (parent::isLoggedIn()) {
			//redirect to the dashboard
			header('Location: ' . $urlRewrite->r('entry.php?t=dashboard'));
			return array('render'=>false);
		} else {
			//display the login page
			return array('render'=>true, 'showError'=>$showError);
		}
	}
	
	private function handleRememberMe($user) {
		//generate hash
		//$authHash = md5($username . $user->getUserId()) . md5($password) . md5(time());
		$authHash = md5($user->userName . authSalt);
		$user->authHash = $authHash;
		//set cookie
		
		$hour = time() + REMEMBERME;
		setcookie(COOKIE_HASH, $authHash, $hour);
		setcookie(COOKIE_USERNAME, $user->userName, $hour);
	}

  private function login($username, $password) {
    $connection = parent::getConnection();
  
    $sql = "Select * from user where username = '"
        . mysqli_escape_string($connection, $username)
        . "' and password='" . mysqli_escape_string($connection, $password) . "'";
  
    $sql_result=mysqli_query($connection, $sql)
    		or exit("Sql Error".mysqli_error($connection));
    
    while($sql_row = mysqli_fetch_array($sql_result)) {
    	$albumSql = "Select a.* from album a,albumuser au where a.id=au.idalbum and au.iduser=" . $sql_row['id'];
    	$sql_albumResult=mysqli_query($connection, $albumSql)
    		or exit("Sql Error".mysqli_error($connection));
    	
    	$albums = array();
    	
    	while ($sql_albumRow = mysqli_fetch_array($sql_albumResult)) {
    		$album = new Album($sql_albumRow['id'], $sql_albumRow['key'], $sql_albumRow['stickercount']);
    	  $albums[$sql_albumRow['id']] = $album;
    	}
    	
    	$user = new User($sql_row['id'], $sql_row['username'],
    			 $sql_row['firstname'], $sql_row['lastname'], $albums);
      return $user;
    }
  
    return null;
  }
}
?>