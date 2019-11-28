
try {
 
/* Google translate - patch gestione spostamento */
function googleTranslateElementInit_custom() {
  // Questa modifica serve per evitare problemi con IE ed Edge, che altrimenti si incastravano su un'eccezione e si bloccava il js di pagamenti
  try {
    var googleLayout = google.translate.TranslateElement.InlineLayout.SIMPLE;
    new google.translate.TranslateElement({pageLanguage: 'it', includedLanguages: 'ar,de,el,en,es,fr,ja,ru,zh-CN', layout: googleLayout}, 'google_translate_element');
  } catch(error) {
    console.log(error);
  }
}

function isIE() {
 var ua = window.navigator.userAgent;
 var msie = ua.indexOf("MSIE ");
 if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
 return true;
 } else {
 return false;
 }
 return false;
}

function isEDGE() {
 var ua = window.navigator.userAgent;
 
 var msEdge = ua.indexOf("Edge");
 if (msEdge > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
 	return true;
 } else {
 	return false;
 }
 return false;
}

//Questa itruzione è necessaria perchè sia Edge che IE non chiamano la callback dopo il caricamento degli script googole
if(isIE() || isEDGE()){
    googleTranslateElementInit_custom();
}

/* funzioni utility per stringhe */
function trim(str) {
    return str.replace(/^\s\s*/, "").replace(/\s\s*$/, "");
}

function isBlank(str) {
    return (!/\S/.test(str));
};

function cfIsValid(cf)
{
  var str = cf.replace(/\s/g,'').toUpperCase(); // converte in maiuscolo e rimuove spazi bianchi
  
  // verifica formato
  if(!/^[A-Z]{6}[\dLMNP-V]{2}[A-EHLMPRST][\dLMNP-V]{2}[A-Z][\dLMNP-V]{3}[A-Z]$/.test(str))
  {
    return false;
  }
  
  // verifica vocali/consonanti in nome/cognome
  var re = /^[^AEIOU]*[AEIOU]*X*$/;
  if(!re.test(str.substr(0,3)) || !re.test(str.substr(3,3)))
  {
    return false;
  }
  
  // verifica data
  /*  var day = str.substr(9,2);
  // TODO: gestire anni di nascita > 1999?
    var year = '19'+str.substr(6,2);
    var female = false;  
    if(day > 31){day = day -40; female = true;}
    var months = 'ABCDEHLMPRST';  
    var month = months.indexOf(str.charAt(8));
    var dateString = day+'/'+(month+1)+'/'+year; 
    if(!dateIsValid(dateString))
    {
        return false;
    }
    // verifica corrispondenza date nascita
    var cfDate = new Date(year, month, day, 0,0,0,0);
    var data = dataNascita.split('/');
    var birthDate = new Date(data[2], data[1]-1, data[0], 0, 0, 0, 0);
    if(cfDate.getTime() != birthDate.getTime())
    {
        return false;
    }
    // verifica corrispondenza sesso
    if(sesso == 'F' && female || sesso == 'M' && !female)
    {  }
    else
    {
        return false;
    }*/
    // verifica codice controllo (converte numeri in lettere e calcola checksum)
    str = str.replace(/\d/g, function(c) {return 'ABCDEFGHIJ'[c];});
    for(var s=str.charCodeAt(15),i=1;i<15;i+=2)
    {
        s -= str.charCodeAt(i);
    }
    for(i=0;i<15;i+=2) 
    {
        s -= 'BAFHJNPRTVCESULDGIMOQKWZYX'.charCodeAt(str.charCodeAt(i)-65);
    }
    return s%26 == 0;
}


/* funzioni jquery */

/* controllo della lunghezza del codice fiscale */

(function($) {
    $.fn.check_cf_length = function(){
        var cf_length = trim($(this).val()).length;
        if(cf_length == 16){
          console.log("Lunghezza cf if: "+cf_length);
          $("#form_registrazione-submit_registrazione").removeAttr('disabled');
          $('#form_registrazione-help_block_cf').css('color','green').text('Lunghezza corretta!');
        } else if(cf_length == 0){
          $("#form_registrazione-submit_registrazione").attr('disabled','disabled');
          $('#form_registrazione-help_block_cf').text('');
        } else {
          console.log("Lunghezza cf else: "+cf_length);
          $("#form_registrazione-submit_registrazione").attr('disabled','disabled');
          $('#form_registrazione-help_block_cf').css('color','red').text('Numero caratteri errato..');
        } 
    };

})(jQuery); 

