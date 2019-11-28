<?
global $IMP;

$crumbs = array
(
  "Home" => 'http://'.SERVER.HOME.'/portal/admin/',
);

$htmlHMenu = "";
$icons = array
(
  "Dati" => " glyphicon-list",
  "Dati IUC" => " glyphicon-check",
  "Dati IMU" => " glyphicon-home",
  "Dati TASI" => " glyphicon-trash",
  "Portal" => " glyphicon-user",
  "Caricamento Pratiche" => " glyphicon-file",
  "Dati Appuntamenti" => " glyphicon-calendar",
  "Trasparenza" => " glyphicon-eye-open",
  "Link e funzioni" => " glyphicon-link",
  "Gestione Utenti" => " glyphicon-user",
  "Impostazioni" => " glyphicon-check",
);
$tree = $W->widgets['menu']->tree;
$currentBranch = false;
while ($tree->moveNext())
{
  $css = "";
  $child = $tree->getName();
  $branch = $tree->$child;
  $tree->checkPelican($branch);
  if (is_object($branch)&&$branch->hasChildren())
  {
    $firstchild = false;
    $link = '#';
    while($branch->moveNext())
    {
      $branchchild = $branch->getName();
      if (is_object($branch->$branchchild))
      {
	if(!$firstchild)
	{	
	  $firstchild = $branchchild;	
	  $link = $branch->$branchchild->_link;
	}
	if($W->widgets['menu']->current == $branch->$branchchild->_name)
	{
	  $currentBranch = $tree->$child;
	  $css = " active";
	  $crumbs[$tree->$child->_label] = $link;
	  $crumb = $branch->$branchchild->_label;
	}
      }
    }
    $htmlHMenu .= '<a class="app '.$css.'" href="'.$link.'">
      <div>
	<img alt="'.$tree->$child->_label.'" src="'.URL_CSS."/img/".trim($icons[$tree->$child->_label]).'.png">
	<span class="name">'.$tree->$child->_label.'</span>
      </div>
    </a>';
  }
  $link = array();
  if ($C['portal']['spider_portal']){
	  $link['Amministrazione Portale'] = 'http://'.SERVER.HOME.'/portal/admin/';
  }
  if (defined('URL_APP_ALBO')){
	  $link['Inserimento manuale albo'] = 'http://'.SERVER.URL_APP_ALBO.'/inserimento.php';
  }
  if (defined('URL_APP_PRATICHE')){
	  $link['Cancellazione pratiche'] = 'http://'.SERVER.URL_APP_PRATICHE.'/pratiche_full.php';
  }
  if (defined('URL_APP_BENEFICI')){
	  $link['Amministrazione benefici'] = 'http://'.SERVER.URL_APP_BENEFICI.'/admin/';
  }
  if (defined('URL_APP_APPUNTAMENTI')){
	  $link['Gesitone appuntamenti'] = 'http://'.SERVER.URL_APP_APPUNTAMENTI.'/amministrazione/';
  }
  if (defined('PATH_APP_CARICAMENTO_PRATICHE')){
	  $link['Associazione multipla domande'] = 'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/domandeProc.php'; 
	  $link['Gestione Istanze Online'] = 'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/';
	  $link['Configurazione Caricamento pratiche'] =  'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/configurazione.php';
  }
}      
?>
<div id="header">
  <div id="header_top">
    <div id="main_title">
      <h1>Amministrazione - <?=$currentBranch->_label?></h1>
    </div>
    <div id="admin_controls">
      <?if ($IMP->security->login){?><span class="welcome">Benvenuto, <b><?=$IMP->security->login?></b></span><?}?> - 
      <a href='./'>Home</a> - 
      <a href='./login.php?action=logout' class="logout_link">Logout</a>
    </div>
  </div>
  <div id="app_buttons">
      <?=$htmlHMenu?>
  </div>
</div>

