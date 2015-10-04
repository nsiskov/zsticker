<?php
require_once 'lib/Twig/Autoloader.php';
require_once 'util/textResources.php';
require_once 'model/pageContext.php';
require_once 'util/urlRewrite.php';
require 'util/mailsender.php';
require 'util/dbutil.php';

class StatusReport {
	
	private $twig;
	private $urlRewrite;
	public function __construct() {
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem('view');
		$this->twig = new Twig_Environment($loader, array(
				'cache' => 'cache',
	
		));
		$this->urlRewrite = new UrlRewrite();
		$this->twig->addGlobal('urlRewrite', $this->urlRewrite);
	}
		
	function getMailUsers() {
		$idAlbum = 1;
		$sql = 
		"SELECT al.key as albumkey, ur.id as srcid, ur.email as srcemail, concat(ur.firstname, \" \", ur.lastname) as srcfullname, j.uid, j.email, j.fullname, j.idstickers 
 		FROM user ur, (SELECT m.idalbum, d.iduser as srcuser, m.iduser as uid, concat(u.firstname, \" \", u.lastname) as fullname,
									u.email, group_concat(d.stickernumber ORDER BY d.stickernumber SEPARATOR ', ') as idstickers 
								FROM missing m 
								JOIN duplicate d on m.stickernumber = d.stickernumber
									and m.idalbum = d.idalbum and d.copy > 0 
								JOIN user u on u.id = m.iduser and u.id != d.iduser
								WHERE u.active=1
								GROUP BY m.iduser, d.iduser) as j
		JOIN album al on al.id=j.idalbum
		WHERE j.srcuser = ur.id and ur.active=1
		";
		
		$connection = DbUtil::getConnection();
		
		$sql_result = mysqli_query($connection, $sql);
		
		$info = array();
		while($sql_row = mysqli_fetch_array($sql_result)) {
			$srcId = $sql_row['srcid'];
			$albumKey = $sql_row['albumkey'];
			
			if (!isset($info[$srcId])) {
				$info[$srcId] = array();
				$info[$srcId]["email"] = $sql_row['srcemail'];
				$info[$srcId]["fullname"] = $sql_row['srcfullname'];
			}
			
			if (!isset($info[$srcId]["albums"])) {
				$info[$srcId]["albums"] = array();
			}
			if (!isset($info[$srcId]["albums"][$albumKey]))
				$info[$srcId]["albums"][$albumKey] = array();
				$info[$srcId]["albums"][$albumKey]["key"] = $albumKey;
			
			if (!isset($info[$srcId]["albums"][$albumKey]["neededBy"])) {
				$info[$srcId]["albums"][$albumKey]["neededBy"] = array();
			}
			$info[$srcId]["albums"][$albumKey]["neededBy"][]= array(
					'id'=>$sql_row['uid'], 
					'name'=>$sql_row['fullname'],
					'stickers'=>$sql_row['idstickers']);
			
			$srcId = $sql_row['uid'];
			
			if (!isset($info[$srcId])) {
				$info[$srcId] = array();
				$info[$srcId]["email"] = $sql_row['email'];
				$info[$srcId]["fullname"] = $sql_row['fullname'];
			}
			if (!isset($info[$srcId]["albums"])) {
				$info[$srcId]["albums"] = array();
			}
			if (!isset($info[$srcId]["albums"][$albumKey])) {
				$info[$srcId]["albums"][$albumKey] = array();
				$info[$srcId]["albums"][$albumKey]["key"] = $albumKey;
			}
			if (!isset($info[$srcId]["albums"][$albumKey]["hasStickers4You"])) {
				$info[$srcId]["albums"][$albumKey]["hasStickers4You"] = array();
			}
			$info[$srcId]["albums"][$albumKey]["hasStickers4You"][]= array(
					'id'=>$sql_row['srcid'],
					'name'=>$sql_row['srcfullname'],
					'stickers'=>$sql_row['idstickers']);
		}
		return $info;
	}
	
	function sendMail($info) {
		foreach ($info as $user) {
			$emailAddr = $user["email"];
			if (isset($emailAddr) && !empty($emailAddr)) {
				date_default_timezone_set('UTC');
				$date = date('F j, Y');
				$textResources = new TextResources('en');
				$serverContext = str_replace('/statusreport.php', '', $_SERVER['SCRIPT_NAME']);
				$pageContext = new PageContext("mail", $textResources, "en", $serverContext);
				$twigVars = array('host'=>"http://" . $_SERVER['HTTP_HOST'], 
    			'pageContext'=>$pageContext,'user'=>$user, 'date'=>$date, 'textResources'=>$textResources);
				$message = $this->twig->render('mails/statusreport.twig', $twigVars);
				if (isset($_GET["testMail"])) {
				  MailSender::sendMail($_GET["testMail"], "zSticker Status Update", $message);
				} else if (isset($_GET["realMail"])) {
				  MailSender::sendMail($emailAddr, "zSticker Status Update", $message);
				}
				echo "<br/>Sending to: " . $emailAddr . "<br/>" . $message;
			}
		}
	}
}
$statusReport = new StatusReport();

	$info = $statusReport->getMailUsers();
	//print_r($info);
	error_reporting(-1);
	set_time_limit(0);
	try {
		$statusReport->sendMail($info);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		print_r($e);
	}
?>