var $allResponsiveTables;

/* gestione dell'accedi/registrati */
$(document).on('click','.cred_dimenticate', function(event){
    event.preventDefault();
    $('html, body').animate({
        scrollTop: ($("#credenziali_dimenticate").offset().top)-100
    }, 1000);
    $("#credenziali_dimenticate").empty();
    $(".alert").remove();
    $C.remote('password_dimenticata', {'mode' : 'embed'}, function(res){
            $("#credenziali_dimenticate").hide().append("<div class='response_password_dimenticata'>"+res+"</div>").fadeIn(150);
            $("#form_psw_dimenticata").submit(function(event) {
                event.preventDefault();
                /* chiudo gli alert di errore precedenti */
                $(".alert").remove();
                /* recupero username e cf inseriti */
                var username = trim($("#username_psw_dim").val());
                var cf = trim($("#cf_psw_dim").val());
                /* chiamo il metodo per recuperare la password */
                $C.remote('password_dimenticata', {'username_psw_dim' : username, 'cf_psw_dim' : cf }, function(res){
                    /* se errore mostro il messaggio di errore sopra il form del recupero psw */
                    if(res.esito == 'Errore'){
                        $("<div class='alert alert-danger'>"+res.esito+": "+res.messaggio_errore+" </div>").insertAfter($(".response_password_dimenticata legend")).fadeIn(300);
                    }
                    /* se successo chiudo form di errore e mostro messaggio sopra il form di login */
                    if(res.esito == 'Ok'){
                        /* riporto su la pagina */
                        $('html, body').animate({
                            scrollTop: ($("#login_form").offset().top)-300
                        }, 1000);
                        /*  */
                        $("#credenziali_dimenticate").empty();
                        $("<div class='alert alert-success'>"+res.messaggio_successo+" </div>").insertBefore($("#login_form")).fadeIn(300);
                    }
                });
            });
    },{dataType:'html'});

    $C.remote('username_dimenticato', {'mode' : 'embed'}, function(res){
            $("#credenziali_dimenticate").hide().append("<div class='response_username_dimenticato'>"+res+"</div>").fadeIn(150);
            $("#form_usr_dimenticato").submit(function(event) {
                event.preventDefault();
                /* chiudo gli alert di errore precedenti */
                $(".alert").remove();
                /* recupero username e cf inseriti */
                var email = trim($("#email_usr_dim").val());
                var cf = trim($("#cf_usr_dim").val());
                /* chiamo il metodo per recuperare la password */
                $C.remote('username_dimenticato', {'email_usr_dim' : email, 'cf_usr_dim' : cf }, function(res){
                    /* se errore mostro il messaggio di errore sopra il form del recupero psw */
                    if(res.esito == 'Errore'){
                        $("<div class='alert alert-danger'>"+res.esito+": "+res.messaggio_errore+" </div>").insertAfter($(".response_username_dimenticato legend")).fadeIn(300);
                    }
                    /* se successo chiudo form di errore e mostro messaggio sopra il form di login */
                    if(res.esito == 'Ok'){
                        /* riporto su la pagina */
                        $('html, body').animate({
                            scrollTop: ($("#login_form").offset().top)-300
                        }, 1000);
                        /*  */
                        $("#credenziali_dimenticate").empty();
                        $("<div class='alert alert-success'>"+res.messaggio_successo+" </div>").insertBefore($("#login_form")).fadeIn(300);
                    }
                });
            });
    },{dataType:'html'});
    
    

}); 




/* funzioni per menu nel top fisso */

var header = null,
    didScroll = null,
    changeHeaderOn = null;

/* scroll verticale che attiva testata scura dopo tot pixel */

function scrollY() {
    return window.pageYOffset || $(document).scrollTop;
}

var current_testo_ente_offset_top = 0;

