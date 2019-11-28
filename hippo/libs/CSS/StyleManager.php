<?

class StyleManager{
  var $css;

  function getCSSClassName(){
    return '';
  }
  
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
        if ($className{0} == '*'){ #not used
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
  
  function saveTo($file){
    $fp = fopen($file, 'w');
    foreach (array_keys($this->css) as $styleName){
      fwrite($fp, $styleName);
      fwrite($fp, "{\n");
      if (is_array($this->css[$styleName])) foreach ($this->css[$styleName] as $name => $value){
        fwrite($fp, "$name: $value;\n");
      }
      fwrite($fp, "}\n\n");
    }
    fclose($fp);
  }


}



?>