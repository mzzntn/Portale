<?

class ServiziPubblici_new extends DataWidget{
  
  function ServiziPubblici_new($name=''){
    parent::DataWidget($name, 'portal::servizioPubblico');
  }
  
  function start(){
    $loader = & $this->getLoader();
    $loader->addOrder('posizione');
    $this->servizi = $loader->load();
  }
  
}

?>