/* attiva top scuro */
function dark_topbar(){
    if(header != null){
      if(!header.hasClass('header_fixed_top_shrink')){
          $("#portal_container").css({ "position": "relative", "margin-top":"100px" });
          $("#header_border_fixed").animate({
              height: "-=35",
              opacity: 0.80,
              "background-color": "#3D3D3D"
          }, 500);
          $("#testo_ente").animate({
            height: "-=35",
            "margin-top": "0px"
        }, 10);
      }
      header.addClass('header_fixed_top_shrink');
    }
};

/* attiva top chiaro */
function light_topbar(){
    if(header != null){
      if(header.hasClass('header_fixed_top_shrink')){
          $("#portal_container").css({ "position": "relative", "z-index": "auto" , "margin-top": "0px" });
          
          $("#header_border_fixed").animate({
              height: "+=35",
              opacity: 1,
              "background-color": "#fff"
          }, 500);
          $("#testo_ente").animate({
            height: "+=35",
            "margin-top": current_testo_ente_offset_top+"px"
        }, 10);
      }
      header.removeClass('header_fixed_top_shrink');
    }
};



function scrollPage() {
    if($(document).width() >= 768 ){
        var sy = scrollY();
        if (sy >= changeHeaderOn) {
            dark_topbar();
            $("#testo_ente").fitText(1.20, { minFontSize: '14px', maxFontSize: '40px' });
        }
        else {
            light_topbar();
            $("#testo_ente").fitText(1.20, { minFontSize: '14px', maxFontSize: '40px' });
        }
    }
    didScroll = false;
}

/* fine funzioni per menu nel top fisso */

/* se sono nella visuale tablet/smartphone mostro la topbar dark */
function activate_dark_topbar(width){
    if(width < 756){
        //$("#portal_container").css("margin-top","100px");
        dark_topbar();
        $("#testo_ente").fitText(1.20, { minFontSize: '14px', maxFontSize: '40px' });
    }else{
        //$("#portal_container").css("margin-top","0");
        light_topbar();
        $("#testo_ente").fitText(1.20, { minFontSize: '14px', maxFontSize: '40px' });
    }
}
    
/*global jQuery */
/*!
* FitText.js 1.2
*
* Copyright 2011, Dave Rupert http://daverupert.com
* Released under the WTFPL license
* http://sam.zoy.org/wtfpl/
*
* Date: Thu May 05 14:23:00 2011 -0600
*/

(function( $ ){

  $.fn.fitText = function( kompressor, options ) {

    // Setup options
    var compressor = kompressor || 1,
        settings = $.extend({
          'minFontSize' : Number.NEGATIVE_INFINITY,
          'maxFontSize' : Number.POSITIVE_INFINITY
        }, options);

    return this.each(function(){

      // Store the object
      var $this = $(this);

      // Resizer() resizes items based on the object width divided by the compressor * 10
      var resizer = function () {
        let font_size = Math.max(Math.min($this.width() / (compressor*10), parseFloat(settings.maxFontSize)), parseFloat(settings.minFontSize));
        $this.css('font-size', font_size );
        /* copio il testo ente in un div nascosto per prendere la width..ci saranno cmq problemi nel caso di parole lunghe!!! */
        $("body").append("<span id='copied_testo_ente' >"+$this.html()+"</span>");
        $("#copied_testo_ente").css('font-size', font_size );
        /* calcolo le larghezze  */
        let width_orig = ($("#copied_testo_ente").width());
        //console.log("width_orig: "+width_orig);
        $("#copied_testo_ente").remove();
        let width_current = $this.width();
        //console.log("width_current: "+width_current);
        //numero di linee in cui separare il nome ente
        let n_linee = Math.floor(width_orig / width_current );
        //console.log("n_linee: "+n_linee);
        let resto = (width_orig % width_current );
        //se ho pixel di resto aggiungo una linea
        if(resto > 0){
          n_linee++;
        }
        //console.log("n_linee finali: "+n_linee);
        let line_height_finale = (($this.height()/n_linee)-4)+"px";
        //console.log("line height: "+line_height_finale);
        $this.css('line-height', line_height_finale);
      };

      // Call once to set.
      resizer();

      // Call on resize. Opera debounces their resize by default.
      $(window).on('resize.fittext orientationchange.fittext', resizer);

    });

  };

})( jQuery );

