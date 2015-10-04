<?php
require_once 'abstractController.php';
class ManageAlbumsController extends AbstractController {
	
	function requiresLogin() {
		return true;
	}
	
	function processInternal($urlRewrite) {
		$connection = parent::getConnection();
		$allAlbums = $this->getAllAlbums($connection);
		
		return array('render'=>true, 'allAlbums'=>$allAlbums);
	}
	
	function getAllAlbums($connection) {
		$user = $_SESSION['user'];
		$sql = "select a.id, a.key, a.stickercount, au.iduser from album a left outer join albumuser au on a.id=au.idalbum and au.iduser=" .
				mysqli_escape_string($connection, $user->getUserId()) ." order by iduser asc";
		
		$sql_result=mysqli_query($connection, $sql)
				or exit("Sql Error".mysqli_error($connection));
		
		$albumInfos = array();
		while($sql_row = mysqli_fetch_array($sql_result)) {
			$albumInfos[] = array(
				'albumId' => $sql_row[0],
				'albumKey' => $sql_row[1],
				'albumStickerCount' => $sql_row[2],
				'userid' => $sql_row[3]
			);
		}
		return $albumInfos;
	}
}
?>