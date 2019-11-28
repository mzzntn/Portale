<?

class ServiziPubblici extends DataWidget{
  
  function ServiziPubblici($name=''){
    parent::DataWidget($name, 'portal::servizioPubblico');
  }
  
  function start(){
    $loader = & $this->getLoader();
    $loader->addOrder('posizione');
    $this->servizi = $loader->load();
  }
  
}

?>
