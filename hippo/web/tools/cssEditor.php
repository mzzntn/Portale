<?
include_once('../init.php');
$IMP->loadJs('divControls');
$IMP->loadJs('comboBox');

$properties = array('background', 'background-color', 'color', 'border-width', 'text-align',
                     'font-weight');


function displayInput($name, $value, $fieldName){
  global $properties;
  $align[''] = '';
  $align['left'] = 'Sinistra';
  $align['center'] = 'Centro';
  $align['right'] = 'Destra';
  $align['justify'] = 'Giustificato';
  $weight[''] = '';
  $weight['bold'] = 'Grassetto';
  $weight['bolder'] = 'Più grassetto';
  $weight['normal'] = 'Normale';
  $weight['lighter'] = 'Leggero';
  $type = $properties[$name];
  $id = fixForJs($fieldName);
  switch ($type){
    case 'size':
      print "<input type='range' min='0' max='50' name='$fieldName' value='$value'>";
      break;
    case 'align':
      print "<select name='$fieldName'>";
      printSelectOptions($align, $value);
      print "</select>";
      break;
    case 'weight':
      print "<input type='hidden' name='$fieldName' id='{$id}' value='$value'>";
      print "<div id='{$id}_div'></div>";
      print "<script>";
      print "div = getObj('{$id}_div');";
      print "cb = createComboBox('{$id}', div);";
      print "cb.setHiddenInput('{$id}');";
      passToCombo('cb', $weight);
      print "cb.init();";
      print "</script>";
      break;
    case 'color':
      print "<input type='text' name='$fieldName' value='$value' id='$id'>";
      print "<a href='javascript: chooseColor(\"$id\")'>Scegli</a>";
      break;
    default:
      print "<input type='text' name='$fieldName' value='$value'>";
  }
}

?>

<html>
<head>
<title>CSS Editor</title>
<script src='../js/divControls.js'></script>
<script>
function CSSEditor(className, tableId){
  this.className = className;
  this.name = 'css['+className+']';
  this.inputsTable = getObj(tableId);
  this.types = new Array();
  this.types['background'] = 'color';
  this.types['background-color'] = 'color';
  this.types['color'] = 'color';
  this.types['border-width'] = 'size';
  this.types['width'] = 'size';
  this.types['text-align'] = 'align';
  this.types['font-weight'] = 'weight';
  this.align = new Array();
  this.align[''] = '';
  this.align['left'] = 'Sinistra';
  this.align['center'] = 'Centro';
  this.align['right'] = 'Destra';
  this.align['justify'] = 'Giustificato';
  this.inputsCnt = 0;
}

CSSEditor.prototype.createInput = function(name, value){
  var tr = document.createElement('tr');
  tr.id = this.name+(++this.inputsCnt);
  var td = document.createElement('td');
  td.innerHTML = name;
  tr.appendChild(td);
  td = document.createElement('td');
  type = this.types[name];
  var inputName = this.name + '['+name+']';
  var input;
  switch (type){
    case 'color':
      input = document.createElement('input');
      input.type = 'text';
      input.size = 10;
      input.value = value;
      var a = document.createElement('a');
      a.innerHTML = "Scegli";
      a.href = 'javascript: this.cssEd.chooseColor()';
      a.onclick = function(e){ this.cssEd.chooseColor(this.inputId) };
      a.cssEd = this;
      td.appendChild(input);
      td.appendChild(a);
      //tr.appendChild(td);
      break;
    case 'size':
      input = document.createElement('input');
      input.type = 'text';
      input.size = 6;
      input.value = value;
      td.appendChild(input);
      break;
    case 'align':
      input = document.createElement('hidden');
      var cb = createComboBox(inputName, td);
      cb.hidden = input;
      cb.v = this.align;
      cb.init();
      cb.setValue(value);
      break;
  }
  if (input){
    input.name = inputName;
    input.id = name;
  }
  tr.appendChild(td);
  td = document.createElement('td');
  var a = document.createElement('a');
  a.innerHTML = 'Cancella';
  a.href = '#';
  a.cssEd = this;
  a.onclick = function(e){ this.cssEd.removeInput(this.rowId); };
  a.rowId = this.name+this.inputsCnt;
  td.appendChild(a);
  tr.appendChild(td);
  this.inputsTable.appendChild(tr);
}

CSSEditor.prototype.removeInput = function(rowId){
  row = getObj(rowId);
  if (!row) return;
  makeCool(row);
  row.remove();
}

function addInput(className){
  select = getObj('prop_'+className);
  prop = select.value;
  cssEditor.createInput(prop);
}

</script>

</head>
<body>

<?


$IMP->styleManager->parseFile(PATH_CSS.'/default.css');
$css = $_REQUEST['css'];
if (is_array($css)){
  foreach (array_keys($css) as $className){
    foreach ($css[$className] as $name => $value){
      $IMP->styleManager->css[$className][$name] = $value;
    }
  }
  $IMP->styleManager->saveTo(PATH_CSS.'/default.css');
}
if ($_REQUEST['className']) $classes = explode(' ', $_REQUEST['className']);
else $classes = $_REQUEST['classes'];
foreach ($classes as $class){
  if ($class) $requestClasses[strtolower($class)] = true;
}
$css = $IMP->styleManager->css;
foreach ($css as $className => $defs){
  $cssClasses = explode('.', $className);
  $good = 0;
  foreach ($cssClasses as $cssClassName){
    if (!$cssClassName) continue;
    if (!$requestClasses[strtolower($cssClassName)]){
      $good = false;
      break;
    }
    else $good++;
  }  
  if (!$good || $good < sizeof($requestClasses) ) unset($css[$className]);
}

$cnt=0;
foreach ($css as $className => $defs){
  print "<form name='$className' action='{$_SERVER['PHP_SELF']}' method='POST'>";
  print "<input type='hidden' name='className' value='$className'>";
  print "<b>-$className:</b>";
  print "<table id='t_$className'>";
  print "</table>";
  print "<script>";
  print "var cssEditor = new CSSEditor('$className', 't_$className');";
  foreach ($defs as $name => $value){
    $name = trim($name);
    $value = trim($value);
    print "cssEditor.createInput('$name', '$value');";
  }
  print "</script>";
  print "Aggiungi: ";
  print "<select name='ds' id='prop_$className'>";
  foreach ($properties as $name){
    print "<option value='$name'>$name</option>";
  }
  print "</select> ";
  print "<a href='javascript: addInput(\"$className\")'>Ok</a><br><br>";
  print "<input type='submit' name='submit' value='Salva'>";
  print "</form>";
}
?>

</body>
</html>