<?php
require_once 'util/dbutil.php';
define("COOKIE_HASH",    "sRnd");
define("COOKIE_USERNAME",    "sUnme");
define("authSalt", "nakoSiskovKavadarciMacedonia");

abstract class AbstractController {
  function process($urlRewrite) {
    
    $this->loginFromCookie();
    
    if ($this->requiresLogin()) {
      if (!$this->isLoggedIn()) {
        header('Location: ' . $urlRewrite->r('entry.php?t=login'));
        return array('render'=>false);
      }
    }
    
    return $this->processInternal($urlRewrite);
  }
  
  function loginFromCookie() {
    if (!$this->isLoggedIn() && isset($_COOKIE[COOKIE_HASH]) ) {
      $authHash = $_COOKIE[COOKIE_HASH];
      $authUsername = $_COOKIE[COOKIE_USERNAME];
      $user = $this->login($authHash, $authUsername);
      if ($user != null) {
        $_SESSION['user'] = $user;
      }
    }
  }
  
  private function login($authHash, $authUsername) {
  	
  	$testHash = md5($authUsername . authSalt);
  	if ($testHash != $authHash) {
  		return null;
  	}
  	
  		//the cookie is correct
    $connection = $this->getConnection();
  
    $sql = "Select * from user where username = '"
        . mysqli_escape_string($connection, $authUsername) . "'";
  
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
      $user->authHash = $authHash;
      return $user;
    }
  
    return null;
  }
  
  protected function isLoggedIn() {
    return isset($_SESSION['user']);
  }
  
	abstract function processInternal($urlRewrite);
	
	abstract function requiresLogin();
	
	protected function getConnection() {
		return Dbutil::getConnection();
	}
}
?>