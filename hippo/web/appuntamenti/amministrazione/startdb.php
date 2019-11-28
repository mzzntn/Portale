<?
include_once('../../init.php');

$loader = & $IMP->getLoader('appuntamenti::stato');
$loader->requestAll();
$stati = $loader->load();
$stato = $stati->get('id');
if (!$stato){
  $valori = array('1' => 'Richiesto', '2' => 'Confermato', 3 => 'Rifiutato', '4' => 'Eliminato');
  $bindingStato = $IMP->bindingManager->getBinding('appuntamenti::stato');
  $db = $bindingStato->getDbObject();
  foreach ($valori as $a => $b){
    $sql = "INSERT INTO {$bindingStato->table} (ID, STATO) VALUES ({$a}, '{$b}')";
    $db->execute($sql);
  }
}
?>
