<?php

    require_once "HTTP/WebDAV/Server.php";
    
    /**
     * Filesystem access using WebDAV
     *
     * @access public
     */
    class HTTP_WebDAV_Server_Hippo extends HTTP_WebDAV_Server 
    {
        var $pathParts;
        var $nameSpace;
        var $structLocalName;
        var $structName;
        var $itemName;
        var $itemId;
        var $element;
        var $suffix;
        var $mode;
        var $nameLength = 20;


        /**
         * Serve a webdav request
         *
         * @access public
         * @param  string  
         */
        function ServeRequest($base = false) 
        {
            // special treatment for litmus compliance test
            // reply on its identifier header
            // not needed for the test itself but eases debugging
            foreach(apache_request_headers() as $key => $value) {
                if(stristr($key,"litmus")) {
                    error_log("Litmus test $value");
                    header("X-Litmus-reply: ".$value);
                }
            }


            // let the base class do all the work
            header("X-Starting: starting");
            parent::ServeRequest();
        }

        /**
         * No authentication is needed here
         *
         * @access private
         * @param  string  HTTP Authentication type (Basic, Digest, ...)
         * @param  string  Username
         * @param  string  Password
         * @return bool    true on successful authentication
         */
        function check_auth($type, $user, $pass) 
        {
            return true;
        }


        /**
         * PROPFIND method handler
         *
         * @param  array  general parameter passing array
         * @param  array  return array for file properties
         * @return bool   true on success
         */
        function PROPFIND(&$options, &$files) 
        {
            global $IMP; 
            $i = new Install();
            // prepare property array
            $this->parsePath($options['path']);
            if ($this->structName) $struct = $IMP->typeSpace->getStructure($this->structName);
            $files["files"] = array();
            $files["files"][] = $this->fileinfo($options["path"]);            
            $size = sizeof($this->pathParts);
            if ($size < 1){
              //list nameSpaces
              $IMP->debug('Listing nameSpaces', 4, 'webdav');
              $nameSpaces = $i->listNameSpaces();
              foreach ($nameSpaces as $nameSpace){
                $files["files"][] = $this->fileInfo("/$nameSpace");
              }
            }
            elseif ($size == 1){
              //list structs
              $IMP->debug("Listing structs for {$this->nameSpace}", 4, 'webdav');
              $i->parseNameSpace($this->nameSpace);
              $structs = $i->getStructs($this->nameSpace);
              foreach ($structs as $struct){
                list ($accessMode, $nameSpace, $localName, $dir) = parseClassName($struct);
                $files["files"][] = $this->fileInfo("/{$this->nameSpace}/$localName");
              }
            }
            elseif ($size == 2){
              //list data
              $IMP->debug("Listing data for {$this->structName}", 4, 'webdav');
              $loader = & $IMP->getLoader($this->structName);
              $loader->requestNames();
              $list = $loader->load();
              while ($list->moveNext()){
                $name = $list->get('id').' -';
                $names = $struct->getNames();
                foreach ($names as $nameEl){
                  $name .= " ".$list->get($nameEl);
                }
                $files["files"][] = $this->fileInfo("/{$this->nameSpace}/{$this->structLocalName}/$name");
              }
            }
            elseif ($size == 3){
              //list elements
              $IMP->debug("Listing elements for {$this->structName}", 4, 'webdav');
              $loader = & $IMP->getLoader($this->structName);
              $loader->requestAll();
              $loader->addParam('id', $id);
              $row = $loader->load();
              $elements = $struct->getSimpleElements();
              $IMP->debug("Base type elements: ", 5, 'webdav');
              $IMP->debug($elements, 5, 'webdav');
              foreach ($elements as $element){
                $data = $row->get($element);
                $type = $struct->type($element);
                $known['type'] = $type;
                $known['data'] = $data;
                $files["files"][] = $this->fileInfo("/{$this->nameSpace}/{$this->structLocalName}/{$this->itemName}/$element", $known);
              }
            }
            // ok, all done
            return true;
        } 
        
        /**
         * Get properties for a single file/resource
         *
         * @param  string  resource path
         * @return array   resource properties
         */
        function fileinfo($path, $known=0) 
        {   
            global $IMP;
            $IMP->debug("fileinfo($path)", 5, 'webdav');
            if (!$known) $known = array(); //to avoid errors
            $pathInfo = $this->parsePath($path, 1);
            $IMP->debug("pathInfo:", 5, 'webdav');
            $IMP->debug($pathInfo, 5, 'webdav');
            $size = sizeof($pathInfo['parts']);
            if ($size <= 3){
              //namespaces & structs & items
              $isDir = true;
            }
            elseif ($size == 4){
              if ($known['type'] && $IMP->typeSpace->isBaseType($known['type'])){
                $isDir = false;
              }
              else $isDir = true;
            }
            $IMP->debug("DIR: $isDir", 5, 'webdav');
            
            if ($known['type']){
              switch($known['type']){
                case 'text':
                case 'longText':
                case 'password':
                case 'html':
                case 'richText':
                  $fsize = strlen($known['data'])*8;
                  break;
              }
              switch($known['type']){
                case 'text':
                case 'longText':
                case 'password':
                  $extension = '.txt';
                  $mime = 'text/plain';
                  break;
                case 'html':
                case 'richText':
                  $mime = 'text/html';
                  $extension = '.html';
                  break;
              }
              $IMP->debug("Known type: ".$known['type'].", mime: $mime, size: $fsize", 5, 'webdav');
              $IMP->debug("Data: ", 5, 'webdav');
              $IMP->debug($known['data'], 5, 'webdav');
            }

            if (!$pathInfo['extension'] && $extension) $path .= $extension;

            // create result array
            $info = array();
            $info["path"]  = $path;    
            $info["props"] = array();
            
            // no special beautified displayname here ...
            $info["props"][] = $this->mkprop("displayname", strtoupper($path));
            
            // creation and modification time
            $info["props"][] = $this->mkprop("creationdate",    mktime()-1000);
            $info["props"][] = $this->mkprop("getlastmodified", mktime()-1000);

            // type and size (caller already made sure that path exists)
            if ($isDir) {
                // directory (WebDAV collection)
                $info["props"][] = $this->mkprop("resourcetype", "collection");
                $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
                $info["props"][] = $this->mkprop("getcontentlength", 0);
            } else {  //size and mime type must have been determined from $known
                // plain file (WebDAV resource)
                $info["props"][] = $this->mkprop("resourcetype", "");
                if ($mime) {
                    $info["props"][] = $this->mkprop("getcontenttype", $mime);
                } else {
                    $info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
                }               
                $info["props"][] = $this->mkprop("getcontentlength", $fsize);
            }


            $IMP->debug("Returning info:", 5, 'webdav');
            $IMP->debug($info, 5, 'webdav');
            return $info;
        }

        /**
         * detect if a given program is found in the search PATH
         *
         * helper function used by _mimetype() to detect if the 
         * external 'file' utility is available
         *
         * @param  string  program name
         * @param  string  optional search path, defaults to $PATH
         * @return bool    true if executable program found in path
         */
        function _can_execute($name, $path = false) 
        {
            // path defaults to PATH from environment if not set
            if ($path === false) {
                $path = getenv("PATH");
            }
            
            // check method depends on operating system
            if (!strncmp(PHP_OS, "WIN", 3)) {
                // on Windows an appropriate COM or EXE file needs to exist
                $exts = array(".exe", ".com");
                $check_fn = "file_exists";
            } else { 
                // anywhere else we look for an executable file of that name
                $exts = array("");
                $check_fn = "is_executable";
            }
            
            // now check the directories in the path for the program
            foreach (explode(PATH_SEPARATOR, $path) as $dir) {
                // skip invalid path entries
                if (!file_exists($dir)) continue;
                if (!is_dir($dir)) continue;

                // and now look for the file
                foreach ($exts as $ext) {
                    if ($check_fn("$dir/$name".$ext)) return true;
                }
            }

            return false;
        }

        
        /**
         * try to detect the mime type of a file
         *
         * @param  string  file path
         * @return string  guessed mime type
         */
        function _mimetype($fspath) 
        {   
            if (empty($mime_type)) {
                switch (strtolower(strrchr(basename($fspath), "."))) {
                case ".html":
                case ".htm":
                    $mime_type = "text/html";
                    break;
                case ".gif":
                    $mime_type = "image/gif";
                    break;
                case ".jpg":
                    $mime_type = "image/jpeg";
                    break;
                case ".txt":
                case ".xml":
                    $mime_type = "text/plain";
                    break;
                default: 
                    $mime_type = "application/octet-stream";
                    break;
                }
            }
            
            return $mime_type;
        }

        function parsePath($path, $query=0){
          global $IMP;
          $IMP->debug('Parsing path:', 5, 'webdav');
          $IMP->debug($path, 5, 'webdav');
          $eparts = explode('/', $path);
          $parts = array();
          foreach ($eparts as $part){
            if ($part) array_push($parts, $part);
          }
          $nameSpace = $parts[0];
          $structLocalName = $parts[1];
          if ($nameSpace && $nameSpace != 'base' && $structLocalName) $structName = $nameSpace.'::'.$structLocalName;
          else $structName = $structLocalName;
          $name = $parts[2];
          if (preg_match('/^(\d+)\s/', $name, $matches)){
            $id = $matches[1];
          }
          $element = $parts[3];
          if (preg_match('/(.+)\.(\w+)$/', $element, $matches)){
            $pureElement = $matches[1];
            $suffix = $matches[2];
          }
          else $pureElement = $element;
          $subFile = $parts[4];
          if (!$query){
            $this->pathParts = $parts;
            $this->nameSpace = $nameSpace;
            $this->structLocalName = $structLocalName;
            $this->structName = $structName;
            $this->itemName = $name;
            $this->itemId = $id;
            $this->element = $pureElement;
            $this->suffix = $suffix;
            $this->subFile = $subFile;
          }
          $IMP->debug("Struct: $structName, NAME: $name, ID: $id, El: $pureElement, SubF: $subFile", 5, 'webdav');
          $IMP->debug("Path parts:", 5, 'webdav');
          $IMP->debug($parts, 5, 'webdav');
          return array('parts' => $parts, 'nameSpace' => $nameSpace, 
                       'structLocalName' => $structLocalName, 'structName' => $structName,
                       'itemName' => $name, 'itemId' => $id, 'element' => $pureElement,
                       'suffix' => $suffix, 'subFile' => $subFile);
        }

        /**
         * GET method handler
         * 
         * @param  array  parameter passing array
         * @return bool   true on success
         */
        function GET(&$options) 
        {
          global $IMP;
          $IMP->debug("GET called with options:", 4, 'webdav');
          $IMP->debug($options, 4, 'webdav');
          $this->parsePath($options['path']);
          $loader = & $IMP->getLoader($this->structName);
          $loader->request($this->element);
          $loader->request('cr_date');
          $loader->request('mod_date');
          $loader->addParam('id', $this->itemId);
          $row = $loader->load();
          $data = $row->get($this->element);
          $IMP->debug("GETting data:", 5, 'webdav');
          $IMP->debug($data, 5, 'webdav');
          $crDate = $row->get('cr_date');
          $modDate = $row->get('mod_date');
          $temp = tempnam('/tmp', 'DAV');
          $fp = fopen($temp, 'w');
          fwrite($fp, $data);
          fclose($fp);
          // get absolute fs path to requested resource
            $fspath = $temp;

            // sanity check
            if (!file_exists($fspath)) return false;
            
            // detect resource type
            $options['mimetype'] = $this->_mimetype($fspath); 
                
            // detect modification time
            // see rfc2518, section 13.7
            // some clients seem to treat this as a reverse rule
            // requiering a Last-Modified header if the getlastmodified header was set
            $options['mtime'] = filemtime($fspath);
            
            // detect resource size
            $options['size'] = filesize($fspath);
            
            // no need to check result here, it is handled by the base class
            $options['stream'] = fopen($fspath, "r");
            
            return true;
        }

        
        /**
         * PUT method handler
         * 
         * @param  array  parameter passing array
         * @return bool   true on success
         */
        function PUT(&$options) 
        {
            global $IMP;
            
            $this->parsePath($options["path"]);
            $IMP->debug('PUTting data (to path '.$options["path"].'):', 5, 'webdav');
            if (!$this->structName || !$this->itemName) return "403 Forbidden";
            $data = '';
            while (!feof($options["stream"])) {
              $data .= fread($options["stream"], 8192);
            }
            $IMP->debug($data, 5, 'webdav');
            $IMP->debug("Type:", 5, 'webdav');
            $IMP->debug(get_class($data), 5, 'webdav');
            #fclose($options["stream"]);
            if ($this->element){
              if (!$this->itemId) return "403 Forbidden";
              if ($this->subFile){
                $loader = & $IMP->getLoader($this->structName);
                $loader->addParam('id', $this->itemId);
                $loader->request($this->element);
                $list = $loader->load();
                $value = $list->get($this->element);
                if (!strstr($value, $this->subFile)){
                  return "204 No Content";
                }
                $tmp = tempnam();
                $tmpFile = fopen($tmp);
                fwrite($tmpFile, $data);
                fclose($tmpFile);
                //:FIXME: fix Images image detection, use it
                if (preg_match('/\.(jpeg|jpg|png)/', $this->subFile)){
                  $fileName = $IMP->images->store($tmpFile, $this->subFile);
                  $destUrl = URL_WEBDATA.'/img/orig';
                }
                else{
                  $fileName = $IMP->files->store($tmpFile, $this->subFile);
                  $destUrl = URL_WEBDATA;
                }
                $value = str_replace($this->element.'/'.$this->subFile, $destUrl.'/'.$fileName, $value);
                $data = $value;
                $struct = & $IMP->typeSpace->getStruct($this->structName);
                $dirElement = $IMP->config['webdav']['defaultDirElement'][$this->structName];
                if ($struct->hasElement($this->element)) $element = $this->element;
                elseif ($dirElement) $element = $dirElement;
                else return "403 Forbidden";
              }
              else{
                $element = $this->element;
              }
              $storer = & $IMP->getStorer($this->structName);
              $storer->set('id', $this->itemId);
              $storer->set($element, $data);
              $storer->store();
              #return "204 No Content";
              return "201 Created";
            }
            else{
              //this->name is the file
              $config = $IMP->config['webdav']['defaultElement'][$this->structName];
              $fileName = $this->itemName;
              $IMP->debug("Storing file: $fileName", 5, 'webdav');
              $nameData = $data;
              if (preg_match('/(.+)\.(.+)$/', $fileName, $matches)){
                $extension = strtolower($matches[2]);
                $baseName = $matches[1];
                $IMP->debug("BaseName: $baseName, extension: $extension", 5, 'webdav');
                if ($config[$extension]){
                  $element = $config[$extension];
                  $nameData = $baseName;
                }
              }
              $storer = & $IMP->getStorer($this->structName);
              $struct = $IMP->typeSpace->getStructure($this->structName);
              $names = $struct->getNames();
              $name = $names[0];
              $storer->set($name, $nameData);
              if ($element) $storer->set($element, $data);
              $storer->store();
              return "201 Created";
            }
            return "403 Forbidden";
        }


        /**
         * MKCOL method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function MKCOL($options) 
        {           
            $path = $this->base .$options["path"];
            $parent = dirname($path);
            $name = basename($path);

            return ("201 Created");
        }
        
        
        /**
         * DELETE method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function delete($options) 
        {
            global $IMP;
            $this->parsePath($options["path"]);
            if (!$this->itemId || $this->element) return "403 Forbidden";
            $deleter = & $IMP->getDeleter($this->structName);
            $deleter->addParam('id', $this->itemId);
            $deleter->go();

            return "204 No Content";
        }


        /**
         * MOVE method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function move($options) 
        {
            return $this->copy($options, true);
        }

        /**
         * COPY method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function copy($options, $del=false) 
        {
            // TODO Property updates still broken (Litmus should detect this?)

            
            if(!empty($_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
                return "415 Unsupported media type";
            }

            // no copying to different WebDAV Servers yet
            if(isset($options["dest_url"])) {
                return "502 bad gateway";
            }

            $source = $this->base .$options["path"];
            if(!file_exists($source)) return "404 Not found";

            $dest = $this->base . $options["dest"];

            $new = !file_exists($dest);
            $existing_col = false;

            if(!$new) {
                if($del && is_dir($dest)) {
                    if(!$options["overwrite"]) {
                        return "412 precondition failed";
                    }
                    $dest .= basename($source);
                    if(file_exists($dest.basename($source))) {
                        $options["dest"] .= basename($source);
                    } else {
                        $new = true;
                        $existing_col = true;
                    }
                }
            }

            if(!$new) {
                if($options["overwrite"]) {
                    $stat = $this->delete(array("path" => $options["dest"]));
                    if($stat{0} != "2") return $stat; 
                } else {                
                    return "412 precondition failed";
                }
            }

            if (is_dir($source)) {
                // RFC 2518 Section 9.2, last paragraph
                if ($options["depth"] != "infinity") {
                    error_log("---- ".$options["depth"]);
                    return "400 Bad request";
                }
                system(escapeshellcmd("cp -R ".escapeshellarg($source) ." " .  escapeshellarg($dest)));

                if($del) {
                    system(escapeshellcmd("rm -rf ".escapeshellarg($source)) );
                }
            } else {                
                if($del) {
                    @unlink($dest);
                    $query = "DELETE FROM properties WHERE path = '$options[dest]'";
                    mysql_query($query);
                    rename($source, $dest);
                    $query = "UPDATE properties SET path = '$options[dest]' WHERE path = '$options[path]'";
                    mysql_query($query);
                } else {
                    if(substr($dest,-1)=="/") $dest = substr($dest,0,-1);
                    copy($source, $dest);
                }
            }

            return ($new && !$existing_col) ? "201 Created" : "204 No Content";         
        }

        /**
         * PROPPATCH method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function proppatch(&$options) 
        {
            global $prefs, $tab;

            $msg = "";
            
            $path = $options["path"];
            
            $dir = dirname($path)."/";
            $base = basename($path);
            
            foreach($options["props"] as $key => $prop) {
                if($ns == "DAV:") {
                    $options["props"][$key][$status] = "403 Forbidden";
                } else {
                    if(isset($prop["val"])) {
                        $query = "REPLACE INTO properties SET path = '$options[path]', name = '$prop[name]', ns= '$prop[ns]', value = '$prop[val]'";
                    } else {
                        $query = "DELETE FROM properties WHERE path = '$options[path]' AND name = '$prop[name]' AND ns = '$prop[ns]'";
                    }       
                    mysql_query($query);
                }
            }
                        
            return "";
        }


        /**
         * LOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function lock(&$options) 
        {
            if(isset($options["update"])) { // Lock Update
                $query = "UPDATE locks SET expires = ".(time()+300);
                mysql_query($query);
                
                if(mysql_affected_rows()) {
                    $options["timeout"] = 300; // 5min hardcoded
                    return true;
                } else {
                    return false;
                }
            }
            
            $options["timeout"] = time()+300; // 5min. hardcoded

            $query = "INSERT INTO locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0")
                ;
            mysql_query($query);
            return mysql_affected_rows() > 0;

            return "200 OK";
        }

        /**
         * UNLOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function unlock(&$options) 
        {
            $query = "DELETE FROM locks
                      WHERE path = '$options[path]'
                        AND token = '$options[token]'";
            mysql_query($query);

            return mysql_affected_rows() ? "200 OK" : "409 Conflict";
        }

        /**
         * checkLock() helper
         *
         * @param  string resource path to check for locks
         * @return bool   true on success
         */
/*        function checkLock($path) 
        {
            $result = false;
            
            $query = "SELECT owner, token, expires, exclusivelock
                  FROM locks
                 WHERE path = '$path'
               ";
            $res = mysql_query($query);

            if($res) {
                $row = mysql_fetch_array($res);
                mysql_free_result($res);

                if($row) {
                    $result = array( "type"    => "write",
                                                     "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                                     "depth"   => 0,
                                                     "owner"   => $row['owner'],
                                                     "token"   => $row['token'],
                                                     "expires" => $row['expires']
                                                     );
                }
            }

            return $result;
        }
*/

        /**
         * create database tables for property and lock storage
         *
         * @param  void
         * @return bool   true on success
         */
        function create_database() 
        {
            // TODO
            return false;
        }
    }


?>
