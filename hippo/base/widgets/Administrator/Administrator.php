<?

class Administrator extends BasicWidget{
  var $action;
  var $params;
  var $activeWidget;
  var $structures;
  var $structNames;
  var $currentStructName;
  var $menu;
  var $widgets;
  var $widgetTemplates;
  var $visWidgets;
  var $formWidgets;
  var $section;
  var $sections;
  var $sideTabs;


  function Administrator($name){
    parent::BasicWidget($name);
    $this->structures = array();
    $this->custom = array();
    $this->section('structs');
    $this->menu->structs->_label = 'Dati';
  }

  function administer($structName, $menuName, $formWidget='Form'){
    $this->structNames[sizeof($this->structures)] = $structName;
    array_push($this->structures, $structName);
    $this->menu->{$this->section}->$structName->_label = $menuName;
    $this->formWidgets[$structName] = $formWidget;
  }

  function customPage($file, $menuName){
      $this->menu->{$this->section}->$menuName->_label = $menuName;
      $this->customPages[$menuName] = $file;
  }

  function addWidget($menuName, $widget){
    //:TODO:
  }


  function section($name){
    $this->menu->$name->_label = $name;
    $this->section = $name;
    $this->sections[] = $name;
  }

  function start(){
    $this->loadWidgets();
    parent::start();
  }


  function loadWidgets(){
    global $IMP;
    $this->currentStructName = $this->getParam('widget');
    if (!$this->currentStructName || !in_array($this->currentStructName, $this->structNames)){
      $this->currentStructName = $this->structNames[0];
    }
    $this->setParam('currentStruct', $this->currentStructName);
    $menuWidget = & $this->createWidget('Menu', $this->name.'menu');
    $menuWidget->setCurrent($this->currentStructName);
    $this->widgets['menu'] = & $menuWidget;
    if (!$this->currentStructName) return;
    if (!$this->action) $this->action = $this->getParam('action');
    #$this->clearParam('action');
    if (!$this->action) $this->action = 'table';
    if ($this->action == 'form'){
      $form = & $this->createWidget($this->formWidgets[$this->currentStructName], 'form_'.$this->currentStructName);
      if ($_GET['id']) $form->setParam('id', $_GET['id']);
      elseif ($_POST['id']) $form->setParam('id', $_POST['id']);
      $form->setStruct($this->currentStructName);
      if ($structName) $form->label = $this->menu->{$structName};
      if ($this->config['form'][$this->currentStructName]) foreach ($this->config['form'][$this->currentStructName] as $key => $value){
        $form->config[$key] = $value;
      }
      $form->generateFromStructure();
      if ($form->storeData()){
        if ($this->hooks['store'][$this->currentStructName]){
          call_user_func($this->hooks['store'][$this->currentStructName], $form->id);
        }
        if ($form->redirect){
            $this->action = $form->redirect['action'];
            $redirectWidget = $form->redirect['widget'];
            $this->setParam('widget', $redirectWidget);
            foreach ($form->redirect['params'] as $key => $value){
                $this->widgetParams->set($this->action.'_'.$redirectWidget, $key, $value);
            }
            return $this->loadWidgets();
        }
        if (!$form->config['stay'] && !$form->redirect){
            $this->setParam('action', 'table');
            $this->action = 'table';
            $goToTable = true;
        }
      }
      if (!$goToTable){
        $form->loadData();
        $this->widgets['form'] = & $form;
      }
      if ($form->getParam('id')){
        $this->sideTabs = & $this->createWidget('TabView');
        $this->sideTabs->setTemplate('accordion');
        #$templates = & $this->createWidget('AdminTools/Templates');
        #$templates->setStruct($this->currentStructName);
        #$templates->id = $form->getParam('id');
        #$templates->load();
        #$this->sideTabs->add('Templates', $templates);
        //$this->storeTemplates($form->getParam('id'));
        $this->storeVisualizations($form->getParam('id'));
        $this->visWidgets = $IMP->widgetsForStruct($this->currentStructName);
        //$this->loadWidgetTemplates($this->currentStructName, $form->getParam('id'));
      }
    }
    if ($this->action == 'table'){
      $search = & $this->createWidget('Form/SearchForm', 'search_'.$this->currentStructName);
      if ($this->config['search'][$this->currentStructName]) foreach ($this->config['searc'][$this->currentStructName] as $key => $value){
        $search->config[$key] = $value;
      }
      $search->setStruct($this->currentStructName);
      $search->generateFromStructure();
      $query = $search->generateQuery();
      $search->store();
      $search->config['showAdvOpts'] = true;
      $this->widgets['search'] = & $search;
      $table = & $this->createWidget('Table', 'table_'.$this->currentStructName);
      $table->config['allowDelete'] = true;
      $table->config['checkWritable'] = true;
      if ($this->config['table'][$this->currentStructName]) foreach ($this->config['table'][$this->currentStructName] as $key => $value){
        $table->config[$key] = $value;
      }
      $table->setStruct($this->currentStructName);
      $table->setParams($query);
      $table->deleteElements();
      $table->load();
      $this->widgets['table'] = & $table;
      $search->config['table'] = $table->name;
    }
    if ($this->action == 'customPage'){
        $this->customPage = $this->customPages[$this->getParam('widget')];
    }
  }


