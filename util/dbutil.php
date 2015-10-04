<?php
class DbUtil {
	static function getConnection() {
		$connection = mysqli_connect($GLOBALS['config']->dbhost, $GLOBALS['config']->dbuser,$GLOBALS['config']->dbpassword, $GLOBALS['config']->dbdatabase)
			or die("Could not connect: ".mysqli_error($connection));
		 
		mysqli_select_db($connection, $GLOBALS['config']->dbdatabase)
			or die("Error in selecting the database:".mysqli_error($connection));
		return $connection;
	}
}
?>