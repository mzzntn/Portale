<?
if ($W->resultRows > 0){
 $tableDisplay = 'inline';
 $noResultDisplay = 'none';
}
else{
  $tableDisplay = 'none';
  $noResultDisplay = 'inline';
}


if(isset($_GET['administrator']))
{
  // :FIXME: this should be managed in a better way, check if we're on the administrator widget (why $W->config['contextAdmin'] is false?)
  $adminUrl = $W->config["admin"];
  if(strpos($adminUrl, "administrator[widget]")===false)
  {
    //&target={$W->name}&{$W->name[start]}={$_GET["$W->name"]["start"]}
    $adminUrl = str_replace("?","?administrator[widget]=".$W->structName."&", $adminUrl);
  }
  $W->config["admin"] = $adminUrl;
}
?>
<p id="<?=$D->name?>_noresult" style="display: <?=$noResultDisplay?>"><?=$W->config['noResultString']?></p>
<? 
$D->loadJs('table'); 
?>
<?
if ($W->config['contextAdmin']){
?>
<input type='hidden' name='el' value='<?=$_REQUEST['el']?>'>
<input type='hidden' name='id' value='<?=$_REQUEST['id']?>'>
<?
}
?>
<div id='<?=$D->name?>_hiddenDiv' style='display: none'>
	<table class="table-bordered table-striped">
	  <tbody id='<?=$D->name?>_htmlContent'>
	  <?
		while ($W->data->moveNext()){
		  print "<tr>";
		  $id = $W->data->get('id');
		  print "<td>";
		  if ($W->config['admin']) print "<a href='{$W->config['admin']}$id'>";
		  print $id;
		  if ($W->config['admin']) print "</a>";
		  foreach ($W->elements as $elementName){
		if ($elementName == 'id') continue;
		print "<td>";
		print $W->data->get($elementName);
		print "</td>";
		  } 
		  print "<td><input type='checkbox'></td></tr>\n";
		}
	  ?>
	  </tbody>
  </table>
</div>
<script type='text/javascript'>
var element = document.getElementById("<?=$D->name?>_hiddenDiv");
element.parentNode.removeChild(element);
</script>
<div id='<?=$D->name?>_tableDiv' style='display: <?=$tableDisplay?>'>
    <!--<table cellpadding='0' cellspacing='0' border='0'>
<?
if ($W->config['maxRows']){
  if ($W->resultRows > $W->config['maxRows']) $displayNext = 'inline';
  else $displayNext = 'none';
  $numPages = ceil($W->resultRows/$W->config['maxRows']);
  if ($numPages < 2) $displayLinks = 'none';
  else $displayLinks = 'inline';
?>
<?
    if ($W->config['upperTableLinks']){
?>
        <tr>
            <td id='tableLinks2' style='display: <?=$displayLinks?>'></td>
        </tr>
<?
}
?>
<?
}
?>
        <tr>
            <td>
                <table cellpadding='0' cellspacing='0' class='rowList' id='<?=$D->name?>_tableEl'>-->
		<table class="table-bordered table-striped">
                    <thead>
<?
        if ($W->config['heading']){
?>
                    
                        <tr>
                            <th class="heading" colspan='<?=sizeof($W->elements)?>'>
                                <?=$W->config['heading'] ?>
                            </th>
                        </tr>
<?
        }
?>
                        <tr>
<?
       $cnt = -1;   
       if ($W->config['showHeadings']) foreach ($W->elements as $elementName){
           $cnt++;
           if ($elementName == 'id' && !$W->config['showId']) continue;
           if ($elementName == 'id' && $W->config['idLinkLabel']) $label = '';
           else $label = $D->label($elementName);
                         
?>
                            <th>
<?

            if ($W->config['sortable'] && !($W->config['contextAdmin'] && $W->contextElements[$elementName]) && !$W->config['notSortable'][$elementName]){
            
	      if(!is_array($W->config['excludeSorting']) || !in_array($elementName, $W->config['excludeSorting'])){                           
?>
                                <a href='#' id='a_<?=$D->name.$cnt?>'><?= $label ?></a>
<?
	      } else {
	      ?><strong><?= $label ?></strong><?
	      }
            }
            else  print $label; 
?>
                            </th>
<? 
            } 
