<?
include_once('../init.php');
// error_reporting(E_ALL); ini_set('display_errors', 1);

$disclaimerP7m = "<p>Questo file sosituisce a tutti gli effetti il cartaceo. In pratica si tratta di un file (con estensione p7m) che contiene il documento prodotto dal PC (normalmente PDF ma pu&ograve; essere di qualsiasi altro formato) e la/e firma/e. Per approfondire clicca <a href='http://it.wikipedia.org/wiki/Firma_digitale' target='_new'>qui</a>.</p>
<p>Di seguito sono immediatamente disponibili le informazioni contenute nel file (documento e firme) oltre all'originale in formato p7m che tuttavia, per essere letto, necessita di uno specifico programma (p7m reader) che va installato sul proprio dispositivo (PC, tablet, smartphone, etc.).</p>";

$disclaimerPdf = "<p>Questo file sosituisce a tutti gli effetti il cartaceo. In pratica si tratta di un file pdf a cui sono state apposte delle firme digitali. Per approfondire clicca <a href='http://it.wikipedia.org/wiki/Firma_digitale' target='_new'>qui</a>.</p>
<p>Di seguito sono immediatamente disponibili le informazioni di firma contenute nel file, normalmente visbili gi&agrave; dallo strumento di visulizzazione del file del proprio dispositivo (PC, tablet, smartphone, etc.). Alcuni programmi per la visualizzazione potrebbero non essere in grado di visualizzare i dati di firma.</p>";

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function displayDetails($filedata) {
?>
  <?foreach($filedata as $tipo => $data){?>
  <div class="form-group">
    <div class="row">
      <label class="col-lg-2 col-md-2 col-sm-3 control-label-left">Nome file</label>
      <div class="col-lg-2 col-md-2 col-sm-9">
        <p class="form-control-static" style='word-wrap: break-word;'><?=$data["name"]?> (<?=$tipo?>)</p>
      </div>
      <label class="col-lg-2 col-md-2 col-sm-3 control-label-left">Hash (md5)</label>
      <div class="col-lg-2 col-md-2 col-sm-9">
        <p class="form-control-static" style='word-wrap: break-word;'><?=$data["hash"]?></p>
      </div>
      <label class="col-lg-2 col-md-2 col-sm-3 control-label-left">Dimensioni</label>
      <div class="col-lg-2 col-md-2 col-sm-9">
        <p class="form-control-static" style='word-wrap: break-word;'><?=$data["size"]?> (<?=$tipo?>)</p>
      </div>
      <label class="col-lg-2 col-md-2 col-sm-3 control-label-left">Link per il download</label>
      <div class="col-lg-2 col-md-2 col-sm-9">
        <p class="form-control-static"><a class="btn btn-default" href="getDoc.php?<?=$data["download"]?>">Scarica</a></p>
      </div>
    </div>
  </div>
  <?}?>
<?
}

