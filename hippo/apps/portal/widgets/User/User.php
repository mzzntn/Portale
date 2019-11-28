<?

class User extends DataWidget{
  var $login;
  var $password;
  var $avvisaSnail;
  var $avvisaEmail;
  var $adminPage;
  var $from;


  function setId($id){
    $this->id = $id;
  }
  
  function setUserId($id){
    $this->userId = $id;
  }
  
  function start(){
    if (!$this->id && !$this->userId) return;
    $loader = & $this->getLoader();
    if ($this->id) $loader->addParam('id', $this->id);
    elseif ($this->userId) $loader->addParam('user', $this->userId);
    $loader->requestAll();
    $loader->request('login');
    $loader->request('password');
    $loader->request('siti.nome');
    $list = $loader->load();
    $list->moveNext();
    $this->data = $list->getRow();
  }
  
  function setRecipient($email){
    $this->recipient = $email;
  }

  function setSender($email){
    $this->from = $email;
  }
  
  function prepareDisplayer(){
    parent::prepareDisplayer();
    $this->displayer->recipient = $this->recipient;    
  }

}



?>