?>
			  <th></th>
		      </tr>
                    </thead>
                    <tbody id='<?=$D->name?>'>
                    </tbody>
			  <?
        /*if ($W->config['allowDelete']){
?>
                    
                        <tr>
                            <th colspan='<?=sizeof($W->elements)+1?>' class="text-right">
								<a class="btn btn-danger" href='javascript: <?=$D->name?>_del()' name="_w[admin][switcher][portal_servizio][delete]">Cancella selezionati&nbsp;&nbsp;</a>
                            </th>
                        </tr>
<?
        }*/
?>
                </table>
            <!--</td>
        </tr>
    </table>  -->
</div>  

<?
            if ($W->config['maxRows']){
?>
<div class="boxSpacer">&nbsp;</div>

<div class="pagination" style='display: <?=$displayLinks?>'>

  <ul>
    <li class="disabled">
      <a href='' id='<?=$D->name?>_linkFirst' class='tableMove' class="first disabled ajaxified">	&#171;</a>
    </li>
    <li class="disabled">
      <a href='' id='<?=$D->name?>_linkPrev' class='tableMove' class="prev disabled ajaxified">&#8249;</a>
    </li>
      <?
	$numeroPagina = 1;
	$htmlLinks = "";
	$htmlSelect = "";
	
	for ($i=1; $i<=$numPages; $i++){
	    $htmlLinks .= "<li";
	    if ($i+1 == $numeroPagina) $htmlLinks .= " class='active'";
	    $htmlLinks .= "><a href='javascript: {$D->name}_page($i)' id='{$D->name}_linkPage_$i' class='ajaxified pageLink";
	    if ($i+1 == $numeroPagina) $htmlLinks .= " current";
	    else $htmlLinks .= " page";
	    $htmlLinks .= "'>".($i)."</a></li>";
	    	    
	    $htmlSelect .= "<option value='$i' ";
	    if ($i+1 == $numeroPagina) $htmlSelect .= "selected='selected'";
	    $htmlSelect .= ">".($i)."</option>";
	}
	echo $htmlLinks;
      ?>
    <li>	
      <a style='display: <?=$displayNext?>' href='javascript: <?=$D->name?>_page(2)' id='<?=$D->name?>_linkNext' class='ajaxified'>&#8250;</a>
    </li>
    <li>
      <a href='' id='<?=$D->name?>_linkLast' class='tableMove' class="ajaxified">&#187;</a>
    </li>
  </ul>
  <select onchange="javascript: <?=$D->name?>_page(this.value)" id='<?=$D->name?>_pageNum_select' style='margin-top: -20px;'>
  <?=$htmlSelect?>
  </select>

</div>
<?
                /*$numeroPagina = 1;
                for ($i=1; $i<=$numPages; $i++){
                    print "<option value='$i' ";
                    if ($i+1 == $numeroPagina) print "selected='selected'";
                    print ">".($i)."</option>";
                }*/
				
            }
?>
<?
if ($W->config['allowDelete']){
?>
  <div class="crud_table_actions">
    <a class="btn btn-default" href='<?=$_SERVER['PHP_SELF']?>?administrator[action]=form&administrator[widget]=<?=$D->w->structName?>&_clear[form_<?=$D->w->structName?>]=1&context[<?=$D->w->context['struct']?>]=<?=$D->w->context['id']?>'>Aggiungi nuovo</a>
<?
if (!$_SESSION['noCancella']){
?>
    <a class="btn btn-danger" href='javascript: <?=$D->name?>_del()' name="_w[admin][switcher][portal_servizio][delete]">Cancella selezionati</a>
<? } ?>
  </div>
<?
}
?>
<script type="text/javascript">
var table_<?=$D->name?> = new Table('<?=$D->name?>');
var t = table_<?=$D->name?>;
t.name = '<?=$D->name?>';
t.className = '<?=$D->getCSSClass()?>';
<?
            foreach ($W->config['jsConfig'] as $key){
                if ($key == 'action' && $W->config[$key]){
?>
t.config.<?=$key?> = <?=$W->config[$key]?>;
<?
            }
            else{
?>
t.config.<?=$key?> = <?=varToJs($W->config[$key])?>;
<?
            }
        }
