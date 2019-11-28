<?
if(isset($C['style']) && $C['style']=="2016") {
	print "<select ";
	if ($W->readOnly){ "disabled ";}
	print "id='{$this->name}' ";
	print "class='form-control ".$this->getCSSClass()."' ";
	if ($this->w->config['multiple']) print "multiple ";
	print "name='{$this->w->name}";
	if ($this->w->config['multiple']) print "[]";
	print "'>";
	if (!$this->w->config['noBlank']) print "<option value=''></option>";
	if (is_array($this->w->tree)) foreach ($this->w->tree as $branch){
	      $this->printBranch($branch);
	}
	print "</select>";
}
else {
  if ($W->readOnly){
	print "<div align='left' class='".$this->getCSSClass('readOnlyText')."'>";
      $values = array();
      if ( is_array($D->fields) ) foreach ($D->fields as $id => $name){
	  if ($W->selectedValues[$id]) $values[] = $name;
      }
      print implode(', ', $values);
      print "</div>";
      return;
  }
  else{
	print "<select ";
	print "id='{$this->name}' ";
	print "class='".$this->getCSSClass()."' ";
	if ($this->w->config['multiple']) print "multiple ";
	print "name='{$this->w->name}";
	if ($this->w->config['multiple']) print "[]";
	if ($this->w->config['onchange']) print "onchange={$this->w->config['onchange']}";
	print "'>";
	if (!$this->w->config['noBlank'] && !($this->w->config['noBlankIfOne'] && sizeof($this->w->tree) == 1)) print "<option value=''></option>";
  }
  if (is_array($this->w->tree)) foreach ($this->w->tree as $branch){
	$this->printBranch($branch);
  }
  print "</select>";
  if($this->w->config['multiple']) { ?>
  <ol id="<?=$this->name?>_extendedSelect" class="bsmList"></ol>
  <script type='text/javascript'>
    var <?=$this->name?>_list = $('#<?=$this->name?>').clone();  
    <?=$this->name?>_list.prop('id', '<?=$this->name?>_visible');
    <?=$this->name?>_list.prop('multiple', false);
    $('#<?=$this->name?>').after(<?=$this->name?>_list);
    
    $('#<?=$this->name?>_visible').change(function(e) {
      $('#<?=$this->name?> option[value='+$(this).val()+']').prop('selected',true);
      $('#<?=$this->name?>').change();
    }); 
    
    $('#<?=$this->name?>').change(function(e) {  
      $('#<?=$this->name?>_visible').html('');
      $('#<?=$this->name?>_visible').append($('#<?=$this->name?> > option').clone());    
      $('#<?=$this->name?>_extendedSelect').html('');
      $( '#<?=$this->name?> option:selected' ).each(function() {
	if($(this).text()!="" && $(this).text()!=" ") {
	  var $link = $('<a class="bsmListItemRemove" style="cursor:pointer;">togli</a>');
	  var $span = $('<span class="bsmListItemLabel"> '+$(this).text()+' </span>');
	  var $li = $('<li class="bsmListItem" style="display: block;"></li>');
	  $li.append($span);
	  $li.append($link);
	  $link.click(function() {
	    var label = $(this).parent().find('.bsmListItemLabel').html();
	    var $option = $('#<?=$this->name?> option').filter(function () { return $(this).html() == label.trim(); });
	    if($option.length>0) {
	      $option.prop('selected', false);
	      $(this).parent().remove();
// 	      $('#<?=$this->name?>').change();
// 	      $('#<?=$this->name?>').prop('selectedIndex',-1);
// 	      $('#<?=$this->name?>').val("");
	    }
	  });
	  $('#<?=$this->name?>_extendedSelect').append($li);
	  e.preventDefault();
	  $('#<?=$this->name?>_visible option[value='+$(this).val()+']').remove();
	  $('#<?=$this->name?>_visible').val("");
// 	  $('#<?=$this->name?>').val("");
	}
      });
    });
    
    $('#<?=$this->name?>').change();
    $('#<?=$this->name?>').hide();
  </script>
<?	}
}
  ?>