<div id="admin" class="spider components admin widget wdgt-Spider-Components-Admin ">
  <div id="admin-switcher" class="spider-admin-container widget wdgt-Spider-Components-Switcher ">
    <div class="sidebar spider-admin-sidebar">
      <div class="sidebar-app-info"><h2><?=$currentBranch->_label?></h2></div>
      <div id="admin-switcher-menu" class="spider components menu widget wdgt-Spider-Components-Menu ">
	<?
        echo "<ul class='section_items'>";
        
        while ($currentBranch->moveNext())
        {
	  $child = $currentBranch->getName();
	  if (is_object($currentBranch->$child))
	  {
	    $currentBranch->checkPelican($currentBranch->$child);
	    
	    $css = 'inactive';
	    if ($W->widgets['menu']->current && $W->widgets['menu']->current == $currentBranch->$child->_name) {$css = 'active';  $crumbs[$currentBranch->$child->_label] = $currentBranch->$child->_link;}
	    echo '<li class="'.$css.'"><a href="'.$currentBranch->$child->_link.'">'.$currentBranch->$child->_label.'</a></li>';
	  }
	}
	
	echo "</ul>";
	?>
	</div>
   <?
    if (!$_SESSION['operatore']){
   ?>
	<div class="sidebar-app-info"><h5>Link e funzioni</h5></div>
	 <div id="admin-switcher-menu" class="spider components menu widget wdgt-Spider-Components-Menu ">
	 <?
	  echo "<ul class='section_items'>";
	    foreach($link as $label=>$url)
	    {
	      echo "<li class='inactive'><a href='$url'>$label</a></li>";
	    }
	  echo "</ul>";
	 ?>
     </div>
    <? } ?>
      <div class="id-menu_bottom" id="admin-switcher-menu-menu_bottom"></div>
    </div>
    
    <div id="admin-switcher-content" class="content spider-admin-content id-content">
      <ul class="breadcrumb">
	<?
	foreach($crumbs as $label => $url)
	{
	  if($crumb != $label)
	  {
	    echo "<li>
	    <a href=\"$url\">$label</a> <span class=\"divider\">/</span>
	    </li>";
	  }
	  else
	  {
	    echo "<li class=\"active\">$crumb</li>";
	  }
	}
	?>
      </ul>

      <div id="admin-switcher-portal_servizio" class="spider components crud widget wdgt-Spider-Components-Crud ">
	<div class="alert alert-success hide" id='success-message'></div>
	<div class="crud-actions">
	  <div class="table_search">
	  <?
	  if($W->action == 'table'){
	    $W->widgets['search']->display();  
	  }
	  ?>
	  </div>
	  
      	  <?
	  if (($W->action == 'form' || $W->action == 'table') && $W->checkInsertAllowed()){
	  ?>    
	    <div class="add-item">
	      <a class="add" href='<?=$_SERVER['PHP_SELF']?>?<?=$this->w->name?>[action]=form&_clear[form_<?=$this->w->currentStructName?>]=1'> Crea nuovo </a>
	    </div>
	  <?
	  }
	  ?>
	</div>

	<div id="admin-switcher-portal_servizio-table" class="spider components table widget wdgt-Spider-Components-Table ">

	  <div class="spider components table">

	  <?
	  if($W->action == 'form'){
	    $W->widgets['form']->loadDisplayer();
	    $W->widgets['form']->displayer->formStart();
	    $W->widgets['form']->display();
	    $W->widgets['form']->displayer->formEnd();
	  }
	  elseif($W->action == 'table'){
// 	    $W->widgets['search']->display();  
	    $W->widgets['table']->display();    
	  }
	  elseif($W->action == 'customPage'){
	    require($W->customPage);
	  }
	  ?>
	  </div>
	  <?
	  if ($W->sideTabs && $W->sideTabs->size() > 0) $W->sideTabs->display();
	  if ($W->config['perms'] && $IMP->security->checkSuperUser()){
	  ?>
	  <iframe width='100%' src='permsManager.php?struct=<?=$W->getParam('currentStruct')?>&id=<?=$W->widgets['form']->getParam('id')?>'></iframe>
	  <?
	  }
	  ?>
	  </div>
	</div>
      </div>
    </div>
  </div>
    
  
</div>
</div>

