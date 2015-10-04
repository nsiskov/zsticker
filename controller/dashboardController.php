<?php
require_once 'abstractController.php';
class DashboardController extends AbstractController {
  
  function requiresLogin() {
    return true;  
  }
  
	function processInternal($urlRewrite) {
		$viewType = "missing";
		if (isset($_GET['type']) && ($_GET['type'] === "missing"
															|| $_GET['type'] === "duplicate"
															|| $_GET['type'] === "match")
				) {
			$viewType = $_GET['type'];
		}
		
		if (isset($_GET['albumId']) && is_numeric($_GET['albumId'])) {
			$albumId = $_GET['albumId'];
			//set the current album
			$_SESSION['user']->currentAlbum = $_SESSION['user']->albums[$albumId];
		}
		
		$album = $_SESSION['user']->currentAlbum;
		return array('render'=>true, 'viewType'=>$viewType, 'album'=>$album);
  }
}
?>