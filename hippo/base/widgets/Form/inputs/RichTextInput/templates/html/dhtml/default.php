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
  //  $this->loadJs('tiny_mce/tiny_mce');
  $this->loadJs('tinymce_4.8.2/tinymce.min');
?>
<textarea id="<?=$this->name?>" name="<?=$this->w->name?>" class="richTextArea" rows="15" cols="80" style="width: 80%">
	<?=StringParser::parse($this->w->value);//htmlentities($this->w->value)?>
</textarea>
<?
    $listaPagine = array();
    $listaPagineEstesa = array();
    $listaPagineLink = array();

    if(defined('URL_APP_TRASPARENZA')){
        $struttura = 'trasparenza::pagina';
        $url = URL_APP_TRASPARENZA.'/pagina.php';
    }
    else if (defined('URL_APP_CARICAMENTO_PRATICHE') && false){
        $struttura = 'caricamento_pratiche::documentazione';
        $url = URL_APP_CARICAMENTO_PRATICHE.'/documentazione.php';
    }
    else if(defined('URL_APP_CMS')){
        $struttura ='cms::pagina';
        $url = URL_APP_CMS.'/pagina.php';
    } else {
	$listaPagine = false;
    }
    if($listaPagine !== false) {
	$loader = & $IMP->getLoader($struttura);
	$loader->addOrder('titolo', 'ASC');
	$loader->requestAll();
	$pagina = $loader->load();
	while ($pagina->moveNext()){
      if($pagina->get('titolo') && $pagina->get('titolo')!=""){
        $parsedTitle = StringParser::parse($pagina->get('titolo'),true);
        $listaPagine[] = $parsedTitle;
        $listaPagineEstesa[StringParser::parse($pagina->get('titolo'),true,false,true)] = $url."?id={$pagina->get('id')}";
        $listaPagineLink[] = array("title"=>StringParser::parse($pagina->get('titolo'),true,false,true),"value"=>$url."?id={$pagina->get('id')}");
      }
	}	
   }
	
// 	print_r($listaPagine);

