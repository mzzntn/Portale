<?php
/* Classe per gestire la paginazione dei risultati.
 * 
 * Utilizzo:
 * $pnav = new PageNavigator();
 * $pnav->display($pagina);
 *
 */
class PageNavigator
{
  const DISPLAY_COMPACT = 0;
  const DISPLAY_FULL = 1;
  const DISPLAY_MOBILE = 2;
  var $itemsPerPage;
  var $currentPage;
  var $displayPages;
  var $totItems;
  var $displayStyle;
  var $tableName;
  var $displayPageJump;

  public function __construct($totItems)
  {
    $this->itemsPerPage = 10;
    $this->currentPage = 1;
    $this->displayPages = 5;
    $this->totItems = $totItems;
    $this->displayStyle = 0;
    $this->displayPageJump = false;
  }

  public function displayPageJump($displayPageJump)
  {
    $this->displayPageJump = $displayPageJump;
  }

  public function setDisplayStyle($displayStyle)
  {
    $this->displayStyle = $displayStyle;
  }

  public function setItemsPerPage($itemsPerPage)
  {
    $this->itemsPerPage = $itemsPerPage;
  }

  public function setCurrentPage($currentPage)
  {
    $this->currentPage = $currentPage;
  }
  public function setTotItems($totItems)
  {
    $this->totItems = $totItems;
  }

  public function setTableName($tableName)
  {
    $this->tableName = $tableName;
  }

  public function setDisplayPages($displayPages)
  {
    $this->displayPages = $displayPages;
  }

  public function getStartItem()
  {
    return ($this->currentPage*$this->itemsPerPage)-$this->itemsPerPage;
  }

  public function getEndItem()
  {
    return $this->currentPage*$this->itemsPerPage;
  }

/*  public function getUrlString($page)
  {
      if ($start == 15) $start = 16;
      return "{$_SERVER['PHP_SELF']}?{$this->tableName}[page]={$page}&{$this->tableName}[start]=".($page*$this->itemsPerPage-$this->itemsPerPage);
}*/

  public function getUrlString($page)
  {
      $start = $page*$this->itemsPerPage-$this->itemsPerPage;
      if ($start >1) $start = $start + 1;
      $string =  "{$_SERVER['PHP_SELF']}?{$this->tableName}[page]={$page}&{$this->tableName}[start]=$start";
      return $string;
  }

