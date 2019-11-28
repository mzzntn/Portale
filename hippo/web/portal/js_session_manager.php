<?
include_once('../init.php');
$allowedParams = array('tabella_albo_page','tabella_albo_full_page','table_delibere_full_page','tabella_albo_matrimonio_page', 'tabella_albo_completo_page');
print_r($_REQUEST);
foreach($allowedParams as $param) {
  if(isset($_REQUEST[$param])) {
    if(!isset($_SESSION['table_pages'])) {
      $_SESSION['table_pages'] = array();
    }
    $_SESSION['table_pages'][$param] = $_REQUEST[$param];
    print("['table_pages'][$param] set to {$_REQUEST[$param]}");
  }
}
?>