?>

<?
        if ($W->config['checkWritable']){
?>
t.writable = <?=varToJs($W->writable)?>
<?
        }
?>

var <?=$D->name?>_sorting = 0;
function <?=$D->name?>_sortTable(e, el){
e = normalizeEvent(e);
a = e.target;
var dir;
if (a.sorting == 'asc') dir = 'desc';
else dir = 'asc';
if (<?=$D->name?>_sorting != a) <?=$D->name?>_sorting.dir = '';
<?=$D->name?>_sorting = a;
a.sorting = dir;
//   console.log("request for ordering on "+'<?=$W->config['postUrl']?>'+'target=<?=$W->name?>&<?=$W->name?>[sort]['+el+']='+dir);
  xmlHttpQuery('<?=$W->config['postUrl']?>', 'target=<?=$W->name?>&<?=$W->name?>[sort]['+el+']='+dir, '', '<?=$W->name?>');
  <?=$D->name?>_startQuery();
<?
        if ($W->config['maxRows']){
?>
  <?=$D->name?>_page(1, true);
<?
        }
?>
        return false;
}
<?
        if ($W->config['maxRows']){
?>
//optional: noFetch
var <?=$D->name?>_currentPage = 1;
var <?=$D->name?>_resultRows = <?=$W->resultRows?>;



function <?=$D->name?>_page(num){
  num = parseInt(num);
  var noFetch = arguments[1];
  var start = <?=$W->config['maxRows']?>*(num-1)+1;
  var end = Math.ceil(<?=$D->name?>_resultRows/<?=$W->config['maxRows']?>);
  if (start > <?=$D->name?>_resultRows) return;
  if (!noFetch){
    xmlHttpQuery('<?=$W->config['postUrl']?>', 'target=<?=$W->name?>&<?=$W->name?>[start]='+start, '', '<?=$W->name?>');
    <?=$D->name?>_startQuery();
  }
  var prev=num-1;
  var next=num+1;
  
  // eliminiamo tutti i link pagina selezionati usando jquery
  $('.pagination').find('li.active').attr('class', '');
  
  if (start+<?=$W->config['maxRows']?> > <?=$D->name?>_resultRows) next=0;
  linkFirst = getObj('<?=$D->name?>_linkFirst');
  linkLast = getObj('<?=$D->name?>_linkLast');
  linkPrev = getObj('<?=$D->name?>_linkPrev');
  linkNext = getObj('<?=$D->name?>_linkNext');
  linkPage = getObj('<?=$D->name?>_linkPage_'+num);
  linkPage.className = 'current ajaxified pageLink';
  linkPage.parentNode.className = 'active';
  if (prev > 0){
//     linkPrev.style.display = '';
    linkPrev.className = 'prev ajaxified';
    linkPrev.parentNode.className = '';
    linkPrev.href = "javascript: <?=$D->name?>_page("+prev+")";
    linkFirst.className = 'first ajaxified';
    linkFirst.parentNode.className = '';
    linkFirst.href = "javascript: <?=$D->name?>_page(1)";
  }
//   else linkPrev.style.display = 'none';
  else
  {
    linkPrev.className = 'prev disabled ajaxified';
    linkPrev.parentNode.className = 'disabled';
    linkPrev.href = '#';
    linkFirst.className = 'first disabled ajaxified';
    linkFirst.parentNode.className = 'disabled';
    linkFirst.href = '#';
  }
  if (next){
//     linkNext.style.display = '';
    linkNext.className = 'next ajaxified';
    linkNext.parentNode.className = '';
    linkNext.href = "javascript: <?=$D->name?>_page("+next+")";
    linkLast.className = 'last ajaxified';
    linkLast.parentNode.className = '';
    linkLast.href = "javascript: <?=$D->name?>_page("+end+")";
  }
//   else linkNext.style.display = 'none';
  else 
  {
    linkNext.className = 'next disabled ajaxified';
    linkNext.parentNode.className = 'disabled';
    linkNext.href = '#';
    linkLast.className = 'last disabled ajaxified';
    linkLast.parentNode.className = 'disabled';
    linkLast.href = '#';
  }

  <?=$D->name?>_currentPage = num;
  var pageNav = getObj('pageNav');
  var tableLinks2 = getObj('tableLinks2');
  if (tableLinks2){
    tableLinks2.innerHTML = pageNav.innerHTML;
    for (var i in tableLinks2.childNodes){
      node = tableLinks2.childNodes[i];
      if (node.nodeType != 1) continue;
      node.id += '2';
      //alert(node.nodeName);
    }
  }
  getObj('<?=$D->name?>_pageNum_select').selectedIndex = num-1;
  if (getObj('<?=$D->name?>_pageNum_select2')) getObj('<?=$D->name?>_pageNum_select2').selectedIndex = num-1;
  
  $('.pagination').find('a.pageLink').each(function()
  {
    var range = 12;
    
    var number = $(this).attr('id').replace('<?=$D->name?>_linkPage_','');
    $(this).html(number);
    $(this).parent().show();
    
    var rangeStart = num-range/2;
    if(rangeStart<1){rangeStart=1;}
    var rangeEnd = rangeStart + range-1;
    if(!$('#<?=$D->name?>_linkPage_'+rangeEnd).length)
    {
      while(!$('#<?=$D->name?>_linkPage_'+rangeEnd).length)
      {
	rangeEnd--;
      }
      rangeStart = rangeEnd-range+1;
    }
    //if(!next){rangeEnd=num; rangeStart = rangeEnd-range;}
    
    if(number==rangeStart-1 || number==rangeEnd+1)
    {
      $(this).html('...');
    }
    else if(number >= rangeStart && number <= rangeEnd)
    {
      $(this).parent().show();
    }
    else
    {
      $(this).parent().hide();
    }
    /*else if( (number>range&&num<=range/2) || num>range/2 && (number < num-range/2 || number>num-1+range/2) )
    {
      $(this).parent().hide();
    }
    else
    {
      $(this).parent().show();
    }*/
  });
}


<?
        }