function displaySignatures($verified, $nuovagrafica) {
  global $disclaimerP7m, $disclaimerPdf;
  $htmlSignatures = "";
  $firme = $verified->getSignatures();
  foreach($firme as $indice => $certificato) {
    foreach($certificato as $soggetto => $dati) {
      switch($soggetto) {
        case "subject":
          $htmlSignatures .= $nuovagrafica?"<h4>File firmato da:</h4>":"<p>File firmato da:</p>";
        break;
        case "issuer":
          $htmlSignatures .= $nuovagrafica?"<h4>Firma digitale rilasciata da:</h4>":"<p>Firma digitale rilasciata da:</p>";
        break;
      }
      if(is_array($dati) && count($dati)>0) {
        $htmlSignatures .= $nuovagrafica?'<div class="form-group"><div class="row">':"<ul style='font-size: 90%;'>";
        $counter = 0;
        foreach($dati as $key => $value) {
          if(array_key_exists($key, $verified->sigleFirma[$soggetto])) {
            $counter++;
            $weirdCountryString = "{$firme[$soggetto]["C"]}:";
            if(strrpos($value, $weirdCountryString)>-1) {
              $value = str_replace($weirdCountryString, "", $value);
            }
            if($nuovagrafica) {
              $htmlSignatures .= '<label class="col-lg-2 col-md-2 col-sm-3 control-label-left">'.$verified->sigleFirma[$soggetto][$key].'</label>';
              $htmlSignatures .= '<div class="col-lg-2 col-md-2 col-sm-9">
                <p class="form-control-static">'.$value.'</p>
              </div>';
            } else {
              $htmlSignatures .= "<li><span style='text-decoration: underline;'>{$sigleFirma[$soggetto][$key]}:</span> {$value}<br></li>";
            }
            if($counter%3===0 && $nuovagrafica) {
              $htmlSignatures .= '</div></div><div class="form-group"><div class="row">';
            }
          }
        }
        $htmlSignatures .= $nuovagrafica?"</div></div>":"</ul>";
      }
    }
  }

  if ($_REQUEST['istanza']) $paramIstanza = "&istanza=1";
    
  if(strlen($htmlSignatures)>0) {
    if(!$nuovagrafica) { echo "<h2>Documento firmato digitalmente</h2>"; }
    if($verified->isP7m()) {
      echo $disclaimerP7m;
      
      displayDetails(
        array(
          "originale"=>array(
            "name"=>$verified->getFileName(),
            "hash"=>hash("md5", $verified->getFileContent()),
            "size"=>formatBytes($verified->getFileSize()),
            "download"=>"t=download&f={$_REQUEST['f']}{$paramIstanza}"
          ),
          "estratto"=>array(
            "name"=>$verified->getExtractedFileName(),
            "hash"=>hash("md5",  $verified->getExtractedFileContent()),
            "size"=>formatBytes($verified->getExtractedFileSize()),
            "download"=>"t=downloadExt&f={$_REQUEST['f']}{$paramIstanza}"
          )
        )
      );
    } else {
      echo $disclaimerPdf;
      
      displayDetails(
        array(
          "originale"=>array(
            "name"=>$verified->getFileName(),
            "hash"=>hash("md5", $verified->getFileContent()),
            "size"=>formatBytes($verified->getFileSize()),
            "download"=>"t=download&f={$_REQUEST['f']}"
          )
        )
      );
    }      
    if($nuovagrafica) {
      echo "<h3>Elenco dei firmatari:</h3>";
      echo "<div class='pill-content col-lg-12 col-md-12 col-sm-12 col-xs-12 panel panel-default'>";
      echo '<div id="firmatari" class="pill-pane panel-body">';
      echo '<div class="form-documento form-horizontal">';
      echo "{$htmlSignatures}";
      echo "</div>";
      echo "</div>";
      echo "</div>";
    } else {
      echo "<h3>Elenco dei firmatari:</h3>{$htmlSignatures}";
    }
  }
  else {
    $link = 'http://'.$_SERVER[SERVER_NAME].$_SERVER[REQUEST_URI].'&t=download'.$paramIstanza;
    header("Location: $link");
  }

}

if(isset($_GET["id"])) {  

  $structs = array(
    "delibere_b::comunicazioneDocumento" => array(
      "basePath" => "integrazioni", // directory principale dove si trovano i file
      "dirField" => "comunicazione.pratica.id", // campo del db che determina il nome della sottodirectory
      "filenameField" => "documento" // il campo del db che contiene il nome del file
    ), 
    "caricamento_pratiche::allegato" => array(
      "basePath" => $C['caricamento_pratiche']['percorso_allegati'],
      "dirField" => "pratica.id",
      "filenameField" => "descrizione",
      "parametri" => "&istanza=1"
    ), 
  );
  /*
  per esempio, caricamento_pratiche salva gli allegati in 
  $C['caricamento_pratiche']['percorso_allegati'].'/'.$idPratica."/".$filename
  quindi abbiamo bisogno di recuperare l'id pratica (dirField) e il filename (filenameField) per poter recuperare correttamente il file
  */
  $found = false;
  foreach($structs as $struct => $data) {
    if(isset($_REQUEST['f'])) {break;}
    else {
      $loader = & $IMP->getLoader($struct);
      $loader->addParam('link', $_GET["id"]);
      $loader->requestAll();
      $loader->request($data["dirField"]);
      $loader->request($data["filenameField"]);
      $file = $loader->load();
      if($file->get($data["filenameField"])) {
        $path = "{$data["basePath"]}/";
        if(isset($data["filenameField"]) && $data["filenameField"]!="") {
          $path = $file->get($data["dirField"])."/";
        }
        $_REQUEST['f'] = $path.$file->get($data["filenameField"]);
        if ($struct == 'caricamento_pratiche::allegato') $_REQUEST['istanza'] = 1;
      }
    }
  }
  
}

