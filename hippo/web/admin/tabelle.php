<? 
include_once('../init.php');

if (!$IMP->security->checkAdmin()) redirect('login.php');

include_once(PATH_APP_PORTAL.'/admin/top.php');

if ($config['nosequenze']){
  $loader = & $IMP->getLoader('setup');
  $loader->addParam('applicazione', "setupDB");
  $loader->addParam('codice', 'hippo');
  
  $setupDb = $loader->load();
  if ($setupDb->get('valore') == 1) { 
  	$administrator = $IMP->widgetFactory->getWidget('Administrator', 'administrator');
  	$administrator->administer('setup', 'Tabella di setup');
  	$administrator->display();

  }
}

include_once(PATH_APP_PORTAL.'/admin/bottom.php');
?>