  function storeVisualizations($id){
    if (!$id) return;
    $vis = $this->takeParam('vis');
    if (!is_array($vis)) return;
    foreach (array_keys($vis) as $widget){
      foreach ($vis[$widget] as $mode => $key){
        $varDir = fixForFile(VARPATH."/vis/$widget/".$mode);
        createPath($varDir);
        $varFile = $varDir.'/'.$id;
        $fp = fopen($varFile, 'w');
        fwrite($fp, $key);
        fclose($fp);
      }
    }
  }

  function loadWidgetTemplates($structName, $id){
    global $IMP;
    if (is_array($this->visWidgets)) foreach ($this->visWidgets as $widget){
      list($accessMode, $nameSpace, $localName, $dir) = parseClassName($widget);
      if ($nameSpace) $base = APPS.'/'.$nameSpace;
      else $base = BASE;
      $base .= '/widgets/'.$localName;
      if ($dir) $base .= '/'.$dir;
      $base .= '/templates';
      $paths = search_dir($base, '*.php');
      if (is_array($paths)) foreach ($paths as $path){
        $template = str_replace($base, '', $path);
        $templateDir = dirname($template);
        $path_parts = pathinfo($template);
        $templateDir = $path_parts["dirname"];
        $templateFile = $path_parts["basename"];
        $extension = $path_parts["extension"];
        $templateName = str_replace('.'.$extension, '', $templateFile); #potentially WRONG
        if (!isset($setTemplates[$templateDir])){
          $varFile = VARPATH."/templates/$widget/".$templateDir.'/'.$id;
          if (file_exists($varFile)){
            $set = file_get_contents($varFile);
            $setTemplates[$templateDir] = $set;
          }
        }
        $set = $setTemplates[$templateDir];
        if ($set == $templateName) $templates[$widget][$templateDir][$templateName] = 1;
        else $templates[$widget][$templateDir][$templateName] = 0;
      }
    }
    if (is_array($templates)) ksort($templates);
    $this->widgetTemplates = $templates;
  }
  
  function checkInsertAllowed(){
      global $IMP;
      $mode = 'i';
      if ($IMP->security->checkSuperUser() || $IMP->security->checkPolicy($this->currentStructName, $mode)){
            return true;
        }
      if (is_array($IMP->security->structures[$this->currentStructName])){
            foreach ($IMP->security->structures[$this->currentStructName] as $grant){
                if ( ($grant['ops']['i'] || $grant['ops']['w']) && !$grant['params'] ){
                    return true;
                }
            }
        }
        return false;
  }

}

?>
