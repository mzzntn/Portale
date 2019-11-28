<?
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
    <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
      <thead>
	<tr>
	  <th></th>
	  <th>Tipo</th>
	  <th>Numero</th>
	  <th>Descrizione</th>
	  <th>Data Pratica</th>
	  <th>Data Pubblicazione</th>
	</tr>
      </thead>
      <tbody>
	<?
	while ($W->data->moveNext()){
	  $descrizione = $W->data->get('descrizione');
	  if ( strlen($descrizione) > 255) $descrizione = substr($descrizione, 0, 255).'...';
	?>
	      <tr>
		<td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
		<td><?=$W->data->get('tipo.nome')?></td>
		<td align='center'><?=$W->data->get('cod2')?></td>
		<td><?=$descrizione?></td>
		<td><?=dateToUser($W->data->get('inizio'))?></td>
		<td><?=dateToUser($W->data->get('iter.data'))?></td>
	      </tr>
	<?
	}
	?>
      </tbody>
    </table>
    <input type='hidden' id='items_per_page' value="15" />  
    <input type='hidden' id='show_per_page' value="15" />  
    <input type='hidden' id='current_page' value="1" />  
    <input type='hidden' id='max_page_in_navbar' value="7"/>
    <div class="page_navigation pagination" id='paginator_div'><ul></ul></div>
    <?
      $current_page = 1;
      $paramName = $this->name."_full_page";
      if(isset($_SESSION['table_pages']) && isset($_SESSION['table_pages'][$paramName])) {
	$current_page = $_SESSION['table_pages'][$paramName];
      }
    ?>
    <script type='text/javascript'>
      var page = <?=is_numeric($current_page)?$current_page:0?>;
      var current_page = $('#current_page').val();
      if(!page && current_page) {page=current_page;}
      var show_per_page = parseInt($('body').find("#items_per_page").val());
      var number_of_items = parseInt($('body').find('.pagination_content .paginated_element').size());
      var last_page_number = Math.ceil(number_of_items/show_per_page);
      
      
      function paginatorListeners($element) {
	var ulContent = $element.find('ul').html();
	if(ulContent) {
	  $element.html(ulContent);
	}
	$element.find('li').each(function() {
	  $(this).find('a').click(function() {
	    var href = $(this).attr('href');
	    var targetPage = 1;
	    if(href.indexOf("go_to_page") > -1) {
	      targetPage = href.replace("javascript:go_to_page(","").replace(", 'body')","").replace(",'body')","");
	    }
	    else if(href.indexOf("first_page") > -1) {
	      targetPage = 1;
	    }
	    else if(href.indexOf("previous") > -1) {
	      targetPage = parseInt($('body').find('#current_page').val())-1;
	    }
	    else if(href.indexOf("next") > -1) {
	      targetPage = parseInt($('body').find('#current_page').val())+1;
	    }
	    else if(href.indexOf("last_page") > -1) {
	      targetPage = last_page_number;
	    }
	    if(targetPage<1) {targetPage=1;}
	    console.log("setting target to "+targetPage);
	    $.post( "<?=HOME?>/portal/js_session_manager.php", { <?=$paramName?>: targetPage } );
	  });
	});
      }
      
      if(number_of_items>show_per_page) {
	
	init_paginatore();
	
	paginatorListeners($('#paginator_div'));
	paginatorListeners($('.lista_pagine'));
	$('#paginator_div').bind("DOMSubtreeModified",function(event){
	  paginatorListeners($(this));
	  paginatorListeners($('.lista_pagine'));
	  current_page = $('#current_page').val();
	});
	
	if (page) { javascript:go_to_page(page, 'body'); } 
      } else {
	$('#paginator_div').remove();
      }
      
      $('.row_linked tbody tr').each(function() {
	$(this).css( "cursor", "pointer" );
	$(this).click( function() {
	    window.location = $(this).find('a').attr('href');
	}).hover( function() {
	    $(this).toggleClass('hover');
	});
      });
    </script>
<?
}
else {
include_once(LIBS.'/Widgets/PageNavigator.class.php');
?>
<div class='contenitore risultati'>
  <table class='rowList center' border='0' >

    <tbody id='<?=$D->name?>'>
      <tr>
        <th></th>
        <th>Tipo</th>
        <th>Numero</th>
        <th>Descrizione</th>
        <th>Data Pratica</th>
        <th>Data Pubblicazione</th>
        <!--<th>Data Provvedimento</th>-->
      </tr>
<?
while ($W->data->moveNext()){
  $descrizione = $W->data->get('descrizione');
  if ( strlen($descrizione) > 255) $descrizione = substr($descrizione, 0, 255).'...';
?>
      <tr>
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
        <td><?=$W->data->get('tipo.nome')?></td>
        <td align='center'><?=$W->data->get('cod2')?></td>
        <td><?=$descrizione?></td>
        <td><?=dateToUser($W->data->get('inizio'))?></td>
        <td><?=dateToUser($W->data->get('iter.data'))?></td>
      </tr>
<?
}
?>
    </tbody>
  </table>
  <div class='bottoni navigazione'><p>
<?
if ($W->config['maxElements'] && $W->resultRows) $showPageNavigator = true;

if($showPageNavigator)
{
    $pageNavigator = new PageNavigator($W->resultRows);
    $pageNavigator->setItemsPerPage($W->config['maxRows']);
    $pageNavigator->setTableName($W->name);
    $pageNavigator->setDisplayStyle(PageNavigator::DISPLAY_FULL); // mostra precedente e successivo al posto delle frecce
    //$pageNavigator->displayPageJump(true); // mostra il menu scelta pagina
}

if($showPageNavigator)
{   
    $pageNavigator->display($_GET[$W->name]["page"]);
}

?>
   </p>
  </div>
</div>
<? } ?>