if ($_REQUEST['f']){
    $posizione = PATH_WEBDATA;
    if($_REQUEST['istanza']) { $posizione = DATA.'/caricamento_pratiche/'; }
    if ($_REQUEST['pdf']) {
        $nomePdf = basename($_REQUEST['f']);
        $path = VARPATH.'/docs/'.$nomePdf;
    } else { 
      $path = $posizione.'/'.$_REQUEST['f']; 
    }
    $path = str_replace('..', '', $path);
    if (file_exists($path)) {
      $verified = new VerifiedFile($path);
      if(!$C['portal']['noOpenSSL'] && $verified->isSigned() && (!isset($_REQUEST['t']))) {
        $intDir = VARPATH.'/docs';
        if (!is_dir($intDir)) { mkdir($intDir, 0777); }
        include_once(PATH_APP_PORTAL.'/portal_top_new.php');
        if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
        ?>
          <div id="portal_content" class="documento">
            <div id="navigatore_portale" class=""> </div>
            <div id="documento_layout">
              <div id="documento_index">
              
                <div class="titolo_pagina row">
                  <h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    Documento firmato digitalmente
                  </h3>
                </div>
                
                <div class="dettagli_documento">
                  <? displaySignatures($verified, true); ?>
                </div>

                <div class="bottoni_pagina">
                  <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mb15 mt15">
                      <div class="back">
                        <?
                          if ($_SERVER['HTTP_REFERER']){ 
                        ?>
                        <a class="btn" href='<?=$_SERVER['HTTP_REFERER']?>'>Indietro</a>
                        <?  }  else  { ?>
                        <a class="btn" href='#' onclick='javascript: window.history.back();'>Indietro</a>
                        <? } ?>
                      </div>
                    </div>
                  </div>
                </div>
              
              </div>
            </div>
          </div>
        <?
        }
        else {
        ?>
          <div id="pageBox">
              <div class="leftTopCorner"></div>
              <div class="rightTopCorner"></div> 
              <div class="boxSpacer">&nbsp;</div>
              <div class="boxContent">
                <?  
                displaySignatures($verified, false); 
                if ($_SERVER['HTTP_REFERER']){ ?>
                  <a class='highlight' href='<?=$_SERVER['HTTP_REFERER']?>'>Torna alla pratica</a>
                <?  }  ?>
              </div> 
              <div class="clear"></div>
              <div class="leftBottomCorner"></div>
              <div class="rightBottomCorner"></div> 
              <div class="boxSpacer">&nbsp;</div>   
          </div>
        <?
        }
        include_once(PATH_APP_PORTAL.'/portal_bottom_new.php');
      } // end if $verified->isSigned()
      else if(isset($_GET["info"])) {
        // mostro le informazioni sul file
        include_once(PATH_APP_PORTAL.'/portal_top_new.php');
        if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
        ?>
          <div id="portal_content" class="documento">
            <div id="navigatore_portale" class=""> </div>
            <div id="documento_layout">
              <div id="documento_index">
              
                <div class="titolo_pagina row">
                  <h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    Dettagli file
                  </h3>
                </div>
                
                <div class="dettagli_documento">
                  <? displayDetails(
                    array(
                      "originale"=>array(
                        "name"=>$verified->getFileName(),
                        "hash"=>hash("md5", $verified->getFileContent()),
                        "size"=>formatBytes($verified->getFileSize()),
                        "download"=>"t=download&f={$_REQUEST['f']}"
                      )
                    )
                  );
                  ?>
                </div>

                <div class="bottoni_pagina">
                  <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mb15 mt15">
                      <div class="back">
                        <?
                          if ($_SERVER['HTTP_REFERER']) { ?>
                            <a class="btn" href='<?=$_SERVER['HTTP_REFERER']?>'>Indietro</a>
                          <?  }  else  { ?>
                            <a class="btn" href='#' onclick='javascript: window.history.back();'>Indietro</a>
                          <? } ?>
                      </div>
                    </div>
                  </div>
                </div>
              
              </div>
            </div>
          </div>
        <?
        }
        else {
        ?>
          <div id="pageBox">
              <div class="leftTopCorner"></div>
              <div class="rightTopCorner"></div> 
              <div class="boxSpacer">&nbsp;</div>
              <div class="boxContent">
                
                <div class="dettagli_documento">
                  <? displayDetails(
                    array(
                      "originale"=>array(
                        "name"=>$verified->getFileName(),
                        "hash"=>hash("md5", $verified->getFileContent()),
                        "size"=>formatBytes($verified->getFileSize()),
                        "download"=>"t=download&f={$_REQUEST['f']}"
                      )
                    )
                  );
                  ?>
                </div>
                
                <a class='highlight' href='<?=$_SERVER['HTTP_REFERER']?>'>Torna alla pratica</a>
              </div> 
              <div class="clear"></div>
              <div class="leftBottomCorner"></div>
              <div class="rightBottomCorner"></div> 
              <div class="boxSpacer">&nbsp;</div>   
          </div>
        <?
        }
      
      } // end if(isset($_GET["info"])
      else {     
        if(isset($_REQUEST["t"]) && $_REQUEST["t"]=="downloadExt") {
          $verified->downloadExtracted();
        } else {
          $verified->download();
        }
      }   
    } // end if file_exists($path)
    else {
      userError('Siamo spiacenti, il file richiesto non &egrave; stato trovato.');
    }
} // end if $_REQUEST['f']
?>