function nascondi_cf(){
    var stato_res = $('#form_registrazione-stato_residenza').val();
    if (!isBlank(stato_res) && stato_res.toUpperCase() != 'IT') {
      //console.log("Stato di residenza iniziale: "+stato_res_iniziale);
      $('#form_registrazione-codice_fiscale').parent().parent().addClass('hide');
      $('#form_registrazione-help_block_cf').addClass('hide');
      $("#form_registrazione-submit_registrazione").removeAttr('disabled');
    }else{
      $('#form_registrazione-codice_fiscale').parent().parent().removeClass('hide');
      $("#form_registrazione-submit_registrazione").attr('disabled','disabled');
    }
}

$(document).ready(function(){

    $("#menu_responsive_laterale").removeClass("not_display");

    /* menu in alto fixed quando scroll */
    if($('#menu_top_fixed').length == 1){
    /* menu in alto fixed */
      header = $('#portal_top');
      didScroll = false;
      changeHeaderOn = 50;

      window.addEventListener('scroll',function(event) {
          if(!didScroll) {
              didScroll = true;
              scrollPage();
          }
      }, false );
    };
    /* fine menu in alto fixed */
    var width_portal_container = $(document).width();
    activate_dark_topbar(width_portal_container);
    $(window).resize(function() {
        /* gestione dimensione header al ridimensionamento */
        var width_portal_container = $(window).width();
        /* gestione barra scura al ridimensionamento */
        activate_dark_topbar(width_portal_container);
    });

    if($("#form_registrazione-codice_fiscale").length == 1) {
        /* ho dovuto impostare il cf non obbligatorio, rimetto da js l'asteriscco per obbligatorieta */
        let valore_label = $(".codice_fiscale_element .control-label").text().trim();
        $(".codice_fiscale_element .control-label").text(valore_label+" *");
        //disabilito il submit
        var initial_cf_length = $('#form_registrazione-codice_fiscale').val().length;
        if (initial_cf_length != 16) {
          $("#form_registrazione-submit_registrazione").attr('disabled','disabled');
        };
          // var cf = $("#form_registrazione-codice_fiscale").val();
          // if(cf!=""){
          //     alert(cfIsValid(cf));
          // }
        if( !isBlank($('#form_registrazione-codice_fiscale').val())){
          $('#form_registrazione-help_block_cf').removeClass('hide');
          $('#form_registrazione-codice_fiscale').check_cf_length();
        }
        $('#form_registrazione-codice_fiscale').keyup(function(){
          $('#form_registrazione-help_block_cf').removeClass('hide');
          $(this).check_cf_length();
        }).change(function(){
          $('#form_registrazione-help_block_cf').removeClass('hide');
          $(this).check_cf_length();
        })    
    }

    /* Controllo nazione Residenza, se diversa da it nascondo il codice fiscale */
    if($("#form_registrazione-stato_residenza").length == 1) {
        nascondi_cf();
        $('#form_registrazione-stato_residenza').keyup(function(){
          nascondi_cf();
        }).change(function(){
          nascondi_cf();
        })    
    }    




    if($("#tabs_dossier_cittadino").length == 1){
        $("#tabs_dossier_cittadino").tabs();
    }

    var modal_options = {
        keyboard: false
    };    

    if($(".trattamento_dati").length == 1){
        $(".trattamento_dati").click(function(event){
            event.preventDefault();
            $C.remote('informativa_dati_personali', function(res){
                $("#myModal .modal-body").html(res);
            }, {dataType: 'html'});
            $("#myModal").modal(modal_options);
        });
    }

    if($(".privacy").length == 1){
        $(".privacy").click(function(event){
            event.preventDefault();
            $C.remote('informativa_privacy', function(res){
                $("#myModal .modal-body").html(res);
            }, {dataType: 'html'});
            $("#myModal").modal(modal_options);
        });
    }  

    /* gestione del link per tornare alla cima della pagina  */
    if($(".back-to-top").length > 0) {
        var offset = 220;
        var duration = 500;
        $(window).scroll(function() {
            if (jQuery(this).scrollTop() > offset) {
                jQuery('.back-to-top').fadeIn(duration);
            } else {
                jQuery('.back-to-top').fadeOut(duration);
            }
        });
        
        $('.back-to-top').click(function(event) {
            event.preventDefault();
            jQuery('html, body').animate({scrollTop: 0}, duration);
            return false;
        })
    };

    if($("#menu_responsive_laterale").length == 1){
        /* gestione del menu laterale responsive */
        $("#menu_responsive_laterale").mmenu({
          //offCanvas: true,
          /* estensioni usate */
          extensions: ["effect-menu-slide", "effect-listitems-slide", "pagedim-black", "pageshadow", "panelshadow"]
          }
        );
        var menu_laterale = $("#menu_responsive_laterale").data("mmenu");

        $("#my-button").click(function(event) {
          event.preventDefault();
          /* porto il bottone per la traduzione nel menu responsive se presente */
          $("#google_translate_element_resp").append($("#google_translate_element"));
          menu_laterale.open();
        });

        menu_laterale.bind('closed', function () {
          $("#sub_bar_header").append($("#google_translate_element"));
        });

    }
    

    /* gestione del link su tutta la riga della tabella */
    $('.row_linked tbody tr').click( function() {
        if($(this).find('a').length > 0){
            window.location = $(this).find('a').attr('href');
        }
    }).hover( function() {
        $(this).toggleClass('hover');
    });

    if($("#cerca_per_tabella").length > 0 && $("table").length > 0){
        var duration = 500;
        var margine_da_top_del_cerca = $("#cerca_per_tabella").offset().top;
        var margine_superiore = 230;
        jQuery('html, body').animate({scrollTop: margine_da_top_del_cerca-margine_superiore}, duration);
    }

    /* Controllo il provider dell'utente loggato, auth esterna */
    
    var provider_utente_loggato = $("#provider_utente").text().trim();
    if(!isBlank(provider_utente_loggato) && provider_utente_loggato.toUpperCase == 'SPID'){
        /* se sono nella pagina per completare i dati provenienti da spid metto readonly certi campi e mostro alert */
        if($("#blocca_campi").length > 0){
          /* disattivo date con calendario su data nascita e documento */
          $("#form_registrazione-data_documento").off();
          $("#form_registrazione-data_nascita").off();

          $("#form_registrazione-nome").attr('readonly','readonly');
          $("#form_registrazione-cognome").attr('readonly','readonly');
          $("#form_registrazione-codice_fiscale").attr('readonly','readonly');
          $("#form_registrazione-sesso").attr('disabled','disabled');
          $("#form_registrazione-provincia_nascita").attr('readonly','readonly');
          $("#form_registrazione-data_nascita").attr('readonly','readonly');
          if(!!$("#form_registrazione-comune_nascita").val()){ $("#form_registrazione-comune_nascita").attr('readonly','readonly');}
          // Tolto dopo aver aggiunto lista campiche arrivano da auth esterne
          //$(".form-actions").prepend("<div class='alert alert-warning'>Attenzione!<br />I dati di contatto (email e cellulare) vengono recuperati dal tuo profilo SPID ma potranno essere gestiti direttamente da questo portale.</div>");
      
          if($("#doc_da_spid").length == 1){
            $("#form_registrazione-tipo_documento").attr('disabled','disabled'); /* la select la devo proprio bloccare */
            $("#form_registrazione-numero_documento").attr('readonly','readonly');
            $("#form_registrazione-data_documento").attr('readonly','readonly');
            $("#form_registrazione-documento_rilasciato").attr('readonly','readonly');
          }

          $("#form_registrazione").submit(function(e){
              $("#form_registrazione-sesso").attr('readonly','readonly');
              $("#form_registrazione-sesso").removeAttr('disabled','disabled');
              $("#form_registrazione-tipo_documento").attr('readonly','readonly');
              $("#form_registrazione-tipo_documento").removeAttr('disabled','disabled');
          })
        }
    }else if(!isBlank(provider_utente_loggato) && provider_utente_loggato.toUpperCase() == 'EIDAS'){
        //arriva ["spidCode", "name", "familyName", "gender", "dateOfBirth", "placeOfBirth", "address"]
        var array_attributi_eidas = JSON.parse($("#attributi_eidas").text());

        $(array_attributi_eidas).each(function(){
          let element_val = $("#form_registrazione-"+this).val();
          if( !isBlank(element_val) ){
            $("#form_registrazione-"+this).attr('readonly','readonly');
          }
        });
        
    }

    

    /* uso un flag nascosto sul form di registrazione per gestire l'autorizzazione delle comunicazioni, così crea widget */
    if($("#form_registrazione-disabilita_comunicazioni").length > 0){
        /* al caricamento, se check Autorizzo all' utilizzo dei miei dati.. ceccato -> uncheck disabilita comunicazioni */
        if($("#form_registrazione-autorizza_comunicazioni").is(":checked")){
            $("#form_registrazione-disabilita_comunicazioni").removeAttr('checked')
        }else{
            $("#form_registrazione-disabilita_comunicazioni").attr('checked','checked')
        }
        $("#form_registrazione-autorizza_comunicazioni").on('click',function(){
            if($("#form_registrazione-autorizza_comunicazioni").is(":checked")){
                $("#form_registrazione-disabilita_comunicazioni").removeAttr('checked')
            }else{
                $("#form_registrazione-disabilita_comunicazioni").attr('checked','checked')
            }
        })
    }
    /* Evito che clicchino piu' volte sul tasto della widget di registrazione */
    if( $("#form_registrazione-submit_registrazione").length > 0 ){
        $("#form_registrazione-submit_registrazione").click(function(event){
            $("#myModal .modal-body").html("<h1><i class='fa fa-spinner fa-spin fa-fw'> </i> Caricamento...</h1>");
            $("#myModal").removeClass('hide');
            $('#myModal').modal({
                keyboard: false,
                backdrop: "static"
            })
        })     
    }
    // trasformo tutte le tabelle in tabelle responsive    
    $allResponsiveTables = $( "table.table-responsive" ); // salvo qui le tabelle della pagina per poter recuperare l'indice durante la conversione
    $allResponsiveTables.each(function() {
      // ma solo se c'è la paginazione mobile, se non c'è vuol dire che spider non è aggiornato
      if(!$(this).hasClass('no-responsive')) {
        tableToUl($(this));
      }
    });
    
    /* mostra loader con modal al click su un link */
    $("a").not("[href^='#']").not("[href*='_w[']").not("[class*='nomodal']").on('click',function(){
        $("#myModal .modal-body").html("<h4><i class='fa fa-spinner fa-spin fa-fw'> </i> Caricamento...</h4>");
        $("#myModal").removeClass('hide');
        $('#myModal').modal({
          keyboard: false,
          backdrop: "static"
        })
    });

    current_testo_ente_offset_top = $("#testo_ente").offset().top;
    /* Nome dell'ente con plugin fittext  */
    $("#testo_ente").fitText(1.20, { minFontSize: '14px', maxFontSize: '40px' });
    //$("#testo_ente").textfill({maxFontPixels: 40, minFontSize: 10 });
}); /* chiudo $(document).ready */

