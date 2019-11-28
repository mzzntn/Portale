<?
include_once('CAS.php');
// libreria per codifica login rails/azure
include_once(LIBS.'/ext/Firebase/JWT/JWT.php'); 
include_once(LIBS.'/ext/Firebase/JWT/BeforeValidException.php'); 
include_once(LIBS.'/ext/Firebase/JWT/ExpiredException.php'); 
include_once(LIBS.'/ext/Firebase/JWT/SignatureInvalidException.php'); 

class Security{
  var $userStruct;    //the struct describing users
  var $groupStruct;   //the struct describing groups
  var $userId;
  var $groups;
  var $groupNames;
  var $login;
  var $structures;
  var $domains;
  var $JWTSecret = "...";

  function Security(){
    global $IMP;
    $this->typeSpace = & $IMP->typeSpace;
    $this->bindingManager = & $IMP->bindingManager;
    $this->userStruct = '_security_user';
    $this->groupStruct = '_security_group';
  }

  function useStructs($userStruct, $groupStruct){
    $this->userStruct = $userStruct;
    $this->groupStruct = $groupStruct;
  }

  function login($name, $pass, $clearText=false){
    global $IMP;
    global $C;
    @session_destroy();
    session_set_cookie_params('', '/');
    @session_start();
    $adminFile = CONFIG.'/security/admins/'.$name;
    $sessionPrefix = $IMP->config['securityPrefix'];
    $md5Pass = md5($pass);
    if (file_exists($adminFile) && trim(file_get_contents($adminFile)) == $md5Pass){
      $this->userId = -1;
      $this->groups[-1] = true;
      $this->login = $name;
    }
    elseif($C['portal']['spider_portal'] ){
        $spider = 1;
	$loader = & $IMP->getLoader('portal_spider::amministratore');
        $loader->addParam('login', $name);
        $spiderAdmin = $loader->load();
        while ($spiderAdmin->moveNext()){
            $pwdDb = $spiderAdmin->get('password');
            $hashDb = explode('$', $pwdDb);
            if ($hashDb[0] == 'sha2') $shaPass = hash('sha256', $pass.$hashDb[1]);
            elseif ($hashDb[0] == 'md5') $shaPass = md5($password);
            if ($shaPass == $hashDb[2]){
                $this->userId = -1;
                $this->groups[-1] = true;
                $this->login = $name;
            }
       }
    }
    else{
      $loader = $this->bindingManager->getLoader($this->userStruct);
      $loader->addParam('login', $name);
      $loader->addParam('password', $pass);
      $loader->request('id');
      $loader->request('groups.id');
      $loader->request('groups.name');
      $this->disablePolicy($this->userStruct);
      $this->disablePolicy($this->groupStruct);
      $user = $loader->load();
      $this->reEnablePolicy($this->userStruct);
      $this->reEnablePolicy($this->groupStruct);
      $this->userId = $user->get('id');
      $this->groups = array();
      while ($user->moveNext('groups')){
        $group = $user->get('groups');
        $this->parseGroup($group);
      }
      $this->login = $name;
    }
    $IMP->widgetParams->clear();
    if (!$spider) $this->readGroups();
    $_SESSION[$sessionPrefix.'userId'] = $this->userId;
    if ($IMP->config['loginSquirrelmail']) $this->loginToSquirrelmail($name, $pass);
    return $this->userId;
  }
  
  function wipeSession() {
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
	$params = @session_get_cookie_params();
	@setcookie(session_name(), '', time() - 42000,
	    $params["path"], $params["domain"],
	    $params["secure"], $params["httponly"]
	);
    }

