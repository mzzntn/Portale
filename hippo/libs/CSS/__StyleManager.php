<?

  
class StyleManager{
  var $styles;
  var $css;
  var $generatedClasses;
  var $activeClasses;
  var $cssDirs;

  function StyleManager(){
    $this->cssDirs = array();
  }
  
  /**
  * void addDir(string)
  * Add a directory to be searched for CSS files
  **/
  function addDir($dir){
    array_push($this->cssDirs, $dir);
  }

  /**
  * void loadStyles()
  * Look for CSS files in the default directories and load them
  **/
  function loadStyles(){
    $this->parseDir(BASE);
    $this->parseDir(APPS);
    foreach ($this->cssDirs as $dir){
      $this->parseDir($dir);
    }
  }

  /**
  * void parseDir(string)
  * Search a directory for CSS files and load them
  *
  * @access private
  *
  **/
  function parseDir($directory){
    $d = dir($directory);
    while (false !== ($entry = $d->read())) {
      $fullPath = $directory.'/'.$entry;
      if ($entry != '.' && $entry != '..' && is_dir($fullPath) ) $this->parseDir($fullPath);
      else{
        $pathParts = pathinfo($entry);
        if ($pathParts['extension'] == 'css'){
          $this->parseFile($fullPath);
        }
      }
    }
  }
  
  /**
  * void parseFile(string)
  * Parse an CSS file
  *
  * @access private
  *
  * This function builds the $this->styles and the $this->css arrays from an ICSS file. The $this->styles array
  * is structured as follows: 
  * $this->styles[$className] is an array[int], where
  * $this->styles[$className][i]['definition'] is an associative array of $parameterName => $parameterValue pairs, and
  * $this->styles[$className][i]['attributesString'] is the original comma separated list of attributes
  * $this->styles[$className][i]['attributes'] is an array of the attributes associated with the definition, if any
  * The $this->css array is an associative array of $parameterName => $parameterValue pairs.
  **/
  
  function parseFile($file){
    $fp = fopen($file, 'r');
    $parsingDefinition = false;
    $parsingName = false;
    $paramName = '';
    $paramValue = '';
    $className = '';
    while ( !feof ($fp) ){
      $char = fgetc($fp);
      if ($char == '{'){ #end of className, start of definition
        $parsingDefinition = true;
        $parsingName = true;
        #if the first char is a '*' the class is an extended ICSS class
        if ($className{0} == '*'){
          $tmpArray['attributes'] = array();
          $className = substr($className, 1);
          #if at the end there is something in brakets, it is interpreted as a comma separated list of attributes 
          #:KLUDGE: reading attributes should be done while parsing
          if ( preg_match('/(.+)\((.+)\)/', $className, $matches) ){
            $className = $matches[1];
            $tmpArray['attributesString'] = $matches[2];
            $tmpArray['attributes'] = explode(',', $matches[2]);
          }
          if ( !is_array($this->styles[$className]) ) $this->styles[$className] = array();
          $numEl = array_push($this->styles[$className], $tmpArray);
          unset($tmpArray);
          $styleDest = & $this->styles[$className][$numEl-1]['definition'];
        }
        else{
          $styleDest = & $this->css[$className];
        }
      }
      elseif (!$parsingDefinition && $char != ' ' && $char != "\n" && $char != "\r" && $char != "\t"){
        $className .= $char;
      }
      if ($parsingDefinition){
        if ($char == '}'){ #end definition
          $parsingDefinition = 0;
          $className = '';
        }
        elseif ($char == ';' || $char == "\n" || $char == "\r"){ #finished reading parameter-value pair
          if ($paramName && $paramValue){
            $styleDest[$paramName] = $paramValue;
          }
          $paramName = '';
          $paramValue = '';
          $parsingName = true;
        }
        elseif ($parsingName){
          if ($char == ':') $parsingName = false;
          elseif ($char != ' ' && $char != "\n" && $char != "\r" && $char != "\t") $paramName .= $char;
        }
        else{
          $paramValue .= $char;
        }
      }
    }
    fclose($fp);
  }
  
