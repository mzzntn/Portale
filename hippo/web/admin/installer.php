<?
#:TODO:
#extend for different bindings (not just db)
#integrate widget installation
include_once('../init.php');
$install = new Install();
print "<html><body>";
print "<h3>Install manager</h3><br>";
print "Choose a namespace: <br>";
$nameSpaces = $install->listNameSpaces();
foreach ($nameSpaces as $nameSpace){
  if ($nameSpace == 'portal_spider') continue;
  if (($nameSpace == 'portal') && $C['portal']['spider_portal']) continue;
  print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}'>";
  print $nameSpace;
  print "</a>";
  print "<br>";
}
print "<hr>";
$struct = $_REQUEST['struct'];
$action = $_REQUEST['action'];
$nameSpace = $_REQUEST['namespace'];
print "NAMESPACE: $nameSpace<br>";
if ($action == 'build'){
  $restoreLevel = $IMP->debugLevel;
  $IMP->debugLevel = 4;
  if ($nameSpace == 'albo' && !$struct) echo "per albo eseguire il build di ogni struttura singolarmente";
  elseif ($struct == 'benefici::dipendente'){
	$loader = & $IMP->getLoader($struct);
	$bindingStruct = $IMP->bindingManager->getBinding($struct);
	$db = $bindingStruct->getDbObject();
	$sql = "drop table benefdipendente;"; 
	$db->execute($sql);	
	$sql = "create view benefdipendente as select * from benefresponsabile;";
        $db->execute($sql);
  }
  elseif ($struct == 'trasparenza::settore' && defined('URL_APP_BENEFICI')){
        $loader = & $IMP->getLoader($struct);
        $bindingStruct = $IMP->bindingManager->getBinding($struct);
        $db = $bindingStruct->getDbObject();
        $sql = "drop table trasparenza__settore;";
        $db->execute($sql);
        $sql = "create view trasparenza__settore as select * from benefsettore;";
        $db->execute($sql);
  }
  elseif (($struct == 'settore') && defined('URL_APP_BENEFICI')){
        $loader = & $IMP->getLoader($struct);
        $bindingStruct = $IMP->bindingManager->getBinding($struct);
        $db = $bindingStruct->getDbObject();
        $sql = "drop table settore;";
        $db->execute($sql);
        $sql = "create view settore as select * from benefsettore;";
        $db->execute($sql);
  }
  
  elseif ($C['albo']['soloPubblica'] && $struct == 'albo::ms_affis'){
        $loader = & $IMP->getLoader($struct);
        $bindingStruct = $IMP->bindingManager->getBinding($struct);
        $db = $bindingStruct->getDbObject();
	$sql = 'drop table albo__ms_affis;';
	$db->execute($sql);
        $sql = 'alter table ms_affis add `CR_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_affis add `MOD_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_affis add `PERMS` varchar(50) DEFAULT NULL';
        $db->execute($sql);
	if ($IMP->config['defaultdb']['type'] == 'mysql' && $IMP->config['nosequenze']){
		$sql = "ALTER TABLE ms_affis ADD PRIMARY KEY(NPROG)"; 
		$db->execute($sql);
		$sql = "ALTER TABLE ms_affis CHANGE NPROG NPROG INT( 11 ) NOT NULL AUTO_INCREMENT";
		$db->execute($sql);
	}
  } 
  elseif ($C['albo']['soloPubblica'] && $struct == 'albo::m1_tab_uff'){ 
        $loader = & $IMP->getLoader($struct);
        $bindingStruct = $IMP->bindingManager->getBinding($struct);
        $db = $bindingStruct->getDbObject();
        $sql = 'drop table albo__m1_tab_uff;';
        $db->execute($sql);
	if (defined('URL_APP_BENEFICI')){
		$sql = "drop table m1_tab_uff;";
		$db->execute($sql);
                $sql = "create view m1_tab_uff (id, CR_DATE, MOD_DATE, CR_USER_ID, MOD_USER_ID, M1_TAB_COD, M1_TAB_DES, M1_STA_FLG) as SELECT `ID`,CR_DATE, MOD_DATE, CR_USER_ID, MOD_USER_ID,  `ID`, `NOME`, '' FROM `benefufficio`;";
                $db->execute($sql);
	}
	else{
	        $sql = 'alter table m1_tab_uff add `ID` int(11) DEFAULT NULL;';
	        $db->execute($sql);
        	$sql = 'alter table m1_tab_uff add `CR_DATE` varchar(50) DEFAULT NULL;';
	        $db->execute($sql);
        	$sql = 'alter table m1_tab_uff add `MOD_DATE` varchar(50) DEFAULT NULL;';
	        $db->execute($sql);
        	$sql = 'alter table m1_tab_uff add `CR_USER_ID` int(11) DEFAULT NULL;';
	        $db->execute($sql);
	        $sql = 'alter table m1_tab_uff add `MOD_USER_ID` int(11) DEFAULT NULL;';
	        $db->execute($sql);
                $sql = 'alter table m1_tab_uff add `PERMS` varchar(50) DEFAULT NULL;';
                $db->execute($sql);
	        $sql = "create view albo__m1_tab_uff as select * FROM `m1_tab_uff`;";
	        $db->execute($sql);
	}
  }
  elseif ($C['albo']['soloPubblica'] && $struct == 'albo::ms_allegato'){ 
        $loader = & $IMP->getLoader($struct);
        $bindingStruct = $IMP->bindingManager->getBinding($struct);
        $db = $bindingStruct->getDbObject();
        $sql = 'drop table albo__ms_allegato;';
	$db->execute($sql);
	$sql = "alter table ms_allegato add `ID` int(11) DEFAULT NULL;";
	$db->execute($sql);
        $sql = 'alter table ms_allegato add `CR_DATE` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_allegato add `MOD_DATE` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_allegato add `CR_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_allegato add `MOD_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_allegato add `PERMS` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
  }
  elseif ($struct == 'albo::ms_tipiatto'){
    $loader = & $IMP->getLoader($struct);
    $bindingStruct = $IMP->bindingManager->getBinding($struct);
    $db = $bindingStruct->getDbObject();
    if ($C['albo']['soloPubblica']){ 
        $sql = 'drop table albo__ms_tipiatto;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `CR_DATE` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `MOD_DATE` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `CR_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `MOD_USER_ID` int(11) DEFAULT NULL;';
        $db->execute($sql);
        $sql = 'alter table ms_tipiatto add `PERMS` varchar(50) DEFAULT NULL;';
        $db->execute($sql);
     }
     else {
        $sql = 'alter table albo__ms_tipiatto modify id int(11)';
        $db->execute($sql);
	echo "Elimino campo autoincremenante";
    }	
  }
  elseif ($C['albo']['soloPubblica'] && $struct == 'albo::ms_tipiatto')  print "<hr>";

  else $install->build($nameSpace, $struct);
  $IMP->debugLevel = $restoreLevel;
  

}
if ($action == 'register'){
  $install->register($nameSpace, $struct);
  print "REGISTERED<br>";
  print "<hr>";
}
if ($struct && $action == 'view'){
  print "<b>Struct {$_REQUEST['struct']}</b><br>";
  print "<pre>";
  $install->printStruct($_REQUEST['struct']);
  print "</pre>";
  print "<hr>";
}
if ($nameSpace){
  $install->parseNameSpace($nameSpace);
  print "<b>Namespace: {$nameSpace}</b> ";
  print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}&action=build'>";
  print "Build";
  print "</a> ";
  print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}&action=register'> ";
  print "Register";
  print "</a>";
  print "<br>";
  print "Structs<br>";
  $structs = $install->getStructs($_REQUEST['namespace']);
  foreach ($structs as $struct){
    if ($IMP->bindingManager->bindingType($struct) != 'db') continue;
    print $struct;
    print " ";
    print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}&struct=$struct&action=view'>";
    print "View";
    print "</a> ";
    print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}&struct=$struct&action=build'>";
    print "Build";
    print "</a> ";
    print "<a href='{$_SERVER['PHP_SELF']}?namespace={$nameSpace}&struct=$struct&action=register'>";
    print "Register";
    print "</a>";
    print "<br>";
  }
}
print "</body>";
print "</html>";

?>