// chiudo modal aperti prima di cambiare pagina in modo che non restino lì a bloccare tutto se l'utente usa il tasto indietro del browser
$( window ).unload(function() {
  $('#myModal').modal('hide');
});

/* utilità per conversione tabelle a responsive */

// i testi della tabella che superano questo limite verranno considerati testi lunghi.
var caratteriTestoBreve = 40;

function rowsToLi($tbody, $ul, columns, balanceColumns) {
  if(typeof(balanceColumns)=="undefined") { balanceColumns = false; }
  var largestCol = false;
  var hasLongTexts = false;
  var widecells = 0;
  var columnsSizes = [];
  for(var i in columns) { columnsSizes.push(0); }
  $tbody.find('tr').each(function(){
    var $li = $('<li>');
    $li.attr("id",$(this).attr("id"));
    $li.addClass($(this).attr("class"));
    if($(this).get(0).style.display) {
        $li.css('display',$(this).css('display'));
    }    
    var counter = 0;
    var longtexts = 0;
    widecells = $(this).find(".cell-wide").length;
    $(this).find("td").each(function(){
      var content = $(this).text().trim();
      var $link = $(this).find("a");
      var label = typeof(columns[counter])!="undefined"?"<label>"+columns[counter]+"</label>":"";
      var $div = $('<div>'+label+$(this).html().trim()+'</div>');
      if($(this).html().trim().length <1) { $div.addClass("empty"); }
      if($div.find("label").html()==""){ $div.find("label").addClass("empty");}
      $div.find("label").html($div.find("label").html()+":");
      $div.attr("id",$(this).attr("id"));
      $div.addClass($(this).attr("class"));
      if($div.hasClass("cell-wide")) {
        $div.addClass("cell-wide-"+widecells);
      }
      if(counter==columns.length-1) {
//         $div.css('width',"90vw"); // senza una larghezza impostata sull'ultima colonna le larghezze in percentuale non hanno effetto
        $div.addClass('widthfixer');
      }
      if($(this).get(0).style.minWidth) {
        $div.css('min-width',$(this).css('min-width'));
      }
      
      if($(this).attr("align")=="center" || $(this).css('text-align') == 'center') {
        $div.addClass("cell-center");
      } else if($(this).attr("align")=="right" || $(this).css('text-align') == 'right') {
        $div.addClass("cell-right");
      }
      
      if(columnsSizes[counter]<content.length) {
        columnsSizes[counter] = content.length;
      }
      
      if( !largestCol || largestCol.content.length<content.length) {
        largestCol = {"colIndex":counter, "colName":columns[counter], "content": content};
      }
      if(content.length>caratteriTestoBreve) {
        longtexts++;
      }
      $li.append($div);
      counter++;
    });
    if(longtexts>0) { hasLongTexts = true; }
    
    if($ul.hasClass("row_linked")) {
      $ul.on('tap', 'tr, li', function(){
        if(typeof($(this).find('a').attr('href'))!="undefined") {
          console.log("TAP Going to "+$(this).find('a').first().attr('href'));
          window.location = $(this).find('a').first().attr('href');
        }
      });
      $ul.on('click', 'tr, li', function(){
        if(typeof($(this).find('a').attr('href'))!="undefined") {
          console.log("TAP Going to "+$(this).find('a').first().attr('href'));
          window.location = $(this).find('a').first().attr('href');
        } else {
          console.log("no href");
        }
      });
    }
    $li.appendTo($ul);
    $(this).remove();
  });
  if(widecells>0) { $ul.addClass("no-filler"); }
  if(hasLongTexts) {largestCol.wrapTable = true;}
  
  if(hasLongTexts && balanceColumns) {
    // è presente almeno una colonna molto larga, effettuo bilanciamento automatico in base ai contenuti
    $ul.find("div").removeClass("cell-wide-"+widecells).removeClass("cell-wide");
  
    var totalSize = columnsSizes.reduce(function(a, b) { return a + b; }, 0);
    
    for(var columnIndex in columnsSizes) {
      var bestWidth = columnsSizes[columnIndex]*100/totalSize;
      if (bestWidth == 0) { bestWidth = 1; }
      for(var n = 1; n < 10; n++) {
        if(bestWidth<=100/n && bestWidth>100/(n+1)) {
          $ul.find("li div:nth-child("+(parseInt(columnIndex)+1)+")").addClass("cell-wide-"+n);
        }
      }
    }
    
  }
  return largestCol;
}