  /**
  * array getStyles(string, string[string])
  * Get pointers to the definitions of styles matching $class and $attributes given
  *
  * @access private
  *
  * This function returns an associative array made as:
  * $results[$className] is an array[int]. $className is the name of a CSS class compatible with the given $class and $attributes
  * $results[$className][$key] is set to a pointer to $this->styles[$className][$key].
  * To be compatible with a style, $class must contain all parts of a given class name (in order), having the parts
  * to be $class split by '.', and $attributes must contain all the attributes of the given style.
  **/
  function getStyles($class, $attributes){
    if (is_array($this->styles)) foreach( array_keys($this->styles) as $className){
      #this is to match also styles missing part of the class tree
      $pregClassName = str_replace('.', '.+', $className);
      if ( preg_match("/$pregClassName$/i", $class) ){
        $stylesArrays = & $this->styles[$className];
        foreach ( array_keys($stylesArrays) as $key){
          $valid = true;
          if ( is_array($stylesArrays[$key]['attributes']) ) foreach ($stylesArrays[$key]['attributes'] as $attrName){
            if (!$attributes[$attrName]){
              $valid = false;
              break;
            }
          }
          if ($valid){
            $results[$className][$key] = & $stylesArrays[$key];
          }
        }
      }
    }
    return $results;
  }
  
  
  /**
  * string getCSSClassName(string, string[string])
  * Get a name for the styles associated to $class
  *
  * If the browser supports multiple classes, the string returned will be a comma-separated list of the
  * classes compatible with $class and $attributes, and those will be marked as active to be printed out in the
  * style section.
  * If multiple classes are not supported, an appropriate class will be generated by merging the attributes of
  * the compatible classes, and a $className will be returned;
  * the definitions will be put in $this->userClasses[$className]
  *
  **/
  
  function getCSSClassName($class, $attributes){
    global $IMP;
    $classString = '';
    $classes = $this->getStyles($class, $attributes);
    if ($IMP->supports['css_multiple_classes']){
      if ( is_array($classes) ) foreach( array_keys($classes) as $className){
        if ($classString != '') $classString .= " ";
        $classString .= strtr($className, '.:', '_-');
        foreach ( array_keys($classes[$className]) as $key){
          $classString .= strtr($classes[$className]['key']['attributesString'], '. ', '_');
        }
      }
      if ($classString) $this->activeClasses[$classString] = & $boh;
    }
    else{ #we manage multiple classes on our own
      $definitions = array();
      if ( is_array($classes) ) foreach ( array_keys($classes) as $className){
        if ($classString) $classString .= '_';
        $classString .= strtr($className, '.:', '_-');
        $firstStyle = true;
        foreach ( array_keys($classes[$className]) as $stylesNumber){
          if ($firstStyle) $classString .= '_';
          else $classString .= '-';
          $firstStyle = 0;
          $classString .= $stylesNumber;
          array_push($definitions, & $classes[$className][$stylesNumber]['definition']);
        }
      }
      if ($classString){
        $this->generatedClasses[$classString] = $definitions;
      }
    }
    return $classString;
  }
  
  /**
  * string getStyle(string, string[], string)
  * Get the style definition for an item
  **/
  function getStyle($class, $attributes, $item){
    $styles = $this->getStyles($class, $attributes);
    if ( is_array($styles) ) foreach ( array_keys($styles) as $className){
      foreach ( $styles[$className] as $params){
        if ( $params['definition'][$item] ) $val = $params['definition'][$item];
      }
    }
    $val = trim($val);
    return $val;
  }
  
  function printStyle($classString){
    if ($this->generatedClasses[$classString]) $definitions = & $this->generatedClasses[$classString];
    else if ($this->activeClasses[$classString]) $definitions = $boh;
  }
  
  
  function printStyles(){
    $definitions = array();
    if ( is_array($this->userClasses) ) foreach ( array_keys($this->userClasses) as $userClassName){
      $definitions = array();
      if ( is_array($this->userClasses[$userClassName]) ) foreach ( array_keys($this->userClasses[$userClassName]) as $key){
        $definitions = array_merge($definitions, $this->userClasses[$userClassName][$key]);
      }
      print ".".$userClassName."{";
      foreach ($definitions as $paramName => $paramValue){
        print $paramName.': '.$paramValue.";";
      }
      print "}";
    }
    if ( is_array($this->css) ) foreach ( array_keys($this->css) as $cssElement){
      print $cssElement.'{';
      if ( is_array($this->css[$cssElement]) ) foreach ($this->css[$cssElement] as $paramName => $paramValue){
        print $paramName.': '.$paramValue.";";
      }
      print "}";
    }
  }
  


}


?>