  public function display($current)
  {
    global $C;
    if(!is_numeric($current)||$current<1){$current=1;}
    $this->currentPage = $current;
    /*$firstString = "&#171;";
    $prevString = "&#8249;";
    $nextString = "&#8250;";
    $lastString = "&#187;";*/
    $firstString = "&lt;&lt;";
    $prevString = "&lt;";
    $nextString = "&gt;";
    $lastString = "&gt;&gt;";       
    $selectString = "";

    if($this->displayStyle==self::DISPLAY_FULL)
    {
      $firstString .= " Prima";
      $prevString .= " Precedente";
      $nextString = "Successiva $nextString";
      $lastString = "Ultima $lastString";
      $selectString = "Scegli pagina: ";
    }

    $html = "<form name='{$this->tableName}_pageForm' id='{$this->tableName}_pageForm' align= 'center'>";
    $script = "";
    $htmlMobile = "<div id='{$this->tableName}_loadMore'>";
    $scriptMobile = "";
    
    $tabelleResponsive = false;
    if(file_exists(HOMEPATH."/js/portal-php.js")) {
      $tabelleResponsive = strpos(file_get_contents(HOMEPATH."/js/portal-php.js"), "function tableToUl") !== false;
    }
    if($C['portal']['spider_portal']) {
      $spiderJs = false;
      for($i=1; $i<=10 && $spiderJs===false;$i++) {
        $spiderJs = @file_get_contents(str_replace("portal/","public/_c/portal.{$i}.js",$C['portal']['spider_portal']));
      }
      if($spiderJs!==false && strlen($spiderJs)>0) {
        $tabelleResponsive = strpos($spiderJs, "function tableToUl") !== false;
      }
    }
    
    if(isset($C['style']) && $C['style']=="2016") {
      $scriptMobile = "<script src='".URL_JS."/jquery.mobile-events.min.js'></script>
      <script type='text/javascript'>
      $(document).ready(function(){
        $('#{$this->tableName}_loadMore').on('click', '#loadMore', function(event) {
          event.preventDefault();
          $('body').addClass('wait');
          $('#{$this->tableName}_loadMore').html('<i class=\"fa fa-circle-o-notch fa-spin fa-3x fa-fw muted\"></i><span class=\"sr-only\">Loading...</span>');
          $.get($(this).attr('href'), function( data ) {
            var columns = [];
            $('.table-header div').each(function(){
              columns.push($(this).text());
            });
            /*$('#{$this->tableName} li').not('.table-header').remove();*/
            var largestCol = rowsToLi($(data).find('#{$this->tableName} tbody'), $('#{$this->tableName}'), columns);
            setCellWidths(largestCol, columns, $('#{$this->tableName}'));
            if($(data).find('#{$this->tableName}_loadMore').length>0) {
              $('#{$this->tableName}_loadMore').html($(data).find('#{$this->tableName}_loadMore').html());
            } else {
              $('#{$this->tableName}_loadMore').html('');
            }
            $('body').removeClass('wait');
          });
        });
        if($('#{$this->tableName}_loadMore:visible').length>0 && $('#{$this->tableName}_pageForm span').text()!='1') {
          $('#{$this->tableName}_pageForm a').first().text('Torna all\'inizio').css('margin-bottom','20px').addClass('btn btn-default btn-block').insertBefore('#{$this->tableName}');
        }
      });
      </script>";
      $script = "<script type='text/javascript'>
    $(document).ready(function(){
      $('#{$this->tableName}_pageForm').on('click', 'a', function(event) {
        event.preventDefault();
// 	    console.log($(this)[0].outerHTML+' clicked');
// 	    console.log('getting '+$(this).attr('href'));
        $('body').addClass('wait');
        $.get($(this).attr('href'), function( data ) {
          if(typeof rowsToLi !== 'function') {
            $('#{$this->tableName}').html($(data).find('#{$this->tableName}').html());
          } else {
            var columns = [];
            $('.table-header div').each(function(){
              columns.push($(this).text());
            });
            $('#{$this->tableName} li').not('.table-header').remove();
            var largestCol = rowsToLi($(data).find('#{$this->tableName} tbody'), $('#{$this->tableName}'), columns);
            setCellWidths(largestCol, columns, $('#{$this->tableName}'));
          }
          $('#{$this->tableName}_pageForm').html($(data).find('#{$this->tableName}_pageForm').html());
          $('html, body').animate({scrollTop: $('#{$this->tableName}').offset().top-$('#portal_top').height()}, 200);
          $('body').removeClass('wait');
        });
      });
    });
      </script>";
      $html = "<form name='{$this->tableName}_pageForm' id='{$this->tableName}_pageForm' align= 'center'>";
    }
    $totPages = $this->totItems/$this->itemsPerPage;
    $RoundTotPages = round($totPages);
    if ($totPages > $RoundTotPages) $totPages = $RoundTotPages +1;
    else $totPages = $RoundTotPages;
    $page = 1;
    

    $htmlMobile .= "<a class='btn btn-default btn-block' title='Carica altri' href='".$this->getUrlString($this->currentPage+1)."&async' id='loadMore'>Carica altri</a></div>";
    
    if($this->currentPage>1)
    {
      $html .= "<a class='button' title='Prima' href='".$this->getUrlString(1)."'>$firstString</a>&nbsp;";
      $html .= "<a class='button' title='Precedente' href='".$this->getUrlString($this->currentPage-1)."'>$prevString</a>&nbsp;";
    }

    $lowEnd=1;
    $highEnd=$totPages;

    if($this->displayPages<$totPages)
    {
      $half = floor($this->displayPages/2);
      if($this->currentPage-$half>0)
      {
        $lowEnd = $this->currentPage-$half;
        $highEnd = $lowEnd+$this->displayPages-1;
      }
      else
      {
        $lowEnd = 1;
        $highEnd = $this->displayPages;
      }
      if($highEnd>$totPages)
      {
        $highEnd=$totPages;
        $lowEnd = $highEnd-$this->displayPages+1;
      }
    }

    $prev = "";
    $select = " - {$selectString}<select title='Scegli pagina' name='{$this->tableName}[page]' onchange='javascript:document.forms.{$this->tableName}_pageForm.submit();'>";
    if(isset($C['style']) && $C['style']=="2016") {
      $select = '
      <div class="btn-group dropup sel_pagine"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Pag <strong>'.$this->currentPage.'</strong> <span class="caret"></span></button><ul class="dropdown-menu lista_pagine" name="'.$this->tableName.'"[page]" id="'.$this->tableName.'_page">';
    }

    while($page<=$totPages)
    {
      $selected="";
      if($page<$lowEnd)
      {
        if($prev!="..."){$html .= "<a class='button' title='...' href='".$this->getUrlString($lowEnd-1)."'>&#8230;</a>&nbsp;";}
        $prev = "...";
      }
      else if($page>$highEnd)
      {
        if($prev!="..."){$html .= "<a class='button' title='...' href='".$this->getUrlString($highEnd+1)."'>&#8230;</a>&nbsp;";}
        $prev = "...";
      }
      else if($page!=$this->currentPage){$html .= "<a class='button' title='$page' href='".$this->getUrlString($page)."'>$page</a>&nbsp;";$prev=$page;}
      else {$html .= "<span class='button' style='color: #959595;'>$page</span>&nbsp;";$selected=" selected";$prev=$page;}
      
      if(isset($C['style']) && $C['style']=="2016") {
    $select .= '<li><a href="'.$this->getUrlString($page).'">'.$page.'</a></li>';
      } else {
    $select .= "<option{$selected}>$page</option>";
      }
      
      $page++;
    }
    if($this->currentPage<$totPages)
    {
      $html .= "<a class='button' title='Successiva' href='".$this->getUrlString($this->currentPage+1)."'>$nextString</a>&nbsp;";
      $html .= "<a class='button' title='Ultima' href='".$this->getUrlString($totPages)."'>$lastString</a>&nbsp;";
    }
    if(isset($C['style']) && $C['style']=="2016") {
      $select .= "</ul></div>";
    } else {
      $select .= "</select>";
    }
    if($this->displayPageJump){$html .= $select;}
    $html .= "</form>";
    if($totPages<2) {$html = "";}
    
    if(($totPages>1 && $this->currentPage>=$totPages) || $totPages==1) { $htmlMobile = "<div id='{$this->tableName}_loadMore'><span class='muted' id='endResults'>Non ci sono ulteriori risultati</span></div>"; }
    
    $output = "<div id=\"paginate_desktop\">{$script}{$html}</div>";
    if($tabelleResponsive && isset($C['style']) && $C['style']=="2016") {
      $output .= "<div id=\"paginate_mobile\">{$scriptMobile}{$htmlMobile}</div>";
    } else {
      $output = $script.$html;
    }
    echo $output;
  }
}
?>