?>
<?
        $cnt = -1;
        foreach ($W->elements as $elementName){
        $cnt++;
?>
  t.columns['<?=$elementName?>'] = <?=$cnt?>;
  t.names[<?=$cnt?>] = '<?=$elementName?>';
<?
//   echo "console.log('".print_r($W->config['excludeSorting'],true)."')";
        if ($elementName == 'id' && !$W->config['showId']) continue;
        if ($W->config['showHeadings'] && $W->config['sortable'] && !($W->config['contextAdmin'] && $W->contextElements[$elementName]) && (!is_array($W->config['excludeSorting']) || !in_array($elementName, $W->config['excludeSorting']))){
?>

  var a = getObj('a_<?=$D->name.$cnt?>');
//   console.log("adding sort table for field <?=$elementName?>");
  if (a) a.onclick = function(e){ 
    <?=$D->name?>_sortTable(e, '<?=$elementName?>'); 
  };
<?
    }
?>    
<? 
} 
if ($W->config['allowDelete']){
?>
function <?=$D->name?>_del(){
  if (confirm("Vuoi veramente cancellare gli elementi selezionati?")){
    table_<?=$D->name?>.sendSelected("<?=$W->config['postUrl']?>?", "<?=$W->name?>[del]");
  }
}
<?
}
?>
function <?=$D->name?>_add(){
  var url = '<?=$W->config['postUrl']?>';
  var w_name = '<?=$W->name?>';
  w_name = w_name.replace(/(::)[^_]+_tab/g, "$1").replace('form_','').replace('rilevazioni','rilevazione');
  window.location.href = url+"?administrator[action]=form&administrator[widget]="+w_name.replace('::','%3A%3A')+"&[widget]="+w_name+"&_clear["+w_name+"]=1&form_"+w_name+"[id]=0";
  ///portal/admin/index.php?form_trasparenza::procedimento_tabmodulistica[del]
  //administrator[action]=form&_clear[form_trasparenza::modulistica]=1
}

