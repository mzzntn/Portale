<?
global $tinyMCEDidInit;
global $IMP;

if ($this->w->readOnly){
   print "<div class='".$this->getCSSClass('readOnlyText')."'>";
   print $this->w->value;
   if (!$this->w->value) print "-";
   print "</div>";
}
else{
    $this->loadJs('tiny_mce/tiny_mce');
?>
<textarea id="<?=$this->name?>" name="<?=$this->w->name?>" class="richTextArea" rows="15" cols="80" style="width: 80%">
	<?=htmlentities($this->w->value)?>
</textarea>
<?
if (!$tinyMCEDidInit){
  $tinyMCEDidInit = true;

	if ($IMP->security->options['editor']['toolbar'] == "limited") {
	// Versione Limitata dell'Editor
?>
<script>
tinyMCE.init({
	// General options
	mode : "specific_textareas",
	editor_selector: "richTextArea",
	language: "it",
	theme : "advanced",
	hippo_tools_url : '<?=TOOLS?>',
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,ajaxfilemanager,guida,internalink",
	
	accessibility_warnings: true,

	// Theme options
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,pastetext,pasteword,|,undo,redo",
	theme_advanced_buttons2 : "search,replace,|,bullist,numlist,|,outdent,indent,|,internalink,link,unlink,anchor,image,media,|,forecolor,backcolor,|,guida", 
	theme_advanced_buttons3 : "",
	//theme_advanced_buttons4 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,

	// Example content CSS (should be your site CSS)
	content_css : "css/content.css",
	
	file_browser_callback : function(field_name, url, type, win){
	    ajaxfilemanager('<?=TOOLS?>/ajaxfilemanager/ajaxfilemanager.php', field_name, url, type, win);
	},
	
	internal_link_browser_callback : function(field_name){
	    internallinkbrowser('<?=TOOLS?>/infoChooser_mce.php', field_name, win);
	},
	
	urlconverter_callback : function(url, node, on_save) {
  	return url;
  },

	// Drop lists for link/image/media/template dialogs
	template_external_list_url : "lists/template_list.js",
	external_link_list_url : "lists/link_list.js",
	external_image_list_url : "lists/image_list.js",
	media_external_list_url : "lists/media_list.js",

});
</script>

<?
} else {
	// Versione Avanzata Editor
?>

<script>
tinyMCE.init({
	// General options
	mode : "specific_textareas",
	editor_selector: "richTextArea",
	language: "it",
	theme : "advanced",
	hippo_tools_url : '<?=TOOLS?>',
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,ajaxfilemanager,guida,internalink",
	
	accessibility_warnings: true,

	// Theme options
	theme_advanced_buttons1 : "fullscreen,code,|,preview,template,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cleanup,removeformat,|,visualaid,pastetext,pasteword,|,undo,redo,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,internalink,link,unlink,anchor,image,media,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,|,sub,sup,|,charmap,iespell,advhr,|,print,|,guida",
	//theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,pagebreak",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,

	// Example content CSS (should be your site CSS)
	content_css : "css/content.css",
	
	file_browser_callback : function(field_name, url, type, win){
	    ajaxfilemanager('<?=TOOLS?>/ajaxfilemanager/ajaxfilemanager.php', field_name, url, type, win);
	},
	
	internal_link_browser_callback : function(field_name){
	    internallinkbrowser('<?=TOOLS?>/infoChooser_mce.php', field_name, win);
	},
	
	urlconverter_callback : function(url, node, on_save) {
    	return url;
    },

	// Drop lists for link/image/media/template dialogs
	template_external_list_url : "lists/template_list.js",
	external_link_list_url : "lists/link_list.js",
	external_image_list_url : "lists/image_list.js",
	media_external_list_url : "lists/media_list.js",

	// Replace values for the template plugin
	template_replace_values : {
		username : "Some User",
		staffid : "991234"
	}	
});
</script>

<?
	}
  }
}
?>
