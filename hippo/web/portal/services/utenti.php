<?
ini_set('memory_limit', '50M');
include_once('../init.php');
include_once(PEAR.'/XML_RPC/RPC.php');
include_once(PEAR.'/XML_RPC/Server.php');

$ip = getip();
if (!in_array($ip, $C['portal']['services_ips'])) return;

$s = new XML_RPC_Server(array("portal.utenti" => array("function" => "getUtenti")));


function getUtenti($msg){
  global $IMP;
  $val = $msg->getParam(0);
  $data = $val->scalarval();
  $loader = & $IMP->getLoader('portal::utente');
  $loader->requestAll();
  $loader->request('siti', 3);
  $loader->addParam('mod_date', $data, '>');
  $list = $loader->load();
  return new XML_RPC_Response(new XML_RPC_Value($list->dumpToXML(), "string"));
}