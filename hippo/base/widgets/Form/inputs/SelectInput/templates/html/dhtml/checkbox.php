<?
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
if (is_array($this->w->tree)) foreach ($this->w->tree as $branch){
?>
<p>
    <label for="<?=$this->name?>_<?=$branch->id?>"><?=$branch->label?></label>&nbsp;
    <input type='checkbox' id='<?=$this->name?>_<?=$branch->id?>' name='<?=$this->w->name?>[]' value='<?=$branch->id?>' <?=$this->w->selectedValues[$branch->id]? " checked='checked'/>": "/>"?>
</p>
<?
}
?>
