<?php
require_once 'abstractController.php';
class ServiceController extends AbstractController {
  function requiresLogin() {
    return true;
  }
  
	function processInternal($urlRewrite) {
		if (isset($_POST)) {
			$params = file_get_contents('php://input');
			$obj = json_decode($params);
			ob_clean();
			$ret = null;
			
			if (($obj->type != "missing"
					&& $obj->type != "duplicate" 
			    && $obj->type != "match" 
			    && !is_numeric($obj->albumId))) {
			  return array('render'=>false);
			}
			  
			if ($obj->type == "duplicate" && $obj->action == "get") {
			  $ret = $this->getDuplicates($obj->albumId);
			} else if ($obj->type === "match") {
  			$ret = $this->getMatch($obj->albumId);
  		} else if ($obj->action === "get" && $obj->type === "missing") {
				$ret = $this->getMissing($obj->albumId);
			} else if ($obj->action === "set" && is_array($obj->stickerNumbers)) {
				$ret = $this->setStickers($obj->type, $obj->albumId, $obj->stickerNumbers, $obj->add);
			} 
			echo json_encode($ret);
			ob_end_flush();
		}
		return array('render'=>false);
  }
  
  private function getMissing($albumId) {
  	$connection = parent::getConnection();
  	$userId = $_SESSION['user']->getUserId();
  	$sql = "Select * from missing where idalbum = "
  			. mysqli_escape_string($connection, $albumId) . " and iduser=" . $_SESSION['user']->getUserId();
  
  	$sql_result=mysqli_query($connection, $sql)
  	  or exit("Sql Error".mysqli_error($connection));
  
  	$stickers = array();
  	while($sql_row = mysqli_fetch_array($sql_result)) {
  		$stickers[] = $sql_row["stickernumber"];
  	}
  	$pair[]['stickers'] = $stickers;
  	
  	$returnArray = array();
  	$returnArray['type'] = "missing";
  	$returnArray['data'] = $pair;
  	return $returnArray;
  }
  
  private function getDuplicates($albumId) {
    $connection = parent::getConnection();
    $userId = $_SESSION['user']->getUserId();
    $sql = "SELECT m.iduser as uid, concat(u.firstname,\" \", u.lastname) as fullname, group_concat(d.stickernumber SEPARATOR ',') as idstickers 
            FROM missing m RIGHT OUTER JOIN duplicate d on m.stickernumber = d.stickernumber and m.idalbum = d.idalbum and m.idalbum = " . mysqli_escape_string($connection, $albumId) . " and d.copy > 0 
            LEFT OUTER JOIN user u on u.id = m.iduser 
            WHERE d.iduser = " .  $_SESSION['user']->getUserId() . 
            " and u.active=1
            GROUP BY m.iduser";
    
    $sql_result = mysqli_query($connection, $sql)
        or exit("Sql Error".mysqli_error($connection));
    
    $pair = array();
    $result = array();
  	while($sql_row = mysqli_fetch_array($sql_result)) {
  		$pair['uid'] = $sql_row["uid"];
  		$pair['fullname'] = $sql_row["fullname"];
  		$pair['stickers'] = explode(",", $sql_row["idstickers"]);
    	$result[] = $pair;
  	}
     
    $returnArray = array();
    $returnArray['type'] = "duplicate";
    $returnArray['data'] = $result;
    return $returnArray;
  }
  
  private function setStickers($table, $albumId, $stickerNumbers ,$add) {
  	$connection = parent::getConnection();
  	$stickerNumber = $stickerNumbers[0];
  	if ($add) {
  		$sql = "Select * from " . $table . " where idalbum = "
  				. mysqli_escape_string($connection, $albumId) . " and iduser=" . $_SESSION['user']->getUserId()
  				. " and stickerNumber=" . mysqli_escape_string($connection, $stickerNumber);
  
  		$sql_result = mysqli_query($connection, $sql)
  		or exit("Sql Error".mysqli_error($connection));
  
  		$sql_num=mysqli_num_rows($sql_result);
  		$updateSql = null;
  		if ($sql_num > 0) {
  			$updateSql = "update " . $table . " set copy = copy + 1 where idalbum="
  					. mysqli_escape_string($connection, $albumId) . " and iduser=" . $_SESSION['user']->getUserId()
  					. " and stickerNumber=" . mysqli_escape_string($connection, $stickerNumber);
  		} else {
  			$updateSql = "insert into " . $table . "(idalbum, iduser, stickernumber) values ("
  					. $albumId . "," . $_SESSION['user']->getUserId() . "," . $stickerNumber . ")";
  		}
  
  		mysqli_query($connection, $updateSql);
  	} else {
  		$deleteSql = "delete from " . $table . " where idalbum="
  				. mysqli_escape_string($connection, $albumId) . " and iduser=" . $_SESSION['user']->getUserId()
  				. " and stickerNumber=" . mysqli_escape_string($connection, $stickerNumber);
  		mysqli_query($connection, $deleteSql);
  	}
  	$retval = array();
  	$retval['added'] = $add;
  	$retval['stickerNumbers'] = $stickerNumbers;
  	
  	$returnArray = array();
  	$returnArray['type'] = $table;
  	$returnArray['data'] = $retval;
  	return $returnArray;
  }
  
  private function getMatch($albumId) {
  	$connection = parent::getConnection();
  	$sql = "SELECT d.iduser as uid, concat(u.firstname, ' ', u.lastname) as fullname, group_concat(m.stickernumber SEPARATOR ',') as idstickers
  	        FROM missing m 
  					LEFT OUTER JOIN duplicate d ON 
  								m.stickernumber = d.stickernumber 
  						and m.idalbum = d.idalbum 
  						and m.idalbum = " . mysqli_escape_string($connection, $albumId) . " 
  						and d.copy > 0
  						and d.iduser in (SELECT id from user where active=1)
  	        LEFT OUTER JOIN user u ON u.id = d.iduser
  					WHERE m.iduser = " . $_SESSION['user']->getUserId() . "
  				  and m.idalbum = " . mysqli_escape_string($connection, $albumId) . " 
  					GROUP by d.iduser";
  
  	$sql_result = mysqli_query($connection, $sql)
  	    or exit("Sql Error".mysqli_error($connection));
  
  	$pair = array();
  	while($sql_row = mysqli_fetch_array($sql_result)) {
  		$pair['uid'] = $sql_row["uid"];
  		$pair['fullname'] = $sql_row["fullname"];
  		$pair['stickers'] = explode(",", $sql_row["idstickers"]);
    	$result[] = $pair;
  	}
    
    
    $returnArray = array();
    $returnArray['type'] = "match";
    $returnArray['data'] = $result;
    return $returnArray;
  }
}
?>