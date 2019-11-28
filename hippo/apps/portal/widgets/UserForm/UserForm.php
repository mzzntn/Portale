<?
include_once(BASE.'/widgets/Form/Form.php');

class UserForm extends Form{
  var $login;
  var $siti;
  var $sitiUtente;

  function buildRequests(){
    $requests = parent::buildRequests();
    $requests->request('user', 3);
    $requests->request('siti', 3);
    $requests->request('siti.id');
    $requests->request('siti.autenticazione');
    $requests->request('siti.nome');
    $requests->request('siti.descrizione');
    $requests->request('siti.login');
    $requests->request('siti.password');
    $requests->request('siti.url');
    $requests->request('siti.campoLogin');
    $requests->request('siti.campoPassword');
    return $requests;
  }

  function loadData(){
    parent::loadData();
    $loader = & $this->getLoader('portal::sito');
    $this->siti = $loader->load();
    if ($this->id){
      $loader = & $this->getLoader('portal::sito');
      $loader->setContext('portal::utente', 'siti', $this->id);
      $loader->requestAll();
      $loader->request('login');
      $loader->request('password');
      $this->sitiUtente = $loader->load();
    }
    //print_r($this->data);
  }

  
  function generateFromStructure(){
    parent::generateFromStructure();
    $sex = & $this->createInput('SelectInput', 'sesso');
    $sex->generateFromArray(array('M'=>'M', 'F'=>'F'));
    $siti = & $this->createInput('SelectInput', 'siti', 'portal::servizioPrivato');
    $siti->config['multiple'] = 1;
    $autenticazione = & $this->createInput('SelectInput', 'autenticazione');
    $autenticazione->generateFromArray(array(1=>'Integrata',2=>'Form',3=>'HTTP'));
  }
  
  function buildPelican(){
    global $IMP;
    $pelican = & parent::buildPelican();
    if ($_REQUEST['sitiUtente']) $pelican->_siti = $_REQUEST['sitiUtente'];
    $IMP->debug("Set sitiUtente:", 6);
    $IMP->debug($pelican->_siti, 6);
    return $pelican; 
  }

}


?>
