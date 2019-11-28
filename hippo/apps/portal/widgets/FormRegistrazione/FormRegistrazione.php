<?
include_once(BASE.'/widgets/Form/Form.php');

class FormRegistrazione extends Form{
  
  function FormRegistrazione($name, $structName=''){
    parent::Form($name, $structName);
  }
  
  function generateFromStructure(){
    parent::generateFromStructure();
    #$loginInput = & $this->createInput('TextInput', 'login');
    $login = & $this->createInput('TextInput', 'login');
    $siti = & $this->createInput('SelectInput', 'siti', 'portal::servizioPrivato');
    $siti->config['multiple'] = true;
    $siti->setTemplate('checkbox');
    $siti->generateFromStructure();
    $this->inputs->provincia->config['size'] = 4;
  }
  
  function checkData($data=0){
    if (!$data) $data = $this->data;
    parent::checkData($data);
    $login = $this->getParam('login');
    if ($login && !$this->id){
      $loader = & $this->getLoader('_security_user');
      $loader->addParam('login', $login);
      $list = $loader->load();
      if ($list->moveNext()){
        $this->addError('login', "E' già presente un utente registrato con questa login. Per favore, scelga un'altra login.");
      }
    }
    elseif ($this->hasData() && !$this->id){
      $this->addError('login', "E' necessario scegliere un nome utente.");
    }
    return !$this->error;
  }
  
  function storeData(){
    global $IMP;
    if ($this->id){
      if ($_REQUEST['old']) {
       $loaderpsw = & $IMP->getLoader('_security_user');
        $loaderpsw->addParam('login', $IMP->security->login);
        $loaderpsw->addParam('password', $_REQUEST['old']);
        $listpsw = $loaderpsw->load();
        if (!$listpsw->get('login')){
          $this->addError('old', 'La password corrente non è corretta');
        }
        if (!$_REQUEST['new1']) $this->addError('new1', 'Devi indicare la nuova password');
        elseif ($_REQUEST['new1'] != $_REQUEST['new2']) $this->addError('new1',  'Le due password non coincidono');
        elseif (strlen($_REQUEST['new1']) < 8)			$this->addError('new1', 'La password deve essere di almeno 8 caratteri');
        if (!$this->error){
          $IMP->security->changePassword($_REQUEST['old'], $_REQUEST['new1']);
          $this->changed_password = true;
        }
      }
    }
    if (!$this->error) return parent::storeData();
  }

  function checkElement($elementName, $elementValue){
	  if ($this->id && $elementName == 'login') return true;
	  return parent::checkElement($elementName, $elementValue);
  }

  function sendRegistrationMails($id, $utente, $isDitta=0){
    global $IMP;
    global $C;
    if (!$id) return;
    if ($isDitta) $struct = $C['portal']['struttura_ditte'];
    else $struct = $C['portal']['struttura_utenti'];
    $user = & $IMP->getWidget('portal::User');
    $user->setStruct($struct);
    $user->setId($id);
    $user->avvisaEmail = $_REQUEST['rcv_email'];
    $user->avvisaSnail = $_REQUEST['rcv_snail'];

    $user->adminPage = 'http://'.SERVER.HOME.'/'.$C['portal']['url_amministrazione'].'?administrator[currentStruct]='.$struct.'&administrator[action]=form&form_'.$struct.'[id]';
    $user->start();
    $user->setDisplayMode('email');
    if ($utente){
      $user->setTemplate('modify');
    }
    else{
      $user->setTemplate('request');
    }
    $user->setSender($C['portal']['email_from']);
    $user->setRecipient($C['ente']['email_amministratore_servizi']);
    $user->display();
  }
  
}

?>