var queryFromSearch;

function <?=$D->name?>_getQueryInfo(xml){
  var tot = 0;
  var totNode = xml.getElementsByTagName('totalRows').item(0);
  for (var i=0; i<totNode.childNodes.length; i++){
  //for (var i in totNode.childNodes){ //look for text node
    if (totNode.childNodes[i].nodeType != 3) continue;
    tot = totNode.childNodes[i].nodeValue;
  }
  if (tot > 0){
    $('#<?=$D->name?>_noresult').hide();
    $('#<?=$D->name?>_tableDiv').show();
    //getObj('<?=$D->name?>_noresult').style.display='none';
    //getObj('<?=$D->name?>_tableDiv').style.display='';
  }
  else{
    $('#<?=$D->name?>_noresult').show();
    $('#<?=$D->name?>_tableDiv').hide();
    //getObj('<?=$D->name?>_noresult').style.display='';
    //getObj('<?=$D->name?>_tableDiv').style.display='none';
  }
  //alert(tot);
  if (true){
    var pages = Math.ceil(tot/<?=$W->config['maxRows']?>);
    //alert(pages);
    if (pages > 1){
      //getObj('pageNav').style.display = '';
      $('.pagination').show();
      $('.crud_table_actions').show();
      if (getObj('tableLinks2')) getObj('tableLinks2').style.display = '';
      /*var pagesSpan = getObj('<?=$D->name?>_numPages');
      pagesSpan.innerHTML = pages;
      <?=$D->name?>_resultRows = tot;
      <?=$D->name?>_setSelectPages('<?=$D->name?>_pageNum_select', pages);
      <?=$D->name?>_setSelectPages('<?=$D->name?>_pageNum_select2', pages);*/
      if (queryFromSearch) <?=$D->name?>_currentPage = 1;
      queryFromSearch = 0;
      <?=$D->name?>_page(<?=$D->name?>_currentPage, 1); //fixes links
    }
    else{
      //getObj('pageNav').style.display = 'none';
      $('.pagination').hide();
      if(tot<1){$('.crud_table_actions').hide();}
      if (getObj('tableLinks2')) getObj('tableLinks2').style.display = 'none';
    }
  }
}

function <?=$D->name?>_setSelectPages(select, n){
  var s = getObj(select);
  if (!s) return;
  s.innerHTML = '';
  for(var i=1; i<=n; i++){
    var o = document.createElement('option');
    o.value = i;
    o.innerHTML = i;
    s.appendChild(o);
  }
}

function <?=$D->name?>_searchFormQuery(){
  queryFromSearch = 1;
}

function <?=$D->name?>_startQuery(){
  var tableEl = getObj('<?=$D->name?>');
  tableEl.style.color = '#336699';
}

<?
 $cnt = -1;

while ($W->data->moveNext()){
    $cnt++;
    print "t.d[$cnt] = new Array();\n";
    print "t.o[$cnt] = $cnt;\n";
    $el = -1;
    foreach ($W->elements as $elementName){
        $el++;
        $data = $W->data->get($elementName);
        $data = str_replace("'", "\'", $data);
        $data = str_replace("\n", '', $data);
        $data = str_replace("\r", '', $data);
        print "t.d[$cnt][$el] = '".$data."';\n";
    } 
}
?>
t.rebuild();
addXmlHttpHandler('<?=$D->name?>', function(xml){table_<?=$D->name?>.loadXml(xml);});
addXmlHttpHandler('<?=$D->name?>_queryInfo', function(xml){<?=$D->name?>_getQueryInfo(xml);});
<?
if ($W->config['maxRows']){
    $currentPage = ($W->getParam('start')/$W->config['maxRows'])+1;
    // questo serve per tenere in memoria la pagina corrente quando ci si sposta da tabella a form e viceversa
    if(isset($_SESSION['table_pages'][$W->structName."_start"])) {
      $currentPage = ($_SESSION['table_pages'][$W->structName."_start"]/$W->config['maxRows'])+1;
    }
?>
<?=$D->name?>_page(<?=$currentPage?>, true);
<?
}
?>
</script>
