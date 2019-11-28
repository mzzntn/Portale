<div class="mhead left">Sezione Pubblica</div>
<div class="mbody left">
    <a href='index.php'>Indice</a>
<?
global $IMP;
$loader = & $IMP->getLoader('portal::servizioPubblico');
$estr = $loader->load();
while ($estr->moveNext()){
  $url = $estr->get('url');
  $codEstr = $estr->get('codEstr');
  if (!strstr($url, 'http://')) $url = HOME.'/'.$url;
  if ($codEstr) $url .= '?codEstr='.$codEstr;
?>
    <a class='menuRow' href='<?=$url?>'><?=$estr->get('nome')?></a>
<?
}
?>			     
</div>        			         
