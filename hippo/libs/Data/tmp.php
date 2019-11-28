<?    
    #process external texts
    #:ERROR: something wrong? too  many stores?
    if ( is_array($this->externalTexts) ){
      foreach ( array_keys($this->externalTexts) as $extTextElement ){
        $fileName = $this->externalTexts[$extTextElement]['file'];
        $fp = fopen(PATH_EXTERNAL_FILES.'/'.$fileName, 'w');
        fwrite($fp, $this->externalTexts[$extTextElement]['text']);
        fclose($fp);
        $storer = new DataStorer_db('_externalFiles');
        $data = new PHPelican();
        $data->set('file', $this->externalTexts[$extTextElement]['file']);
        $data->set('counter', sizeof($this->idArray) );
        $storer->store($data);
      }
      if ( $this->mode == 'update' ){ #cleanup old files
        while ( $this->oldVals->moveNext() ){
          foreach ( array_keys($this->externalTexts) as $extTextElement){
            $file = $this->oldVals->get('_'.$extTextElement);
            if ($file) $fileCounter[$file]++;
          }
        }
        foreach ( $fileCounter as $file => $count ){
          $loader = new DataLoader_db('_externalFiles');
          $loadOptions = new LoadOptions();
          $loadOptions->request('counter');
          $params = new QueryParams();
          $params->add('file', $file);
          $loader->setRequests($loadOptions);
          $loader->setParams($params);
          $list = $loader->load();
          $oldCount = $list->get('counter');
          $newCount = $oldCount - $count;
          if ($newCount == 0){
            unlink(PATH_EXTERNAL_FILES."/$file");
            $deleter = new DataDeleter_db('_externalFiles');
            $deleter->setParams($params);
            $deleter->delete();
          }
          else{
            $storer = new DataStorer_db('_externalFiles');
            $dataHolder = new DataHolder();
            $dataHolder->set('id', $list->get('id') );
            $dataHolder->set('counter', $newCount);
            $storer->store($dataHolder);
          }
        }
      }
    }
    
 ?>