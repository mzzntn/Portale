<?
include_once('../init.php');
#$IMP->debugLevel = 3;
// if (!$IMP->security->checkAdmin()) redirect('login.php');
include_once('top.php');

$administrator = $IMP->widgetFactory->getWidget('Administrator', 'administrator');
$administrator->config['form']['portal::utente']['showSelect']['portal::servizioPrivato'] = true;
$administrator->administer('appuntamenti::tipo', 'Tipo');
$administrator->administer('appuntamenti::operatore', 'Operatori/sportelli');
$administrator->administer('appuntamenti::orario', 'Orari');
$administrator->administer('appuntamenti::chiusura', 'Giorni chiusura');
if (!$_SESSION['operatore']) $administrator->administer('operatore', 'Amministratori');
$administrator->start();
if ($administrator->widgets['form']->structName == 'portal::servizioPrivato'){
  $autInput = & $administrator->widgets['form']->createInput('SelectInput', 'autenticazione');
  $autInput->generateFromArray(array('1'=>'Interna', '2'=>'Form', '3'=>'HTTP'));
  $autInput->setValue($administrator->widgets['form']->data->autenticazione);
}

$administrator->display();

include_once('bottom.php');

?>
