<?php
require_once 'lib/Twig/Autoloader.php';
require_once 'util/textResources.php';
require_once 'model/pageContext.php';
require_once 'util/urlRewrite.php';
require_once 'model/user.php';
require_once 'controller/loginController.php';
require_once 'controller/dashboardController.php';
require_once 'controller/serviceController.php';
require_once 'controller/manageAlbumsController.php';
require_once 'lib/le_php-master/logentries.php';
require_once 'config/config.php';

class Entry {
  private $twig;
  private $urlRewrite;
  private $log;
  public function __construct() {
    
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem('view');
    $twigInitParams = array();
    if ($GLOBALS['config']->twigCache) {
    	$twigInitParams['cache'] = 'cache';
    }
    $this->twig = new Twig_Environment($loader, $twigInitParams);
    $this->urlRewrite = new UrlRewrite();
    $this->twig->addGlobal('urlRewrite', $this->urlRewrite);
    $this->log = $GLOBALS['log'];
  }
  
  public function process() {
    session_start();
    
    $aclang = $this->getallheaders();
    $this->log->Info("Serving request: " . implode(",",$aclang));
    
    $pageId = $this->determinePage();
    if ($pageId == "") {
    	$this->log->Info("PageId is null, redirecting to entry.php?t=dashboard&type=duplicate");
    	header('Location: ' . $this->urlRewrite->r('entry.php?t=dashboard&type=duplicate'));
    } else {
    	$this->log->Info("PageId " . $pageId);
    	$lang = $this->determineLanguage();
    	 
    	$textResources = new TextResources($lang);
    	$serverContext = str_replace('/entry.php', '', $_SERVER['SCRIPT_NAME']);
    	$pageContext = new PageContext($pageId, $textResources, $lang, $serverContext);
    	
    	
    	$twigVars = array('host'=>"http://" . $_SERVER['HTTP_HOST'],
    			'textResources'=>$textResources, 'pageContext'=>$pageContext);
    	$twigTemplate = $pageId;
    	$render = true;
    	$controller = null;
    	
    	switch ($pageId) {
    		case 'login':
    		  $controller = new LoginController();
    			$twigTemplate = 'login';
    			break;
    		case 'dashboard':
    		  $controller = new DashboardController();
    			$twigTemplate = 'dashboard';
    			break;
    		case 'manageAlbums':
    			$controller = new ManageAlbumsController();
    			$twigTemplate = 'manageAlbums';
    			break;
    		case 'service':
    			$controller = new ServiceController();
    			break;
    	}
    	
    	if (isset($controller)) {
    		$vars = $controller->process($this->urlRewrite);
    		$render = $vars['render'];
    		$twigVars = array_merge($twigVars, $vars);
    	}
    	
    	$twigVars = array_merge($twigVars, array('config'=>$GLOBALS['config'], 'userLoggedIn'=>isset($_SESSION['user'])));
    	
    	if (isset($_SESSION['user'])) {
    		$twigVars = array_merge($twigVars, array('user'=>$_SESSION['user']));
    	}
    	if ($render === true) {
    		echo $this->twig->render('page/' . $twigTemplate . '.twig', $twigVars);
    	}
    }
  }
  
  function determinePage() {
  	$pageId = "";
  	if (isset($_GET['t'])) {
  		$pageId =  $_GET['t'];
  	}
  
  	if(false === array_search($pageId,
  			array('login', 'dashboard', 'service', 'manageAlbums'))) {
  		$pageId = "";
  	}
  	return $pageId;
  }
  
  function determineLanguage() {
  	if (isset($_GET['lng'])) {
  		//set the language from the lng parameter (overrides all other cases)
  		$lang =  $_GET['lng'];
  	} else {
  		if (isset($_SESSION['lang'])) {
  			//set the session language as first option (if any)
  			$lang = $_SESSION['lang'];
  		} else {
  			$reqLang = $this->getLangFromRequest();
  			if (isset($reqLang)) {
  				//set the requrest language (from browser) as second option (if any)
  				$lang = $reqLang;
  			}
  		}
  	}
  	 
  	if(false === array_search($lang, array('en','de','mk'))) {
  		$lang = 'en';
  	}
  	 
  	return $lang;
  }
  
  function getLangFromRequest() {
  	$aclang = $this->getallheaders();
  	$this->log->Info("headers: " . implode("'", $aclang));
  	$lngHead = $aclang['Accept-Language'];
  	if (isset($lngHead)) {
  		$chk = explode(";", $lngHead);
  		$chunks = $chk[0];
  		foreach (explode(",",$chunks) as $entry) {
  			if(strlen($entry) == 2) {
  				return $entry;
  			}
  			if (strpos($entry, "-") != -1 && strlen($entry) == 5) {
  				$chkd = explode("-",$entry);
  				return $chkd[0];
  			}
  		}
  	}
  }
  function getallheaders()
  {
  	$headers = '';
  	foreach ($_SERVER as $name => $value)
  	{
  		if (substr($name, 0, 5) == 'HTTP_')
  		{
  			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
  		}
  	}
  	return $headers;
  }
}
$config = new Config();
$GLOBALS['config'] = $config;
$GLOBALS['log'] = $log;
error_reporting(-1);
$entry = new Entry();
$entry->process();
?>