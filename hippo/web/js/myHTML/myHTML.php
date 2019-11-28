<?
if (!defined('HIPPO')) include_once('../../init.php');
$IMP->styleManager->parseFile(PATH_CSS.'/info.css');
$css = $IMP->styleManager->css;


?>

var myHTMLs = new Array();


function MyHTML(divId, hiddenId){
  this.div = getObj(divId);
  this.hidden = getObj(hiddenId);
  this.config = new MyHTML.config();
  this.buttons = new Array();
  this.pushedButtons = new Array();
  this.activeButtons = new Array();
  this.dynCss = new Array();
  this.num = myHTMLs.length;
  myHTMLs.push(this);
}  

MyHTML.config = function(){
  this.buttons = {
    bold: [ "Grassetto", "bold.gif"],
    italic: [ "Corsivo", "italic.gif"],
    underline: [ "Sottolineato", "underline.gif"],
    strikethrough: [ "Barrato", "strike.gif"],
    subscript: [ "Pedice", "sub.gif"],
    superscript: [ "Apice", "sup.gif"],
    justifyleft: [ "Allinea a sinistra", "left.gif"],
    justifycenter: [ "Centrato", "center.gif"],
    justifyright: [ "Allinea a destra", "right.gif"],
    justifyfull: [ "Giustifica", "justify.gif"],
    insertorderedlist: [ "Elenco numerato", "list_num.gif"],
    insertunorderedlist: [ "Elenco puntato", "list_bullet.gif"],
    outdent: [ "Diminuisci indentazione", "indent_less.gif"],
    indent: [ "Aumenta indentazione", "indent_more.gif"],
    forecolor: [ "Colore testo", "fg.gif"],
    hilitecolor: [ "Colore sfondo", "bg.gif"],
    inserthorizontalrule: [ "Riga orizzontale", "hr.gif"],
    link: [ "Collegamento Web", "link.gif"],
    image: [ "Immagine", "image.gif"],
    table: [ "Tabella", "table.gif"],
    htmlmode: [ "Modalità HTML", "html.gif"],
    popupeditor: [ "Ingrandisci editor", "maximize.gif"],
    undo: [ "Annulla", "undo.gif"],
    redo: [ "Ripristina", "redo.gif"],
    cut: [ "Taglia", "cut.gif"],
    copy: [ "Copia", "copy.gif"],
    paste: [ "Incolla", "paste.gif"],
    file: [ "Inserisci file", "save.gif"],
    lefttoright: [ "Da sinistra a destra", "left_to_right.gif"],
    righttoleft: [ "Da destra a sinistra", "right_to_left.gif"],
    maximize: [ "Ingrandisci", "maximize.gif" ],
    minimize: [ "Chiudi", "minimize.gif" ],
    linkinfo: [ "Link ad un articolo", "about.gif"],
    fontname: [ "Font", ""],
    fontsize: [ "Dim", ""],
    tableModify: [ "Proprietà tabella", "table-prop.gif"],
    addRowAfter: [ "Aggiungi riga sotto", "row-insert-under.gif"], 
    addRowBefore: [ "Aggiungi riga sopra", "row-insert-above.gif"],
    deleteRow: [ "Cancella riga", "row-delete.gif"],
    addCellAfter: [ "Aggiungi una cella a destra", "cell-insert-after.gif"],
    addCellBefore: [ "Aggiungi una cella a sinistra", "cell-insert-before.gif"],
    deleteCell: [ "Elimina cella", "cell-delete.gif"],
    mergeCells: [ "Fondi due celle", "cell-merge.gif"],
    cellProps: [ "Proprietà cella", "cell-prop.gif"],
    userStyle: [ "Stile", ""],
    clearFormat: [ "Cancella formattazione", "clearformat.gif"],
    findText: [ "Cerca", "find.gif"],
    templateSave: [ "Salva modello", "template.gif"],
    templates: [ "Modelli", ""],
    table_outline: [ "Evidenzia bordi tabelle", "table_outline.gif"],
    block_outline: [ "Evidenzia blocchi", "block_outline.gif"],
    line_outline: [ "Evidenzia sezioni", "line_outline.gif"]
  };
  this.toolbars = new Array();
  this.toolbars["utils"] = ['maximize', 'htmlmode'];
  this.toolbars["basicEdit"] = ['cut', 'copy', 'paste', '-', 'undo', 'redo'];
  this.toolbars["insert"] = ['link', 'image', 'file', 'table', 'inserthorizontalrule', 'linkinfo'];
  this.toolbars["basicFormat"] = ['bold', 'italic', 'underline'];
  this.toolbars["otherFormat"] = ['subscript', 'superscript','justifyleft', 'justifycenter', 'justifyright', 'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'clearFormat'];
  this.toolbars["extFormat"] = ['forecolor', 'hilitecolor', 'fontname', 'fontsize',  'userStyle'];
  this.toolbars["miniExtFormat"] = ['userStyle'];
  this.toolbars["table"] = [ 'tableModify', 'addRowAfter', 'addRowBefore', 'deleteRow', 'addCellAfter', 'addCellBefore', 'deleteCell', 'mergeCells', 'cellProps', 'table_outline'];
  this.toolbars["extUtils"] = ['block_outline', 'line_outline', 'templateSave', 'findText',  'templates' ];
  this.toolbars["miniExtUtils"] = ['findText'];
  this.pushedContext = new Array();
  //this.pushedContext["b"] = 'bold';
  this.context = new Array();
  this.context["table"] = ['tableModify'];
  this.context["tr"] = ['addRowAfter', 'addRowBefore', 'deleteRow'];
  this.context["td"] = ['addCellAfter', 'addCellBefore', 'deleteCell', 'cellProps'];
  this.contextButtons = ['tableModify', 'addRowAfter', 'addRowBefore', 'deleteRow', 'addCellAfter', 'addCellBefore', 'deleteCell', 'mergeCells', 'cellProps'];
  this.modes = new Array();
  this.modes['default'] = ['utils', '-', 'basicFormat', '-', 'insert'];
  this.modes['full'] = ['utils', '-', 'basicFormat', '-', 'otherFormat', '-', 'extFormat', 'insert', '<br>', 'table', '-', 'extUtils'];
  this.modes['limited'] = ['utils', '-', 'basicFormat', '-', 'otherFormat', '-', 'miniExtFormat', 'insert', '-', 'miniExtUtils'];
  this.modes['source'] = ['utils', '-', 'miniExtUtils'];
  this.toolbarMode = 'default';
  this.cssClass = 'myHTML';
  this.fontname = {
     "-default-":'-default-',
		"Arial":	   'arial,helvetica,sans-serif',
		"Courier New":	   'courier new,courier,monospace',
		"Georgia":	   'georgia,times new roman,times,serif',
		"Tahoma":	   'tahoma,arial,helvetica,sans-serif',
		"Times New Roman": 'times new roman,times,serif',
		"Verdana":	   'verdana,arial,helvetica,sans-serif',
		"Impact":	   'impact',
		"WingDings":	   'wingdings'
	};
	this.fontsize = {
    "-default-": "3",
		"1 (8 pt)":  "1",
		"2 (10 pt)": "2",
		"3 (12 pt)": "3",
		"4 (14 pt)": "4",
		"5 (18 pt)": "5",
		"6 (24 pt)": "6",
		"7 (36 pt)": "7"
	};
	this.formatblock = {
                "-default-": "",
		"Heading 1": "h1",
		"Heading 2": "h2",
		"Heading 3": "h3",
		"Heading 4": "h4",
		"Heading 5": "h5",
		"Heading 6": "h6",
		"Normale": "p",
		"Address": "address",
		"Formattato": "pre"
	};
	this.templates = {
<?
  $d = dir(PATH_WEBDATA.'/myHTML/templates');
  $first = 1;
  while (false !== ($entry = $d->read())) {
    if ($entry != '.' && $entry != '..'){
      if (!$first) print ",";
      $first = 0;
      if (preg_match('/(.+)\.(html|htm)$/', $entry, $matches)) $entry = $matches[1];
?>
    "<?=$entry?>": "<?=$entry?>"
<?
    }
  }
  $d->close();
?>
	};
	this.userStyle = new Array();
	this.userStyle['-default-'] = '-default-';
	this.cssString = "";
<?
if (is_array($css)){
  foreach (array_keys($css) as $styleName){
    $styleName = trim($styleName);
    if ($styleName[0] != '.') continue;
    $cleanStyle = trim(str_replace('.', ' ', $styleName));
    print "this.userStyle['$cleanStyle'] = '$cleanStyle';";
    //print "this.cssString += '$styleName{';";
    //if (is_array($css[$styleName])) foreach ($css[$styleName] as $key => $value){
    //  print "this.cssString += '$key:$value;';";
    //}
    //print "this.cssString += '}';";
  }
}
?>
}

MyHTML.prototype.setTimeout = function(funcText, t){
  setTimeout("myHTMLs["+this.num+"]."+funcText, t);
}

MyHTML.prototype.setToolbar = function(div){
  this.toolbar = getObj(div);
}

MyHTML.prototype.setIframe = function(div){
  this.iframe = getObj(div);
}

MyHTML.prototype.init = function(){
  if (!this.toolbar){
    var toolbar = document.createElement("div");
    this.toolbar = toolbar;
    this.div.appendChild(toolbar);
  }  
  if (!this.iframe){
    var iframe = createIFrame("myHTML_iframe");
    iframe.src='<?=HOME?>/tools/empty.html';
    this.iframe = iframe;
    this.div.appendChild(iframe);
  }
//  var info = document.createElement("div");
//  this.div.appendChild(info);
//  this.info = info;
  this.container = this.iframe;
  this.doc = this.iframe.contentWindow.document;
  this.doc.open();
  this.doc.write('');
  this.doc.close();
  this.body = this.doc.body;
  this.doc.body.contentEditable = true; //IE
  this.doc.myHTML = this;
  
  this.initToolbar(this.config.toolbarMode);
  this.doc.designMode = 'on';
  this.doc = this.iframe.contentWindow.document; //repetita juvant, especially to IE
  
  this.window = window;
  window.myHTML = this;
  window.myHTMLPopups = new Array();
  this.contentWindow = this.iframe.contentWindow;
  this.mode = 'html';
  this.postInit();
  addEvent(window, 'unload', function(){this.myHTML.closePopups();});
}

MyHTML.prototype.initInline = function(editDiv, toolbarDiv){
  this.toolbar = toolbarDiv;
  this.body = editDiv;
  this.container = editDiv;
  this.div = editDiv;
  this.initToolbar(this.config.toolbarMode);
  this.div.contentEditable = true;
  this.doc = document;
  this.doc.myHTML = this;
  this.window = window;
  this.contentWindow = window;
  this.mode = 'html';
  this.inline = true;
  this.postInit();
  var saveSelection = function(){document.myHTML.saveSelection();return false;};
  var restoreSelection = function(){document.myHTML.restoreSelection(); return false;};
  if (this.div.attachEvent){ //ie
  
   this.div.attachEvent('onmouseup', saveSelection);
   document.body.attachEvent('onmouseup', restoreSelection);
  }
  if (document.addEventListener){  //moz
   this.div.addEventListener('mouseup', saveSelection, false);
   document.body.addEventListener('mouseup', restoreSelection, false);
  }

}

MyHTML.prototype.postInit = function(){
  if (!this.body) this.body = this.doc.body;
  if (!this.doc.body){
    this.setTimeout('postInit()', 100);
    return;
  }
  this.doc.myHTML = this;
  var link=this.doc.createElement("link");
  link.setAttribute("rel", "stylesheet");
  link.setAttribute("type", "text/css");
  link.setAttribute("href", this.baseUrl+"/css/"+name+".css");
  link.setAttribute("href", "<?=URL_CSS?>/info.css");
  link.setAttribute("media", "screen");
  var head = this.doc.getElementsByTagName("head");
  head[0].appendChild(link);
  this.attachEvents();
  this.contentWindow.focus();
}

MyHTML.prototype.initToolbar = function(mode){
  removeTooltip();
  this.toolbar.innerHTML = '';
  for (var i in this.config.modes[mode]){
    toolbar = this.config.modes[mode][i];
    if (toolbar == '-'){
      var separator = document.createElement("span");
      separator.className = this.config.cssClass+" separator";
      this.toolbar.appendChild(separator);
    }
    else if (toolbar == '<br>'){
      var br = document.createElement('br');
      this.toolbar.appendChild(br);
    }
    else this.buildToolbar(toolbar);
  }
}

MyHTML.prototype.execCmd = function(cmd, params){
  if (this.mode != 'html' && (cmd != 'htmlmode' && cmd != 'findText' && cmd != 'minimize')) return;
  this.contentWindow.focus();
  var createdNode;
  var popupString = "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=640,height=480";
  switch (cmd){
    case 'maximize':
    case 'minimize':
      if (this.master){
        if (this.mode == 'source') this.switchMode();
        this.master.setValue(this.body.innerHTML);
        window.close();
      }
      else{
        var popupWidth = screen.width?screen.width:'630';
        var popupHeight = screen.height?screen.height:'460';
        if (this.popup && !this.popup.closed && this.popup.focus) this.popup.focus();
        else{
          this.popup = window.open(this.baseUrl+'/fullscreen.php', "myHTML_fullscreen", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,width="+popupWidth+",height="+popupHeight+",screenX=0,screenY=0,left=0,top=0");
          this.popup.top.master = this;
          window.myHTMLPopups[window.myHTMLPopups.length] = this.popup;
        }
      }
      break;
    case 'htmlmode':
      this.switchMode();
      break;
    case 'image':
    case 'table':
    case 'link':
    case 'file':
    case 'linkinfo':
    case 'forecolor':
    case 'hilitecolor':
    case 'tableModify':
    case 'cellProps':
    case 'templateSave':
    case 'findText':
      this.openPopup(cmd);
      break;
    case 'addRowBefore':
      this.addRow(1);
      break;
    case 'addRowAfter':
      this.addRow(2);
      break;
    case 'deleteRow':
      this.deleteRow();
      break;
    case 'addCellBefore':
      this.addCell(1);
      break;
    case 'addCellAfter':
      this.addCell(2);
      break;
    case 'deleteCell':
      this.deleteCell();
      break;
    case 'mergeCells':
      this.mergeCells();
      break;
    case 'setUserStyle':
      this.setUserStyle(params);
      break;
    case 'clearFormat':
      this.clearFormat();
      break;
    case 'setTemplate':
      this.setTemplate(params);
      break;
    case 'table_outline':
    case 'block_outline':
    case 'line_outline':
      this.switchCSS(cmd);
      break;
    default:
      this.doc.execCommand(cmd, false, params);
      break;
  }
  this.updateToolbar();
  this.valueChange();
  //buttons that will be active soon
//  if (cmd != 'htmlmode') this.restoreSelection(createdNode); 
}

MyHTML.prototype.openPopup = function(name){
  var file;
  var width=400;
  var height=300;
  var doDisableSelection;
  var noDisableSelectionTags = {};
  var noDisableSelectionIds = {};
  switch (name){
    case 'image':
      image = this.getSelectionEl();
      file = 'imageUploader.php';
      if (image.tagName.toLowerCase() == "img") file += '?fileUrl='+image.src+'&img_border='+image.border+'&img_align='+image.align+'&alt='+image.alt;
      break;
    case 'file':
      link = this.getSelectionEl();
      file = 'fileUploader.php';
      if (link.tagName.toLowerCase() == 'a') file += '?fileUrl='+link.href;
      width
      break;
    case 'table':
      file = 'insertTable.php';
      break;
    case 'tableModify':
      table = this.findParentTag('table');
      if (!table) return alert("Devi prima posizionarti all'interno di una tabella");
      file = 'modifyTable.php';
      file += '?border='+table.border+'&padding='+table.cellPadding+'&spacing='+table.cellSpacing;
      var width;
      if (table.width) width = table.width;
      else width = table.style.width;
      file += '&width='+width+'&align='+table.align;
      break;
    case 'cellProps':
      td = this.findParentTag('td');
      if (!td) return alert("Devi prima posizionarti all'interno di una cella");
      file = 'modifyCell.php';
      file += '?colspan='+td.colSpan+'&rowspan='+td.rowSpan;
      file += '&width='+td.width+'&align='+td.align+'&valign='+td.valign;
      file += '&bgcolor='+escape(td.bgColor);
      break;  
    case 'link':
      link = this.findParentTag('a');
      file = 'chooseLink.php';
      if (link.tagName && link.tagName.toLowerCase() == 'a') file += '?url='+link.href+'&target='+link.target;

      break;
    case 'linkinfo':
      file = 'infoChooser.php';
      break;
    case 'templateSave':
      file = 'templateSave.php';
      break;
    case 'forecolor':
    case 'hilitecolor':
      file = 'palette.php';
      this.paletteCmd = name;
      //noDisableSelectionIds = {'color':1};
      doDisableSelection = true;
      break;
    case 'findText':
      file = 'find.php';
      width = 300;
      height = 100;
      break;
  }
  var url = this.toolsUrl+'/'+file;
//  this.popup = window.open(url, name, popupString);
  window.prova = 'pippo';
  window.openerInfo.master = this;
  if (window.showModelessDialog) window.showModalDialog(url, window.openerInfo, "dialogWidth:"+width+"px;dialogHeight:"+height+"px;resizable:yes");
  else{
    this.popup = openIPopup(url, name, this.config.buttons[name][0]);
    normalizeDiv(this.buttons[name]);
  //this.popup.moveTo(this.buttons[name].x, this.buttons[name].y + 20);
//  this.popup.moveInWindow();
    this.popup.iPopupIframe.contentWindow.master = this;
    if (doDisableSelection) disableSelection(this.popup);
    window.iPopupInfo = new Object();
    window.iPopupInfo.master = this;
    window.iPopup = this.popup;
    this.popup.style.width = width+'px';
    this.popup.style.height = height+'px';
    this.popup.fixResize();
    switch (name){
      case 'table':
      case 'tableModify':
      case 'cellProps':
      case 'image':
        this.popup.style.width = 400;
        this.popup.style.height = 300;
        break;
      case 'link':
      case 'file':
        //this.popup.style.width = 300;
	    //      //this.popup.style.height = 200;
	    break;
	}   
    position(this.popup);
  }
}

MyHTML.prototype.closePopups = function(){
  for (var i=0; i<window.myHTMLPopups.length; i++){
    try{
      if (window.myHTMLPopups[i]) window.myHTMLPopups[i].close();
    }
    catch(exc){
    }
  }
}

MyHTML.prototype.buildToolbar = function(name){
  this.toolbar.className = this.config.cssClass+" toolbar";
  for (var i in this.config.toolbars[name]){
    var button = this.config.toolbars[name][i];
    switch (button){
      case '-':
        var separator = document.createElement("span");
        separator.className = this.config.cssClass+" separator";
        this.toolbar.appendChild(separator);
        break;
      case 'fontname':
      case 'fontsize':
      case 'userStyle':
      case 'templates':
        var span = document.createElement('span');
        //span.className = this.config.cssClass+" label";
        var text = document.createTextNode(this.config.buttons[button][0]+":");
        span.appendChild(text);
        span.className = this.config.cssClass+" button";
        this.toolbar.appendChild(span);
        //var sel = document.createElement('select');
        //if (typeof(this.config[button] == 'Array')) for (var i in this.config[button]){
        //  var opt = document.createElement('option');
        //  opt.value = i;
        //  opt.innerHTML = this.config[button][i];
        //}
        //this.toolbar.appendChild(sel);
        combo = createComboBox(button, this.toolbar);
        combo.myHTML = this;
        combo.disableInput();
        if (typeof(this.config[button] == 'Array')) for (var i in this.config[button]){
          var comboText;
          if (button == 'fontname'){
            comboText = "<font face='"+this.config[button][i]+"'>"+i+"</font>";
          }
          else if (button == 'fontsize'){
            comboText = "<font size='"+this.config[button][i]+"'>"+i+"</font>";
          }
          else if (button == 'userStyle'){
            comboText = "<span class='"+this.config[button][i]+"'>"+i+"</span>";
          }
          else{
            comboText = i;
          }
          combo.v[this.config[button][i]] = i;
          combo.menuTexts[this.config[button][i]] = comboText;
        }
        break;
      case 'maximize':
        if (this.master) button = 'minimize';
        //go on  
      default:
        var img = document.createElement("img");
        img.cmd = button;
        img.alt = this.config.buttons[button][0];
        img.src = this.baseUrl+"/img/"+this.config.buttons[button][1];
        img.myHTML = this;
        this.toolbar.appendChild(img);
        img.onclick = function(e){this.myHTML.execCmd(this.cmd)};
        this.buttons[button] = img;
        img.className = this.config.cssClass+" button";
        makeCool(img);
        addTooltip(img, this.config.buttons[button][0]);
    }
    switch (button){
      case 'fontname':
        combo.input.size = 12;
        combo.config['menuAction'] = function(param, c){ c.myHTML.execCmd('fontname', param) };
        combo.init();
        this.fontInput = combo;
        break;
      case 'fontsize':
        combo.config['menuAction'] = function(param, c){ c.myHTML.execCmd('fontsize', param) };
        combo.init();
        combo.input.size = 6;
        this.fontSizeInput = combo;
        break;
      case 'userStyle':
        combo.config['menuAction'] = function(param, c){ c.myHTML.execCmd('setUserStyle', param) };
        combo.init();
        combo.input.size = 12;
        this.classInput = combo.input;
        break;
      case 'templates':
        combo.config['menuAction'] = function(param, c){ c.myHTML.execCmd('setTemplate', param) };
        combo.input.size = 12;
        combo.init();
        break;
    }
  }
  disableSelection(this.toolbar);
}

MyHTML.prototype.switchCSS = function(name){
  if (this.dynCss[name]){
    //try{
      this.doc.getElementsByTagName("head")[0].removeChild(this.dynCss[name]);
    //} catch (exc){
    //}
    this.dynCss[name] = false;
    this.doc.body.contentEditable = false;
    this.doc.body.contentEditable = true;
  }
  else{
    var l=document.createElement("link");
    l.setAttribute("rel", "stylesheet");
    l.setAttribute("type", "text/css");
    l.setAttribute("href", this.baseUrl+"/css/"+name+".css");
    l.setAttribute("media", "screen");
    this.doc.getElementsByTagName("head")[0].appendChild(l);
    this.dynCss[name] = l;
  }
}

MyHTML.prototype.closePopup = function(){
  closeIPopup();
}

MyHTML.prototype.switchMode = function(){
  if (this.mode == 'html'){
    this.mode = 'source';
    this.initToolbar('source');
    var ta = document.createElement('textarea');
    var contents = document.createTextNode(this.doc.body.innerHTML);
    ta.appendChild(contents);
    this.textArea = ta;
    this.textArea.style.width = this.container.offsetWidth;
    this.textArea.style.height = this.container.offsetHeight;
    this.container.style.display = 'none';
    this.div.appendChild(ta);
    this.restoreSelection();
  }
  else{
    makeCool(this.textArea);
    var html = this.textArea.value;
    this.textArea.remove();
    this.initToolbar(this.config.toolbarMode);
    this.container.style.display = '';
    this.mode = 'html';
    this.setValue(html);
    for (var i in this.dynCss){
      if (this.dynCss[i]){
        try{
          this.doc.getElementsByTagName("head")[0].removeChild(this.dynCss[i]);
        } catch (exc){
        }
        this.doc.getElementsByTagName("head")[0].appendChild(this.dynCss[i]);
      }
    }
    //this.doc = this.iframe.contentWindow.document;
    this.doc.body.contentEditable = true; //IE
    //this.doc.designMode = 'on';
  }
  this.TRange = null;
}

MyHTML.prototype.insertImage = function(img, params){
  image = this.getSelectionEl();
  var newImage = false;
  if (image.tagName.toLowerCase() != 'img'){
    image = document.createElement('img');
    newImage = true;
  }
  var URL_WEBDATA = '<?=URL_WEBDATA?>';
  if (img.substr(0,7) != 'http://' && img.substr(0,URL_WEBDATA.length) != URL_WEBDATA) img = URL_WEBDATA+'/img/orig/'+img;
  else if (img.substr(0,16) == 'http://localhost') img = img.substr(16);
  image.src = img;
  if (params['border'] != 'undefined') image.border = params['border'];
  if (params['align'] != 'undefined') image.align = params['align'];
  if (params['alt'] != 'undefined') image.alt = params['alt'];
  //else this.doc.execCommand('insertimage', false, img);
  if (newImage) this.insertAtSelection(image);
  this.valueChange();
}

MyHTML.prototype.insertTable = function(params){
  var table = this.doc.createElement('table');
  var tbody = this.doc.createElement('tbody');
  table.appendChild(tbody);
  for (var i = 0; i < params["rows"]; i++) {
    var tr = this.doc.createElement("tr");
    tbody.appendChild(tr);
    for (var j = 0; j < params["cols"]; j++) {
      var td = this.doc.createElement("td");
      td.appendChild(this.doc.createElement("br"));
      tr.appendChild(td);
    }
  }
  table.border = params["border"];
  table.cellPadding = params["padding"];
  table.cellSpacing = params["spacing"];
  table.width = params["width"];
  table.align = params["align"];
  this.insertAtSelection(table);
  this.valueChange();
}

MyHTML.prototype.modifyTable = function(params){
  var table = this.findParentTag('table');
  if (!table) return;
  table.border = params["border"];
  table.cellPadding = params["padding"];
  table.cellSpacing = params["spacing"];
  table.width = params["width"];
  table.align = params["align"];
}

MyHTML.prototype.modifyCell = function(params){
  var td = this.findParentTag('td');
  if (!td) return;
  td.width = params["width"];
  td.align = params["align"];
  td.valign = params["valign"];
  td.colSpan = params["colspan"];
  td.rowSpan = params["rowspan"];
  td.bgColor = params["bgcolor"];
}

MyHTML.prototype.insertLink = function(params){
  if (params["href"].charAt(0) != '/' && params["href"].substring(0,7) != 'http://'){
    params["href"] = 'http://'+params["href"];
  }
  var a;
  var selectedText = this.getSelectedText();
  var selectedEl = this.getSelectionEl();
  if (selectedEl.tagName.toLowerCase() == 'a'){
    a = selectedEl; 
  }
  else if (!selectedText && (!selectedEl || !selectedEl.tagName || selectedEl.tagName.toLowerCase() != 'img')){
    a = document.createElement('a');
    a.innerHTML = params["href"];
    this.insertAtSelection(a);
  }
  else{
    this.doc.execCommand("createlink", false, params["href"]);
    a = this.findParentTag('a');
  }
  a.href = params["href"];
  a.target = params["target"];
  
  this.valueChange();
}

MyHTML.prototype.setUserStyle = function(param){
  var el = this.getSelectionEl();
  var text = this.getSelectedText();
  var span;
  if (el.tagName.toLowerCase() == 'span' && el.className && text.length < 1){
    el.className = param;
    span = el;
  }
  else{
    var sel = this.getSelectedHTML();
    span = this.doc.createElement('span');
    span.className = param;
    span.innerHTML = sel;
    this.insertAtSelection(span);
  }
  var attrs = {'class' : ''};
  var tags = {'span' : attrs, 'font' : attrs };
  var p = this.findParentTag(tags, span.parentNode);
  if (p){
    this.splitAncestor(span, p);
  }
  this.restoreSelection(span);
}


MyHTML.prototype.setColor = function(color){
  try{
    this.doc.execCommand(this.paletteCmd, false, color);
  }
  catch(exc){
    if (this.paletteCmd == 'hilitecolor') this.doc.execCommand('backcolor', false, color);
  }
}

function MyHTML_setValueFromTmpIframe(){
  try{
    myHTML.setValue(myHTML.tmpIframe.contentWindow.document.body.innerHTML);
  }
  catch(exc){
    setTimeout('MyHTML_setValueFromTmpIframe()', 100);
  }
}

MyHTML.prototype.setTemplate = function(template){
  tmpIframe = document.createElement('iframe');
  tmpIframe.myHTML = this;
  if (document.all) MyHTML_setValueFromTmpIframe();
  else tmpIframe.onload = function(e){ this.myHTML.setValue(this.contentWindow.document.body.innerHTML); };
  tmpIframe.src = '<?=URL_WEBDATA?>/myHTML/templates/'+template+'.html';
  this.tmpIframe = tmpIframe;
  myHTML = this;
  document.body.appendChild(tmpIframe);
  //tmpIframe.style.visibility= 'hidden';
  this.valueChange();
  //:TODO: remove the tmpIframe somehow
}




MyHTML.prototype.attachEvents = function(){
  eval("function myHTMLcursorMove_"+this.num+"(e){ var m = myHTMLs["+this.num+"]; if (m){m.cursorMove(e)};}");
  var func = eval("myHTMLcursorMove_"+this.num);
  this.attachEvent('keyup', func);
  this.attachEvent('mouseup', func);
}

MyHTML.prototype.attachEvent = function(event, func){
  if (this.body.attachEvent){ //ie
   this.body.attachEvent('on'+event, func);
   return;
  }
  if (this.doc.addEventListener){  //moz
   this.doc.addEventListener(event, func, false);
  }
}


MyHTML.prototype.updateToolbar = function(){
  var oldActive = this.activeButtons;
  var oldPushed = this.pushedButtons;
  this.activeButtons = new Array();
  this.pushedButtons = new Array();
  this.getContext();
  for (var i in oldPushed){
    if (!this.pushedButtons[i]) this.unpushButton(i);
  }
//  for (var i in this.config.contextButtons){
 //   button = this.config.contextButtons[i];
 //   if (oldActive[button]) this.deactivateButton(button);
 // }
  for (var i in this.dynCss){
    if (this.dynCss[i]) this.pushButton(i);
    else this.unpushButton(i);
  }
  this.getClass();
}

MyHTML.prototype.getContext = function(el){
  if (this.mode != 'html') return;
  var inLink = false;
  var el = this.getSelectionEl();
  while(el){
    if (el.nodeType != 1){
      el = el.parentNode;
      continue;
    }
    var tagName = el.tagName.toLowerCase();
    if (tagName == 'a') inLink = true;
    if (this.config.pushedContext[tagName]) this.pushButton(this.config.pushedContext[tagName]);
    if (this.config.context[tagName]) this.activateButton(this.config.context[tagName]);
    el = el.parentNode;
  }
  try{ //will fail in mozilla in inline mode
    if (this.doc.queryCommandState("bold")) this.pushButton("bold");
    if (this.doc.queryCommandState("italic")) this.pushButton("italic");
    if (!inLink && this.doc.queryCommandState("underline")) this.pushButton("underline");
    if (this.doc.queryCommandState("justifyleft")) this.pushButton('justifyleft');
    if (this.doc.queryCommandState("justifycenter")) this.pushButton('justifycenter');
    if (this.doc.queryCommandState("justifyright")) this.pushButton('justifyright');
    if (this.doc.queryCommandState("justifyfull")) this.pushButton('justifyfull');
    if (this.doc.queryCommandState("insertorderedlist")) this.pushButton('insertorderedlist');
    if (this.doc.queryCommandState("insertunorderedlist")) this.pushButton('insertunorderedlist');
    if (this.doc.queryCommandState("subscript")) this.pushButton('subscript');
    if (this.doc.queryCommandState("superscript")) this.pushButton('superscript');
    if (this.fontInput) this.fontInput.setValue(this.doc.queryCommandValue("fontname"));
    if (this.fontSizeInput) this.fontSizeInput.setValue(this.doc.queryCommandValue("fontsize"));
  }
  catch(exc){
  }
  
}

MyHTML.prototype.toggleButton = function(name){
  if (name == undefined || !this.buttons[name]) return;
  if (this.pushedButtons[name]) this.unpushButton(name);
  else this.pushButton(name);
}

MyHTML.prototype.pushButton = function(name){
  if (name == undefined || !this.buttons[name]) return;
  this.pushedButtons[name]=1;
  this.buttons[name].addClass('pressed');
}

MyHTML.prototype.unpushButton = function(name){
  if (name == undefined || !this.buttons[name]) return;
  this.buttons[name].removeClass('pressed');
}

MyHTML.prototype.deactivateButton = function(name){
  if (name == undefined || !this.buttons[name]) return;
  this.buttons[name].addClass('disabled');
  this.buttons[name].onclick = '';
}

MyHTML.prototype.activateButton = function(name){
  if (name == undefined || !this.buttons[name]) return;
  this.activeButtons[name]=1;
  this.buttons[name].removeClass('disabled');
  this.buttons[name].onclick = function(e){this.myHTML.execCmd(this.cmd)};
}

MyHTML.prototype.setFontInput = function(font){
  if (this.fontInput) this.fontInput.setValue(font);
}

MyHTML.prototype.getClass = function(){
  span = this.findParentTag('span');
  if (this.classInput){
    if (span && span.className) this.classInput.value = span.className;
    else this.classInput.value = '';
  }
}


MyHTML.prototype.cursorMove = function(e){
  if (this.mode != 'html') return;
  this.saveSelection();
  this.getClass();
  this.updateToolbar();
  this.valueChange();
}

//NOTE: if el is specified and matches, will return el
MyHTML.prototype.findParentTag = function(tags, el){
  t = el;
  if (tags && typeof(tags) != 'Array' && typeof(tags) != 'object'){
    var tag = tags;
    tags = new Object();
    tags[tag] = 0;
  }
  if (!t) t = this.getSelectionEl();
  if (!t) return;
  if ( t.tagName && this.checkTag(t, tags) ) return t;
  while (t.parentNode){
    if (t.nodeType != 1){
      t = t.parentNode;
      continue;
    }
    if (this.checkTag(t, tags)) return t;
    t = t.parentNode;
  }
  if ( !t.tagName || this.checkTag(t, tags) ) return 0;
  return t;
}

MyHTML.prototype.checkTag = function(el, tags){
  if (!tags) return true;
  for (var i in tags){
    if (el.tagName.toLowerCase() == i){
      if (typeof(tags[i]) == 'Array' || typeof(tags[i]) == 'object'){
        for (var j in tags[i]){
          var attr = j;
          var attrSearch = tags[i][j];
          for (var k in el.attributes){
            if (el.attributes[k].name == attr){
              if (!attrSearch) return true;
              if (el.attributes[k].value && el.attributes[k].value.indexOf(attrSearch) != -1) return true;
            }
          }
        }
      }
      else return true;
    }
  }
  return false;
}    

MyHTML.prototype.addRow = function(beforeAfter){
  selRow = this.findParentTag('tr');
  if (!selRow){
    alert("Devi prima selezionare una riga di tabella");
    return;
  }
  row = this.doc.createElement('tr');
  sibling = selRow.nextSibling;
  if (sibling) checkRow = sibling;
  else checkRow = selRow.previousSibling;
  var tdCount = 0;
  if (checkRow){
    cells = checkRow.childNodes;
    tdCount = cells.length;
  }
  if (tdCount) for (var i=0; i<tdCount; i++){
    td = this.doc.createElement('td');
    td.innerHTML = '<br>';
    row.appendChild(td);
  }
  if (beforeAfter == 2) nextRow = sibling;
  else nextRow = selRow;
  if (nextRow) selRow.parentNode.insertBefore(row, nextRow);
  else selRow.parentNode.appendChild(row);
}

MyHTML.prototype.deleteRow = function(){
  selRow = this.findParentTag('tr');
  if (!selRow){
    alert("Devi prima selezionare una riga di tabella");
    return;
  }
  selRow.parentNode.removeChild(selRow);
}

MyHTML.prototype.addCell = function(beforeAfter){
  selCell = this.findParentTag("td");
  if (!selCell) return alert("Devi prima selezionare una cella");
  if (beforeAfter == 2) nextCell = selCell.nextSibling;
  else nextCell = selCell;
  td = this.doc.createElement('td');
  td.innerHTML = '<br>';
  if (nextCell) selCell.parentNode.insertBefore(td, nextCell);
  else selCell.parentNode.appendChild(td);
}

MyHTML.prototype.deleteCell = function(){
  selCell = this.findParentTag("td");
  if (!selCell) return alert("Devi prima selezionare una cella");
  selCell.parentNode.removeChild(selCell);
}

MyHTML.prototype.mergeCells = function(){
  selCells = this.getSelectionStartEnd();
  t1 = selCells[0];
  t2 = selCells[1];
  cell1 = this.findParentTag('td', t1);
  cell2 = this.findParentTag('td', t2);
  if (!cell1 || !cell2) return alert("Devi prima selezionare due celle");
  if (cell1 == cell2) return alert("Devi selezionare due celle distinte");
  cell1.innerHTML += " "+cell2.innerHTML;
  cell2.parentNode.removeChild(cell2);
}

MyHTML.prototype.findText = function(text){
  var win;
  //this.TRange = null;
  var strFound;
  if (this.mode == 'html')win = this.contentWindow;
  else if (this.mode == 'source') win = window;
  if (win.find){
    var found = win.find(text, false, false);
    if (!found){
      while (win.find(text, false, true)){
        //do nothing
      }
    }
  }
  else if (navigator.appName.indexOf("Microsoft")!=-1) {
    if (this.TRange!=null) {
     this.TRange.collapse(false)
     strFound=this.TRange.findText(text)
     if (strFound) this.TRange.select()
    }
    if (this.TRange==null || strFound==0) {
     this.TRange=win.document.body.createTextRange()
     strFound=this.TRange.findText(text)
     if (strFound) this.TRange.select()
    }
   }
}

MyHTML.prototype.getSelectionEl = function(){
  var range;
  var sel;
  if (window.getSelection){ //moz
    sel = this.contentWindow.getSelection();
    if (!sel) return;
    range = sel.getRangeAt(0);
    var p = range.commonAncestorContainer;
    var p1 = range.startContainer;
    var p2 = range.endContainer;
    //control elements (table, img...)
    if ( range.startContainer == range.endContainer && (range.endOffset - range.startOffset) == 1 ){
      return sel.anchorNode.childNodes[ sel.anchorOffset ] ;
    }
    while (p1.nodeType != 1) p1 = p1.parentNode;
    while (p2.nodeType != 1) p2 = p2.parentNode;
    if (p1 == p2) return p1;
    //if (range.startContainer.nodeType == 1 && range.startContainer == range.endContainer) p = range.startContainer.childNodes[range.startOffset];
    //if (!range.collapsed && range.startContainer == range.endContainer &&
    //    range.startOffset - range.endOffset <= 1 && range.startContainer.hasChildNodes())
    //  p = range.startContainer.childNodes[range.startOffset];
    while (p.nodeType != 1) {
      p = p.parentNode;
    }
    return p;
  }
  else if (document.selection){ //ie
    range = this.doc.selection.createRange();
		switch (this.doc.selection.type) {
		    case "Text":
		    case "None":
			return range.parentElement();
		    case "Control":
			return range.item(0);
		    default:
			return this.body;
	  }
	  
	  
  }
  else return;
}

MyHTML.prototype.getSelectionStartEnd = function(){
  var range;
  var sel;
  if (window.getSelection){
    sel = this.contentWindow.getSelection();
    range = sel.getRangeAt(0);
    var p1, p2;
    if (range.startContainer.nodeType == 3) p1 = range.startContainer.parentNode;
    else p1 = range.startContainer.childNodes[range.startOffset];
    if (range.endContainer.nodeType == 3) p2 = range.endContainer.parentNode;
    else p2 = range.endContainer.childNodes[range.endOffset];
    while (p1.nodeType != 1) p1 = p1.parentNode;
    while (p2.nodeType != 1) p2 = p2.parentNode;
  }
  var selElements = new Array();
  selElements[0] = p1;
  selElements[1] = p2;
  return selElements;
  //:TODO: ie 
}

MyHTML.prototype.saveSelection = function(){
  if (window.getSelection) {
    var selection = this.contentWindow.getSelection();
    if (selection.rangeCount > 0) {
      var selectedRange = selection.getRangeAt(0);
      this.selection = selectedRange.cloneRange();
    }
    else {
      return null;
    }
  }
  else if (document.selection) {
    var selection = this.doc.selection;
    if (selection.type.toLowerCase() == 'text') {
      this.selection = selection.createRange().getBookmark();
    }
    else {
      return null;
    }
  }
  else {
    return null;
  }
}

MyHTML.prototype.restoreSelection = function(node){
  this.contentWindow.focus();
  if (this.selection) {
    if (window.getSelection) {
      var selection = this.contentWindow.getSelection();
      if (!selection) return;
      if (node && node.innerHTML == ''){
        node.innerHTML = '&nbsp;';
        this.selection.selectNodeContents(node);
      }
      selection.removeAllRanges();
      selection.addRange(this.selection);
    }
    else if (document.selection && document.body.createTextRange) {
      var range = this.body.createTextRange();
      range.moveToBookmark(this.selection);
      range.select();
    }
  }
}

MyHTML.prototype.clearFormat = function(){
  var text = this.getSelectedText();
  var text = document.createTextNode(text);
  this.insertAtSelection(text);
}

MyHTML.prototype.getSelectedText = function(){
  if (window.getSelection) return this.contentWindow.getSelection()+"";
	else if (this.doc.selection) return this.doc.selection.createRange().text;
	else return "";
}


MyHTML.prototype.getSelectedHTML = function(){
  var sel;
  if (window.getSelection){
    sel = this.contentWindow.getSelection();
    if (sel.rangeCount > 0) sel = sel.getRangeAt(0);
  }
  else if (this.doc.selection) sel = this.doc.selection.createRange();
  else return "";
  var b = document.createElement('body');
  if (sel.cloneContents) b.appendChild(sel.cloneContents());
  else if (typeof(sel.item) != 'undefined' || typeof(sel.htmlText) != 'undefined'){
    b.innerHTML = sel.item ? sel.item(0).outerHTML : sel.htmlText;
  }
  else b.innerHTML = sel.toString();
  return b.innerHTML;
}

MyHTML.prototype.insertAtSelection = function(element){
  var range;
  var sel;
  if (this.doc.selection){ //ie
    range = this.doc.selection.createRange();
    var content;
    if (element.outerHTML) content = element.outerHTML;
    else if (element.outerText) content = element.outerText;
    else if (element.data) content = element.data;
    range.pasteHTML(content);
  }
  else if (window.getSelection){ //moz
    sel = this.contentWindow.getSelection();
    range = sel.getRangeAt(0);
    sel.removeAllRanges();
		range.deleteContents();
		var node = range.startContainer;
		var pos = range.startOffset;
		var nextNode;
		if (node.nodeType == 3){
		  node = node.splitText(pos);
		  node.parentNode.insertBefore(element, node);
		}
		else node.insertBefore(element, node.childNodes[pos]);
	  range.selectNodeContents(element);
		sel.addRange(range);
  }
  else return;
  this.updateToolbar();
}

MyHTML.prototype.splitAncestor = function(el, ancestor){
  if (!el) return;
  var p = el.parentNode;
  if (ancestor){
    var currP = p;
    var ancestors = new Array();
    while (currP != ancestor){
      ancestors[ancestors.length] = currP;
      currP = currP.parentNode;
    }
    for (var i=ancestors.length-1; i>=0; i--){
      this.splitAncestor(ancestors[i]);
      var clone = ancestor.cloneNode(false);
      for (var j in anchestors[i].childNodes){
        var c = anchestors[i].childNodes[j];
        if (c.nodeType == 1 || c.nodeType == 3){
          anchestors[i].removeChild(c);
          clone.appendChild(c);
        }
      }
      //clone.innerHTML = ancestors[i].innerHTML;
      //ancestors[i].innerHTML = '';
      ancestors[i].appendChild(clone);
    }
  }
  var nextNode = p.nextSibling;
  var a = p.cloneNode(false);
  var b = p.cloneNode(false);
  var foundEl = 0;
  for (var i in p.childNodes){
    var child = p.childNodes[i];
    if (child.nodeType == undefined) continue; //?!?
    var clone = child.cloneNode(true);
    if (foundEl) b.appendChild(clone);
    else if (p.childNodes[i] == el) foundEl = 1;
    else a.appendChild(clone);
  }
  var grandpa = p.parentNode;
  grandpa.removeChild(p);
  grandpa.insertBefore(b, nextNode);
  grandpa.insertBefore(el, b);
  grandpa.insertBefore(a, el);
  
}

MyHTML.prototype.valueChange = function(el){
  var el = this.getSelectionEl();
  if (el.parentNode) el = el.parentNode;
  this.cleanUp(el);
  this.hidden.value = this.body.innerHTML;
  //alert('value change '+this.hidden.value);
//  if (this.master){
//    this.master.setValue(this.body.innerHTML);
//  }
}

MyHTML.prototype.setValue = function(html){
  try{
    if (html == 'undefined') html = '';
    //this.doc.body.innerHTML = '';
    //this.doc.open();
    //this.doc.write(html);
    //this.doc.close();
    this.body = this.doc.body;
    //this doesn't work in inline mode: use innerHTML instead, BUT
    //it has a bug, you have to enter text to have backspace working
    this.body.innerHTML = html;
    this.hidden.value = this.body.innerHTML;

    this.valueChange();
    this.attachEvents();
    //head = this.doc.getElementsByTagName('head')[0];
    //:FIXME: this is needed for styles but breaks explorer
    //head.innerHTML = '<style>'+this.config.cssString+'</style>';
    //var style = this.doc.createElement('style');
    //style.innerHTML = this.config.cssString;
    //head.appendChild(style);
  }
  catch(exc){
    html = html.replace(/'/g, "\\'");
    html = html.replace(/(\n|\r)/g, "");
    this.setTimeout("setValue('"+html+"')", 100);
  }
  this.updateToolbar();
}

MyHTML.prototype.cleanUp = function(el){
  var toDel = new Array();
  if (!el) return;
  try{
    while (el.nodeType != 1) el = el.parentNode;
    var elTag = el.tagName.toLowerCase();
  } catch(exc){ //this should not be needed... but it is
    return;
  }
  
  for (var i in el.childNodes){
    var node = el.childNodes[i];
    if (!node || node.nodeType != 1) continue;
    var tag = node.tagName.toLowerCase();
    if (tag == 'span' || tag == 'div'){
      if (node.innerHTML == '' || node.innerHTML.match(/(&nbsp;|\s)+/)){
        toDel[toDel.length] = node;
      }
    }
    this.cleanUp(node);
  }
  for (var i in toDel){
    try{
      var text = document.createTextNode(toDel[i].innerHTML);
      el.replaceChild(toDel[i], text);
    } catch (exc){
    }
  }
}
  