    // Finally, destroy the session.
    @session_destroy();
    session_set_cookie_params('', '/');
    @session_start();
  }
  
  function loginStart($destination, $issuer="portal_php", $jwt=false, $authType=false) {
    global $IMP, $C;
    $test = false && strpos($_SERVER['HTTP_HOST'],"piovene-new")!==false;
    @session_destroy();
    session_set_cookie_params('', '/');
    @session_start();
    $sessionPrefix = $IMP->config['securityPrefix'];
    if($authType===false){$authType=$C['portal']['start_auth']['auth_type'][0];}
    $sessionId = session_id();
    if($test){echo "<br>current session id is [$sessionId]<br>";}
    
    $currentPageURL = 'http'; 

    $uri = $_SERVER["REQUEST_URI"];
    if(strpos($uri, "?")!==false) {
      // elimino i parametri GET
      $uri = explode("?", $_SERVER["REQUEST_URI"]);
      $uri = $uri[0];
    }

    if(strpos($uri, "/")!==false) {
      // elimino i parametri GET
      $destinationUri = explode("/", $_SERVER["REQUEST_URI"]);
      unset($destinationUri[count($destinationUri)-1]);
      $destinationUri = implode("/",$destinationUri);
    }

    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
	$currentPageURL .= "s";
    }
    $currentPageURL .= "://";
    $destinationPageUrl = $currentPageURL;

    if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
	$currentPageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$uri;
	$destinationPageUrl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$destinationUri."/".$destination;
    } else {
	$currentPageURL .= $_SERVER["SERVER_NAME"].$uri;
	$destinationPageUrl .= $_SERVER["SERVER_NAME"].$destinationUri."/".$destination;
    }
    
    if($jwt) {
      try {
	$token = JWT::decode($_GET["jwt"], $this->JWTSecret, array('HS256'));
	if(isset($token->ext_session_id) && $token->ext_session_id==$sessionId) {
	  // sono loggato
	  $_SESSION["jwt"] = $_GET["jwt"];
          $this->userLogin = $token->user->name;
	  if($C['portal']['start_auth']['local_users']) {
	    // utilizza i permessi dal database locale
	     if($token->user->admin) {
            // utente amministratore, aggiungiamo i permessi (o sovrascriviamoli se sono stati impostati prima, start ha la precedenza
               $this->groups[-1] = true;
               $this->userId = -1;
            }

	    elseif(!$this->getLocalUser($token->user->email)){
	      // non ho trovato corrispondenze col db locale, l'utente non ha i permessi per accedere alla sezione richiesta
	      if($test){
		echo "<pre>".print_r($token, true)."</pre>";
		echo "<pre>".$pippo."</pre>";
		echo "Can't find user {$token->user->email} in db - should redirect to <a href='{$currentPageURL}?error=1'>{$currentPageURL}?error=1</a>"; exit();
	      }
	      header("Location: {$currentPageURL}?error=1");exit();
	    }
	  } else {
	    // utilizza i permessi ricevuti da start
	    $this->userId = $token->user->user_id;
	    $this->groups[$token->user->user_group_id] = true;
	  }
	  $_SESSION[$sessionPrefix.'userId'] = $this->userId;
	  $_SESSION["username"] = $token->user->name;
	  //  JWT ok
	  if($test) {
	    echo '$this->groups[-1] is ['.$this->groups[-1]."]<br>";
	    echo '$this->userId is ['.$this->userId."]";
	    echo "<pre>".print_r($_SESSION, true)."</pre>";
	    echo "<pre>".print_r($token, true)."</pre>";
	    echo "should redirect to <a href='{$destinationPageUrl}'>{$destinationPageUrl}</a>"; exit();
	  }

	  header("Location: {$destinationPageUrl}");exit();
	  // sono loggato
	  /*$_SESSION["jwt"] = $_GET["jwt"];
          $this->groups[-1] = true;
          $this->userLogin = $token->user->name;
          $this->userId = -1; // $token->user->tid
// 	  getLocalUser($token->user->user_id);
	  $_SESSION[$sessionPrefix.'userId'] = $this->userId;
	  $_SESSION["username"] = $token->user->name;
	  //  JWT ok
	  if($test) {
	    echo "<pre>".print_r($_SESSION, true)."</pre>";
	    echo "<pre>".print_r($token, true)."</pre>";
	    echo "should redirect to <a href='{$destinationPageUrl}'>{$destinationPageUrl}</a>"; exit();
	  }

	  header("Location: {$destinationPageUrl}");*/
	} else {
          if($test){echo "token received<br><pre>".print_r($token, true)."</pre>";}
	  if($test){echo "property ext_session_id exists? [".property_exists($token,"ext_session_id")."]<br>";}
	  // l'id sessione non combacia
	  // faccio redirect per rimuovere i parametri GET dall'URL
	  if(($token->user->tid!=""||!property_exists($token,"tid")) && ($token->ext_session_id == ""||!property_exists($token,"ext_session_id"))) {
	    if($test){echo "session id mismatch, call coming from next. Logging out (php only) and logging in.";}
	    $this->wipeSession();
// 	    $this->loginStart($destination, $issuer, false, $token->auth);
	    unset($_GET["jwt"]);
	    $redirect = "{$currentPageURL}?login&auth=".$token->auth."&".http_build_query($_GET);
	    if($test){
	      echo "should redirect to <a href='{$redirect}'>{$redirect}</a>"; exit();
	    }
	    header("Location:$redirect");exit();
	  } else {
	    if($test){
	      echo "session id mismatch - should redirect to <a href='{$currentPageURL}?error'>{$currentPageURL}?error</a>"; exit();
	    }
	    header("Location: {$currentPageURL}?error");exit();
	  }
	}
      } catch(Exception $e) {
	// errore formato JWT
	// faccio redirect per rimuovere i parametri GET dall'URL
	if($test){echo "JWT error - should redirect to <a href='{$currentPageURL}?error'>{$currentPageURL}?error</a>"; exit();}
	header("Location: {$currentPageURL}?error");exit();
      }
    } else {
      
      // genero un token jwt per identificare origine, applicazione e sessione
      unset($_GET["jwt"]);
      $token = array(
	"iss" => $issuer,
	"aud" => $C['portal']['start_auth']['auditor'],
	"idc" => $C['portal']['start_auth']['tenant'],
	"ub" => $currentPageURL."?".http_build_query($_GET),
        "auth" => $authType,
	"ext_session_id" => session_id()
      );
   
      $jwt = JWT::encode($token, $this->JWTSecret);
      
      // salvo il token in sessione
      $_SESSION["jwt"] = $jwt;
      if($test){
      echo "<pre>".print_r($token, true)."</pre>";
      echo "should redirect to <a href='{$C['portal']['start_auth']['auditor']}/sign_in?jwt={$jwt}'>{$C['portal']['start_auth']['auditor']}/sign_in?jwt={$jwt}</a>"; exit();
      }
      
      // invio il token a rails tramite GET per il login
      header("Location: {$C['portal']['start_auth']['auditor']}/sign_in?jwt={$jwt}");exit();
    }
  }
  
  function getErrorMessage($error) {
    $errorMessage = "";
//     echo "error is $error";
    switch($error) {
      case 1:
	$errorMessage = "Non sei abilitato a visualizzare questa sezione.";
	break;
      case 2:
	$errorMessage = "Si &egrave; verificato un errore, si prega di riprovare. Se il problema persiste contattare l'amministratore.";
	break;
    }
    return $errorMessage;
  }
  
  function getLocalUser($startId) {
    global $IMP;
    global $C;
    $userFound = false;
    
    // if using admin file, we trust start for authentication and assign default values for admins
    if($C['portal']['spider_portal'] ){
      // check in db for user
      $spider = 1;
      #NON SERVE PIU' GLI UTENTI AMMINISTRATORI NON SONO DEFINITI LOCALMENTE
     /* $loader = & $IMP->getLoader('portal_spider::amministratore');
      $loader->addParam('start_user', $startId);
      $spiderAdmin = $loader->load();
      if ($spiderAdmin->moveNext()){
	// found this user
	$this->userId = -1;
	$this->groups[-1] = true;
	$this->login = $name;
	$userFound = true;
      } else {
	// try the other possible user table
	$spider = 1;*/
	$loader = & $IMP->getLoader('portal_spider::amministratoreServizi');
	$loader->addParam('start_user', $startId);
	$spiderAdminS = $loader->load();
	if ($spiderAdminS->moveNext()){
	  // found this user
	  $this->userId = $spiderAdminS->get('id');
	  $this->groups[-1] = true;
	  $this->login = $login;
	  $_SESSION['settore'] = $spiderAdminS->get('settore');
	  $_SESSION['operatore'] = 1;
	  $userFound = true;
	}
   //   }
    }
    else{
/*      $loader = $this->bindingManager->getLoader($this->userStruct);
//      $loader->addParam('start_user', $name);
      $loader->request('id');
      $loader->request('login');
      $loader->request('groups.id');
      $loader->request('groups.name');
      $this->disablePolicy($this->userStruct);
      $this->disablePolicy($this->groupStruct);
      $user = $loader->load();
      if($user->moveNext()) {
	// user exists in users table
	$this->reEnablePolicy($this->userStruct);
	$this->reEnablePolicy($this->groupStruct);
	$this->userId = $user->get('id');
	$this->groups = array();
	while ($user->moveNext('groups')){
	  $group = $user->get('groups');
	  $this->parseGroup($group);
	}
	$this->login = $user->get('login');
	$userFound = true;
      } 
	else {*/
	// let's check if user is in operatore table
	$loader = & $IMP->getLoader('operatore');
	$loader->addParam('start_user', $startId);
	$user = $loader->load();
	if($user->moveNext()) {
	  $this->groups[-1] = true;
	  $this->userLogin = $login;
	  $this->userId = $user->get('id');
	  $_SESSION['operatore'] = 1;
	  $userFound = true;
	}
    //  }
    }
    $IMP->widgetParams->clear();
    if (!$spider) $this->readGroups();
    $_SESSION[$sessionPrefix.'userId'] = $this->userId;
    
    return $userFound;
  }
  
  function logoutStart($destination, $issuer="portal_php") {
    // questa funzione viene usata solo in caso di errori, per forzare il logout
    global $C;
    
    $currentPageURL = 'http'; 
    
    $uri = $_SERVER["REQUEST_URI"];
    if(strpos($uri, "?")!==false) {
      // elimino i parametri GET
      $uri = explode("?", $_SERVER["REQUEST_URI"]);
      $uri = $uri[0];
    }
    if(strpos($uri, "/")!==false) {
      // elimino i parametri GET
      $destinationUri = explode("/", $_SERVER["REQUEST_URI"]);
      unset($destinationUri[count($destinationUri)-1]);
      $destinationUri = implode("/",$destinationUri);
    }

    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
	$currentPageURL .= "s";
    }
    $currentPageURL .= "://";
    $destinationPageUrl = $currentPageURL;

    if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
	$currentPageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$uri;
	$destinationPageUrl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$destinationUri."/".$destination;
    } else {
	$currentPageURL .= $_SERVER["SERVER_NAME"].$uri;
	$destinationPageUrl .= $_SERVER["SERVER_NAME"].$destinationUri."/".$destination;
    }
    
    // genero un token jwt per identificare origine, applicazione e sessione
    $token = array(
	"iss" => $C['portal']['start_auth']['issuer'],
	"aud" => $C['portal']['start_auth']['auditor'],
	"idc" => $C['portal']['start_auth']['tenant'],
	"ub_logout" => $destinationPageUrl,
	"auth" => $C['portal']['start_auth']['auth_type'][0], // azure
//	"auth" => "up", // username e password
	"ext_session_id" => session_id()
    );
    $jwt = JWT::encode($token, $this->JWTSecret);
    
    $this->wipeSession();
    
    //echo "<pre>".print_r($token, true)."</pre>";
    //echo "should redirect to <a href='{$C['portal']['start_auth']['auditor']}/ext_logout?jwt={$jwt}'>{$C['portal']['start_auth']['auditor']}/ext_logout?jwt={$jwt}</a>"; exit();
    // invio il token a rails tramite GET per il logout
    header("Location: {$C['portal']['start_auth']['auditor']}/ext_logout?jwt={$jwt}");exit();
  }

  function requireCAS($servizio=''){
      global $C;
      //if ($REQUEST['servizio']) $this->logoutCAS();
      if (!(!$_SESSION['servizi_cas'] || !$servizio || $_SESSION['servizi_cas'][$servizio])){
        $_SESSION['phpCAS'] = null;
      }
      phpCAS::setDebug();
      phpCAS::client(CAS_VERSION_2_0, $C['portal']['cas']['server'], intval($C['portal']['cas']['port']), $C['portal']['cas']['uri']);
      phpCAS::setNoCasServerValidation();
      //if (!$_SESSION['servizi_cas'] || !$servizio || $_SESSION['servizi_cas'][$servizio]){
          phpCAS::forceAuthentication();
      //}
      //else{
      //    phpCAS::renewAuthentication();
      //}
      $this->casUser->username = phpCAS::getUser();
      if ($servizio && $this->casUser->username){
          $_SESSION['servizi_cas'][$servizio] = true;
      }
      $attributes = phpCAS::getAttributes();
      if ($attributes) foreach ($attributes as $key => $value){
          if (!$key) continue;
          $this->casUser->$key = $value;
      }
    return $this->casUser;
   }

  function logoutCAS(){
    global $IMP;
    if (isset($_COOKIE[session_name()])) {
      @setcookie(session_name(), '', time()-42000, '/');
    }
}

    function loginCivilia($login, $password){
      global $IMP;
  #       $IMP->debugLevel = 3;
       $this->civiliaOpen->userCod = null;
      if (!$IMP->config['civiliaOpen']){
          error("Per utilizzare la funzione di login CiviliaOpen, 
              è necessario specificare i dati di connessione al database in init.php");
      }
      $dbConfig = $IMP->config['civiliaOpen']['db'];
      $db = $IMP->getDbObject($dbConfig['type']);
      $db->connect($dbConfig['name'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['server']);
      if ($dbConfig['tablespace']) $owner = $dbConfig['tablespace'];
	  else $owner =  $dbConfig['user'];
      $sql = "SELECT M1_USER_PWD, M1_USER_HASH FROM $owner.M1_SICU_USERS WHERE M1_USER_FIN IS NULL
          AND M1_USER_COD='{$login}'";
      $db->execute($sql);
      if (!$db->fetchrow()) return false;
      $cryptPass = $db->result('M1_USER_PWD');
      $md5Hash = $db->result('M1_USER_HASH');

      if($md5Hash) {
	      //faccio il controllo MD5 se esiste, altrimenti faccio controllo password vecchia
	      if(md5($password) != $md5Hash) return false;
      }
      else {

	      $sCharacters = array("$", "O", "N", "Y", "M", "a", "d", "0", "B", "z", "Q", "G", "t", "P", "1", "J", "A", "w", "Z", "y", "i", "c", "f", "k", "2", "s", "j", "T", "v", "L", "R", "m", "q", "W", "E", "U", "V", "5", "u", "g", "h", "7", "H", "x", "I", "F", "9", "C", "6", "S", "b", "e", "n", "K", "l", "8", "o", "X", "4", "3", "p", "r", "D");
	      $len = strlen($cryptPass);
	      $res = "";

	      for ($i=0; $i<$len; $i+=2){
		      $p1 = array_search($cryptPass[$len - $i - 1], $sCharacters);
		      $q1 = array_search($cryptPass[$i], $sCharacters);
		      $diff = $p1 - $q1;
		      if ($diff < 0) $diff += 63;
		      if ($diff == 0) break;
		      $res .= $sCharacters[$diff];
	      }
	      if ($res != $password) return false;
	}
	$this->civiliaOpen->userCod = $login;
	$_SESSION['civiliaOpenUser'] = $this->civiliaOpen->userCod;
	#$sessionPrefix = $IMP->config['securityPrefix'];
	#$_SESSION[$sessionPrefix.'security'] = $this;
	return true;
    }

  function loginOperatore($login, $password){
	  global $IMP;
	  #$IMP->debugLevel = 3;
	  $loader = & $IMP->getLoader('operatore');
	  $loader->addParam('login', $login);
	  $loader->addParam('password', $password);
	  $user = $loader->load();
          $this->groups[-1] = true;
          $this->userLogin = $login;
          $this->userId = $user->get('id');
          $_SESSION['operatore'] = 1;
	  return $this->userId;
  }

function loginOperatoreNew($login, $password){
	global $IMP;
	global $C;
	if($C['portal']['spider_portal']){
		$spider = 1;
		$loader = & $IMP->getLoader('portal_spider::amministratoreServizi');
		$loader->addParam('login', $login);
		$spiderAdminS = $loader->load();
		while ($spiderAdminS->moveNext()){
			$pwdDb = $spiderAdminS->get('password');
			$hashDb = explode('$', $pwdDb);
			if ($hashDb[0] == 'sha2') $shaPass = hash('sha256', $password.$hashDb[1]);
			elseif ($hashDb[0] == 'md5') $shaPass = md5($password);
			if ($shaPass == $hashDb[2]){
	                	$this->userId = $spiderAdminS->get('id');
                	        $this->groups[-1] = true;
        	                $this->login = $login;
			        $_SESSION['settore'] = $spiderAdminS->get('settore');
				$_SESSION['operatore'] = 1;
				$_SESSION['livello'] = $spiderAdminS->get('limita');
			}
		}
	}
	elseif(!$_SESSION['operatore']){
		$loader = & $IMP->getLoader('operatore');
		$loader->addParam('login', $login);
		$loader->addParam('password', $password);
		$user = $loader->load();
		$this->groups[-1] = true;
		$this->userLogin = $login;
		$this->userId = $user->get('id');
		$_SESSION['settore'] = $user->get('settore');
		$_SESSION['operatore'] = 1;
	}          
	return $this->userId;
}


function loadUser($id){
	global $IMP;
	global $C;
	if ($id < 0){
		$this->userId = -1;
		$this->login = 'admin';
		$this->groups[-1] = 'admin';
		$this->groupNames['admin'] = true;
	}
	elseif($_SESSION['operatore']){
		//$IMP->debugLevel = 3;
	        if($C['portal']['spider_portal']){
        	        $spider = 1;
                	$loader = & $IMP->getLoader('portal_spider::amministratoreServizi');
		}
		else $loader = & $IMP->getLoader('operatore');
		$loader->addParam('id', $id);
		$user = $loader->load();
		$this->userId = $user->get('id');
		$this->login = $user->get('login');
        	$this->groups[-1] = 'admin';
        	$this->groupNames['admin'] = true;
		$_SESSION['settore'] = $user->get('settore');
		$_SESSION['livello'] = $user->get('limita');
    }
    else{
      if ($id){
        $this->disablePolicy($this->userStruct);
        $this->disablePolicy($this->groupStruct);
        $loader = & $this->bindingManager->getLoader($this->userStruct);
        $loader->addParam('id', $id);
        $loader->request('id', 'login', 'groups.id', 'groups.name');
        $user = $loader->load();
        $this->userId = $user->get('id');
        $this->login = $user->get('login');
        $this->groups = array();
        while ($user->moveNext('groups')){
          $group = $user->get('groups');
          $this->parseGroup($group);
        }
        $this->login = $user->get('login');
        $this->reEnablePolicy($this->userStruct);
        $this->reEnablePolicy($this->groupStruct);
      }
      if (sizeof($this->groups) < 1){
        $this->groups[0] = 'guests';
        $this->groupNames['guests'] = true;
      }
    }
    $IMP->userId = $id;
    $this->readGroups();
    return $this->userId;
  }

  function addGroup($id, $name){
    $haveGroup = $this->groups[$id];
    $this->groups[$id] = $name;
    $this->groupNames[$name] = true;
    if (!$haveGroup){
      $this->readGroups($name);
    }
  }

  function parseGroup($group){
    $this->groups[$group->get('id')] = $group->get('name');
    $this->groupNames[$group->get('name')] = true;
    if ($group->get('parent.id')){
      $loader = & $this->bindingManager->getLoader($this->groupStruct);
      $loader->addParam('id', $group->get('parent.id'));
      $loader->request('id', 'name', 'parent.id');
      $parent = $loader->load();
      $this->parseGroup($parent);
    }
  }

  function readGroups($groups=0){
    if (!$groups) $groups = $this->groups;
    else if (!is_array($groups)) $groups = array($groups);
    $this->structures = array();
    foreach ($groups as $group){
      $xmlFile = CONFIG.'/security/'.$group.'.xml';
      if (!file_exists($xmlFile)) continue;
      $group = new PHPelican();
      $group->loadXmlFile($xmlFile);
      $grants = $group->getList('grant');
      while ($grants->moveNext()){
        $this->loadGrant($grants->getRow());
      }
      $options = $group->getList('option');
      while ($options->moveNext()){
        $option = $options->getRow();
        $name = $option->getAttribute('name');
        $this->options[$name] = $option->toArray();
      }
    }
  }

  function loadGrant($grant){
    $struct = $grant->getAttribute('struct');
    if (!$struct) while ($grant->moveNext('struct')){
      $structs[] = $grant->get('struct');
    }
    else $structs[] = $struct;
    if (is_array($structs)) foreach ($structs as $struct){
      $params = array();
      while ($grant->moveNext('op')){
        $op = $grant->get('op');
        if (is_object($op)) $op = $op->get('op'); //:FIXME: this should not happen,
        if (!is_array($op)) $op = array($op);
        foreach ($op as $o){
        //but it does when there is more than one op
	  if (!is_array($op)) $op = array($op);
	  foreach ($op as $o){
            $ops[$o] = true;
	  }
	}
      }
      while ($grant->moveNext('params')){
        array_push($params, $grant->get('params'));
      }
      $grantArray['ops'] = $ops;
      $grantArray['params'] = $params;
      if (!is_array($this->structures[$struct])) $this->structures[$struct] = array();
      array_push($this->structures[$struct], $grantArray);
    }
  }

  function getLogin(){
    return $this->login;
  }

  function logout(){
    global $IMP;
    $this->userId = 0;
    $this->groups = array();
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
      @setcookie(session_name(), '', time()-42000, '/');
    }
    @session_destroy();
    @session_start();
    $sessionPrefix = $IMP->config['securityPrefix'];
    $_SESSION[$sessionPrefix.'userId'] = 0;
    $_SESSION[$sessionPrefix.'security'] = 0;
  }

  function check(){
    if (!$this->userId) return false;
    return true;
  }

  function checkAdmin(){
//     echo '$this->userId is ['.$this->userId."]<br>";
//     echo '$this->groups[-1] is ['.$this->groups[-1]."]";exit();
    if ($this->groups[-1]) return true;
    return false;
  }

  function checkSuperUser(){
    if ($this->groups[-1]) return true;
    return false;
  }

  function checkGroup($group){
    if ($this->userId){
      if($this->groups[$group])return true;
      if ($this->groupNames[$group]) return true;
    }
    return false;
  }

  //FIXME: it's not nice to just check the login: the full extensions path
  //should be checked, but i need a loader method/param to do this.
  function checkDomain($domain){
    global $IMP;
    if (!$this->userId) return false;
    if (!$this->login) return false;
    #if (isset($this->domains[$domain])) return $this->domains[$domain];
    if ($domain == $this->userStruct) return true;
    $loader = & $this->bindingManager->getLoader($domain);
    $loader->request('id');
    $loader->addParam('login', $this->login);
    $this->disablePolicy($domain);
    $list = $loader->load();
    $this->reEnablePolicy($domain);
    if ($list->moveNext())$this->domains[$domain] = true;
    else $this->domains[$domain] = false;
    return $this->domains[$domain];
  }

  function checkPolicy($structName, $mode){
    global $IMP;
    $IMP->debug("Checking policy for $structName, mode $mode", 5);
    $IMP->debug("Policy is ".$this->policies[$structName][$mode], 5);
    if ($IMP->bindingManager->bindingType($structName) == 'inline' && ($mode == 'w' || $mode == 'i' || $mode == 'u')){
      return false;
    }
    list($accessMode, $nameSpace, $localName, $dir) = parseClassName($structName);
    if (!isset($this->policies[$structName]) && !isset($this->policies[$nameSpace])) return true;
    $good = $this->policies[$structName][$mode];
    if (!$good && ($mode == 'i' || $mode == 'u') && $this->polices[$structName]['w']){
      $good = true;
    }
    if (!$good){
      if ($nameSpace && 
          ($this->policies[$nameSpace][$mode]
          || ( ($mode == 'i' || $mode=='u') && $this->polices[$nameSpace]['w'])
          )
         ) $good = true;
    }
    return $good;
  }

  function disablePolicy($structOrNs){
    if (!$this->tmpPolicies[$structOrNs]){
      $this->tmpPolicies[$structOrNs] = $this->policies[$structOrNs];
      $this->policies[$structOrNs] = array('r'=>1, 'w'=>1, 'u'=>1, 'i'=>1);
    }
  }
  
  function reEnablePolicy($structOrNs){
    if ($this->tmpPolicies[$structOrNs]) $this->policies[$structOrNs] = $this->tmpPolicies[$structOrNs];
    unset($this->tmpPolicies[$structOrNs]);
  }

  function generatePassword($minLenght=6, $maxLenght=12){
    $pwd = '';
    for($i=0;$i<rand($minLenght,$maxLenght);$i++){
      $num=rand(48,122);
      if(($num > 97 && $num < 122) || ($num > 65 && $num < 90) || ($num >48 && $num < 57) || $num==95){
          $pwd.=chr($num);
      }
      else $i--;
    }
    return strtolower($pwd);
  }

  function loadStructs(){
  }


  function setStruct($structName){
    if ($this->structName != $structName){
      $this->structName = $structName;
      $this->struct = $this->typeSpace->getStructure($structName);
    }
  }

  function checkStruct($structName=''){
    return true;
  }

  function checkAnyEl($structName=''){
    return true;
  }

  /**
  * bool checkEl(string[, string, string])
  * Check permissions on an element
  **/
  function checkEl($elementName, $structName='', $mode=''){
    return true;
  }

  function checkRow($row){
    return true;
  }

  function setMode($mode){
    $this->mode = $mode;
  }

  function checkUserUpdate($elementName){
  }

  function userId(){
    return $this->userId;
  }

  function redirectToLogin($login){
    $url =  $_SERVER['PHP_SELF'].'?'.arrayToGET($_REQUEST);
    $url = urlencode($url);
    $dest = $login;
    if (strpos($login, '?') !== false) $dest .= '&';
    else $dest .= '?';
    redirect($dest.'go='.$url);
  }

  function changePassword($old, $new){
    global $IMP;
    $this->disablePolicy($this->userStruct);
    $storer = $this->bindingManager->getStorer($this->userStruct);
    $storer->addParam('login', $this->login);
    if ($old) $storer->addParam('password', $old);
    $storer->set('password', $new);
    $storer->store();
    $this->reEnablePolicy($this->userStruct);
    if (is_array($IMP->config['mailGroups'])) foreach ($IMP->config['mailGroups'] as $group){
      if ($this->checkGroup($group)){
        $this->changeHMailPassword($old, $new);
        break;
      }
    }
  }

  function loginToSquirrelmail($name, $pass){
    global $IMP;
    global $login_username, $secretkey;
    $goToMail = false;
    if ($IMP->config['loginSquirrelmail'] && $this->userId){
      if (is_array($IMP->config['mailGroups'])) foreach ($IMP->config['mailGroups'] as $group){
        if ($this->checkGroup($group)){
          $goToMail = true;
          break;
        }
      }
    }
    if ($goToMail){
      $login_username = $name.'@'.$IMP->config['hmailDomain'];
      $secretkey = $pass;
      $this->createHMAilAccount($login_username, $pass);
      include(LIBS.'/ext/squirrelmail/squirrelmail_login.php');
    }
  }

  function createHMailAccount($login, $pass){
    global $IMP;
    $md5Pass = md5($pass);
    $dbClass = 'Db_'.$IMP->config['hmailDbType'];
    #$IMP->debugLevel = 3;
    $db = new $dbClass('', $IMP->config['hmailDbName'], $IMP->config['hmailDbUser'], $IMP->config['hmailDbPass']);
    $sql = "SELECT accountid from hm_accounts where accountaddress='{$login}'";
    $db->execute($sql);
    if (!$db->fetchrow()){
      $sql = "SELECT MAX(accountid) as M from hm_accounts";
      $db->execute($sql);
      $db->fetchrow();
      $max = $db->result('M');
      $max++;
      $sql = "SELECT domainid from hm_domains WHERE domainname='".$IMP->config['hmailDomain']."'";
      $db->execute($sql);
      $db->fetchrow();
      $domainId = $db->result('domainid');
      $sql = "INSERT into hm_accounts
      (accountid, accountdomainid, accountadminlevel,accountaddress,accountpassword,accountactive,
        accountisad, accountaddomain, accountadusername, accountmaxsize, accountvacationmessageon,
        accountvacationmessage, accountvacationsubject, accountpwencryption, accountforwardenabled,
	accountforwardaddress, accountforwardkeeporiginal, accountenablesignature,
	accountsignatureplaintext, accountsignaturehtml
	)
        VALUES
        ($max, $domainId, 0, '$login','$md5Pass', 1,
	0, '', '', 0, 0, 
	'', '', 2, 0,
	'', 0, 0,
	'', '')";
      $db->execute($sql);
    }
  }
  
  
  function changeHMailPassword($old, $new){
    global $IMP;
    $md5Old = md5($old);
    $md5New = md5($new);
    $dbClass = 'Db_'.$IMP->config['hmailDbType'];
    #$IMP->debugLevel = 3;
    $db = new $dbClass('', $IMP->config['hmailDbName'], $IMP->config['hmailDbUser'], $IMP->config['hmailDbPass']);
    $sql = "SELECT domainid from hm_domains WHERE domainname='".$IMP->config['hmailDomain']."'";
    $db->execute($sql);
    $db->fetchrow();
    $domainId = $db->result('domainid');
    $address = $this->login.'@'.$IMP->config['hmailDomain'];
    $sql = "UPDATE hm_accounts SET accountpassword='$md5New' 
            WHERE accountdomainid=$domainId AND accountaddress ='$address' AND accountpassword='$md5Old'";
    $db->execute($sql);
  }

}

?>