if (!$tinyMCEDidInit){
  $tinyMCEDidInit = true;

	if ($IMP->security->options['editor']['toolbar'] == "limited") {
	// Versione Limitata dell'Editor
?>
<script>
tinymce.init({
	// General options
	mode : "specific_textareas",
	editor_selector: "richTextArea",
	language: "it",
	theme : "modern",
	hippo_tools_url : '<?=TOOLS?>',
	installation_path : '<?=HOMEPATH?>',
	plugins : "pagebreak,table,save,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,ajaxfilemanager,link",
	
	accessibility_warnings: true,

	// Theme options
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,pastetext,pasteword,|,undo,redo",
	theme_advanced_buttons2 : "search,replace,|,bullist,numlist,|,outdent,indent,|,link,unlink,anchor,image,media,|,forecolor,backcolor,|,guida", 
	theme_advanced_buttons3 : "",
	//theme_advanced_buttons4 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,

	// Example content CSS (should be your site CSS)
	content_css : "css/content.css",
	
	file_browser_callback : function(field_name, url, type, win){
	    ajaxfilemanager('<?=TOOLS?>/ajaxfilemanager/ajaxfilemanager.php', field_name, url, type, win, '<?=HOMEPATH?>');
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
var listapagine = <?=$listaPagine===false?'false':str_replace("\\\\u","\\u",json_encode($listaPagineEstesa))?>;

jQuery.fn.filterByText = function(textbox) {
  return this.each(function() {
    var select = this;
    var options = [];
    $(select).find('option').each(function() {
      options.push({
        value: $(this).val(),
        text: $(this).text()
      });
    });
    $(select).data('options', options);

    $(textbox).bind('change keyup', function() {
      var options = $(select).empty().data('options');
      var search = $.trim($(this).val());
      var regex = new RegExp(search, "gi");

      $.each(options, function(i) {
        var option = options[i];
        if (option.text.match(regex) !== null) {
          $(select).append(
            $('<option>').text(option.text).val(option.value)
          );
        }
      });
    });
  });
};

function expandable(editor) {
  var text = editor.selection.getContent({
      'format': 'html'
  });
  if (text && text.length > 0) {
    var $closestExpandable = $(editor.selection.getNode()).closest(".expandable");
    if($closestExpandable.length) {
      $($closestExpandable.unwrap().html()).insertBefore($closestExpandable);
      $closestExpandable.remove();
      tinymce.activeEditor.undoManager.add();
    } else {
      if(!text.startsWith("<")){text = "<p>"+text+"</p>";}
      tinymce.activeEditor.execCommand('mceInsertContent', false,
          '<div class="expandable">' + text + '</div>');
    }
  } else {
    var $closestExpandable = $(editor.selection.getNode()).closest(".expandable");
    if($closestExpandable.length) {
      $($closestExpandable.unwrap().html()).insertBefore($closestExpandable);
      $closestExpandable.remove();
      tinymce.activeEditor.undoManager.add();
    }
  }
}
function expandablePostRender(ctrl, editor) {
  editor.on('NodeChange', function(e) {
      var $closestExpandable = $(tinymce.activeEditor.selection.getNode()).closest(".expandable");
      ctrl.active($closestExpandable.length>0);
  });
}

function openInternalLink(editor) {
          editor.windowManager.open({
            title: 'Scegli pagina',
            body: [
              {type: 'label', text: 'Per creare un link alle pagine interne basta selezionare il titolo della pagina a cui fare riferimento dal menu a tendina e premere ok.'},
              {type: 'textbox', name: 'filtro_pagina', label  : 'Cerca pagina:'},
              {
                type   : 'selectbox',
                name   : 'selectbox',
                label  : 'Scegli la pagina a cui far riferimento:',
                options : <?=$listaPagine===false?"''":str_replace("\\\\u","\\u",json_encode($listaPagine))?>,
              },
              
            ],
            onsubmit: function(e) {
              // Insert content when the window form is submitted
              var title = $('label:contains("Scegli la pagina a cui far riferimento:")').parent().find("select").val();
              var text = title;
              if(editor.selection.getContent()!="") {
                text = editor.selection.getContent();
              }
              editor.insertContent('<a href="'+ listapagine[title] +'" title="'+ title +'">'+ text +'</a>');
            }
          });
          $('label:contains("Scegli la pagina a cui far riferimento:")').parent().find("select").filterByText($('label:contains("Cerca pagina")').parent().find("input"), true);        
}

tinymce.init({
	// General options
	mode : "specific_textareas",
	editor_selector: "richTextArea",
	language: "it",
	theme : "modern",
	hippo_tools_url : '<?=TOOLS?>',
	installation_path : '<?=HOMEPATH?>',
	plugins : "pagebreak,table,save,insertdatetime,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,responsivefilemanager,link,image, lists,charmap,fullscreen,code,textcolor",
	toolbar: ["undo redo | bold italic underline formatselect fontselect forecolor | alignleft aligncenter alignright alignjustify | numlist bullist "," "+(listapagine?"internallink":"")+" responsivefilemanager expandable link unlink image charmap | fullscreen code print guida"],
        menu: {
          file: {title: 'File', items: 'newdocument print'},
          edit: {title: 'Modifica', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace '},
          insert: {title: 'Inserisci', items: 'link internallink responsivefilemanager | insertdatetime | charmap arrowdown hr'},
          view: {title: 'Visualizza', items: 'visualaid visualblocks visualchars | fullscreen '},
          format: {title: 'Formato', items: 'bold italic underline strikethrough superscript subscript | fontselect textcolor | formats | removeformat'},
          //table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
          tools: {title: 'Strumenti', items: 'code | guida'}
        },
    image_advtab: true ,
	external_filemanager_path:"<?=TOOLS?>/responsivefilemanager_tinymce_4.8.2/",
    filemanager_title:"Esplora risorse" ,
    filemanager_sort_by: "date",
    filemanager_descending: 1,
    external_plugins: { "responsivefilemanager" : "<?=URL_JS?>/tinymce_4.8.2/plugins/responsivefilemanager/plugin.min.js"},
    relative_urls: false,
    <?=$listaPagine===false?"":"link_list: ".str_replace("\\\\u","\\u",json_encode($listaPagineLink)).","?>
    table_default_attributes: {class: 'table table-bordered table-responsive'},
//     charmap: [
//       [0x00A9, 'copyright'],
//       [0x00A9, 'copyright',]
//     ],
   
	
	accessibility_warnings: true,
	    
    setup : function(editor) {
      editor.addButton("expandable",{
        icon:"arrowdown",
        tooltip:"Crea una sezione espandibile",
        onclick:function() { 
          expandable(editor);
        },
        onPostRender: function() {
          expandablePostRender(this, editor);
        }
      
      });        
      editor.addMenuItem("arrowdown",{
        icon:"arrowdown",
        text:"Sezione espandibile",
        onclick:function() { 
          expandable(editor);
        },
        onPostRender: function() {
          expandablePostRender(this, editor);
        },
        context:"insert"
      });
      editor.addButton("guida",{
        icon:"help",
        tooltip:"Guida all'utilizzo",
        onclick:function() { 
          editor.windowManager.open({
            title: 'Guida all\'utilizzo',
            url: '<?=URL_JS?>/tinymce_4.8.2/guida/guida.html',
            width: 700,
            height: 600
          });
        }
      });       
      editor.addMenuItem("guida",{
        icon:"help",
        text:"Guida all'utilizzo",
        onclick:function() { 
          editor.windowManager.open({
            title: 'Guida all\'utilizzo',
            url: '<?=URL_JS?>/tinymce_4.8.2/guida/guida.html',
            width: 700,
            height: 600
          });
        }
      });
      editor.addButton("internallink",{icon:"anchor",tooltip:"Collegamento a pagina",onclick:function() { 
	  openInternalLink(editor);
        }
      });
      editor.addMenuItem("internallink",{icon:"anchor",text:"Collegamento a pagina",onclick:function() { 
          openInternalLink(editor);
        }
      });
    },

});

</script>

<?
	}
  }
}
?>