function setCellWidths(largestCol, columns, $ul) {
  if(!$ul.hasClass("no-filler")) {
    if (largestCol) {
      $ul.find("li div:nth-child("+(largestCol.colIndex+1)+")").addClass("cell-wide-1");
    }
  }
  
  if(columns.length>8 && !$ul.hasClass("wrap-header")) {
    $ul.parent().addClass("table-big");
  } else if(columns.length>4) {
    $ul.parent().addClass("table-medium");
  }
  if(largestCol.wrapTable) { $ul.addClass("table-wrap"); }
}

function tableToUl($table) {
  if($table.is("table")) {
    var $ul = $('<ul>');
    var empty = $table.find("td").length<1;
    var balanceColumns = false;
    $ul.addClass($table.attr("class"));
    if($table.find("[class^=cell-wide]").length==0) {
      // se non ho impostato nessuna larghezza colonna, bilancio automaticamente la larghezza di tutte le colonne
      $table.find("td").addClass("cell-wide");
      balanceColumns = true;
    } else {
      // ci sono delle larghezze colonna impostate nell'html, impedisco che ne vengano aggiunte automaticamente
      $ul.addClass("no-filler");
    }
    $ul.attr("id",$table.attr("id"));
    if($ul.attr("id")=="" || typeof($ul.attr("id"))=="undefined") {
      $ul.attr("id", $table.find("tbody").attr("id"));
    }
    if($ul.attr("id")=="" || typeof($ul.attr("id"))=="undefined") {
      var index = -1;
      for(var i = 0; i<$allResponsiveTables.length; i++) {
        if($table.is($allResponsiveTables[i])) {
          index = i;
        }
      }
      if(index>-1) {
        $ul.attr("id","table_"+index);
      }
    }
    var columns = [];
    var htmlColumns = [];
    var $allTableHeaders = $table.find("tr:has(th)");
    $allTableHeaders.each(function(){
      var $li = $('<li>');
      if($(this).find("th").length==1) {
        // titolo tabella
        $li.addClass("table-caption");
        $li.html($(this).find("th").html());
      } else {
        // nomi colonne
        $li.addClass("table-header");
        $li.addClass($(this).attr("class"));
        $(this).find("th").each(function(){
          var $div = $('<div>'+$(this).html()+'</div>');
          columns.push($(this).text());
          var $link = $(this).find("a");
          if($link.length>0){
            htmlColumns.push($(this).html());
            $link.addClass("btn btn-default");
            $table.parent().find(".mobile-sorting").append($link);
          }
          $li.append($div);
        });
        if(empty) {
          // se la tabella è vuota aggiusto le larghezze della header perchè riempia tutto lo spazio orizzontale
          $li.find("div").css("width", ((100/columns.length)*1.1)+"%");
        }
      }
      $li.appendTo($ul);
    });
    
    $ul.insertBefore($table);
    var $tbody = $table.find("tbody");
    $table.find("tfoot tr").each(function(){$(this).appendTo($tbody);});
    if(!empty) {
      var largestCol = rowsToLi($tbody, $ul, columns, balanceColumns);
      setCellWidths(largestCol, columns, $ul);
    }
    
    if($ul.find("li:not(.table-header)").find("a").length<1) { 
      $ul.removeClass("row_linked");     
    }
    
    if($(".page_navigation.pagination.pagination-desktop").length<1) {
      // convertiamo il paginatore js
      $(".page_navigation.pagination").addClass("pagination-desktop");
      $(".sel_pagine").addClass("sel_pagine-desktop");
      var $loadMore = $('<a class="btn btn-default btn-block">Carica altri</a>');
      $loadMore.click(function(){
        $(this).hide();
        $(this).parent().append('<i class=\"fa fa-circle-o-notch fa-spin fa-3x fa-fw muted\"></i><span class=\"sr-only\">Caricamento...</span>');
        var $container = $(this).parent().parent();
        var $hiddenRows = $container.find('.pagination_content .paginated_element:hidden');
        var show_per_page = parseInt($container.find("#items_per_page").val());
        
        $(this).parent().find(".fa-spin").remove();
        $hiddenRows.slice(0, show_per_page).show();
        var $hiddenRows = $container.find('.pagination_content .paginated_element:hidden');
        if($hiddenRows.length>0) {
          $(this).show();
        }
      });
      if($ul.find('.paginated_element:hidden').length>0) {
        if($(".pagination-mobile-loadmore").length<1) {
          $('<div class="pagination-mobile pagination-mobile-loadmore"></div>').insertAfter($ul);
        }
        $(".pagination-mobile-loadmore").append($loadMore);
      }
    }    
    
    if($table.is("table")){$table.remove();}
  }
}


} //chiusura del try all'inizio

catch(err) {
  setTimeout(function() { alert("Si sono verificati problemi tecnici, l'amministratore è stato informato. Ricaricare la pagina tra qualche minuto."); }, 5);
  $C.remote('js_recompile', {'app': "portal"}, function(res){
      console.log(res);
  });
}
