var user_token;

function check_tipo_comunicazione(){
	/* quando carico la pagina della scelta destinatari controllo se voglio quelli pubblici o privati */
    /* di default nascondo il div per la comunicazione privata */
    $(".scelta_destinatari_privati").hide();
	var tipo_comunicazione = $("input[name='scelta_tipo_comunicazione']:checked").val();
	if(tipo_comunicazione!= null && tipo_comunicazione == "privata"){
		$('.scelta_destinatari_privati').show();
        $('.scelta_gruppi_pubblica').hide();
	}
    if(tipo_comunicazione!= null && tipo_comunicazione == "pubblica"){
        $('.scelta_destinatari_privati').hide();
        $('.scelta_gruppi_pubblica').show();
    }
};

function checkbox_esclusivi(checkbox_cliccato, evento){
    $("input[name='"+checkbox_cliccato.name+"']").each(function(index) {
        if($(this).val() != checkbox_cliccato.value ){
            /* se evento è un click abilito o disabilito, se ready non faccio niente */
            if(evento == 'click'){
                if(($(this).attr("disabled") == true) || ($(this).attr("disabled") == 'disabled')){
                    $(this).removeAttr("disabled");
                }else{
                        $(this).attr("disabled", "disabled");
                }
            }else{
                    $(this).attr("disabled", "disabled");
            }    
        }
    });
};

// funzione per pubblicare automaticamente su facebook al caricamento di conferma_invio
function pubblicazione_automatica() {
  var titolo_com = $("#titolo").text().trim()+": ";
  var testo_breve = $("#testo_breve").text().trim();
  var testo_com = $("#testo").text().trim();
  var img_com = $("#img_comunicazione").length? $("#img_comunicazione").attr("src"):null;
  if(img_com != null){
      //invio anche l'immagine
      var img_src = $("#img_comunicazione").attr('src')
      post_to_page(titolo_com, testo_breve, testo_com, img_src);
  }
  else{
      //invio senza immagine
      post_to_page(titolo_com, testo_breve, testo_com, "http://localhost/noimage");
  }
};


// modificato per nuovo sistema autenticazione facebook
function check_facebook_status(action, pubblica){
  if(typeof(pubblica)=="undefined") {pubblica=false;}
  FB.getLoginStatus(function(response) {
      if (response.status === 'connected') {
          //alert('connesso');
          // connected
          user_token = response.authResponse.accessToken;
          $("#facebook_link").show();
          $("#facebook").removeAttr('disabled');
          $("#facebook_status").html("Connesso a Facebook").parent().addClass("fb_active").removeClass("fb_non_active");
          FB.api('/me', function(response) {
              nome_utente_fb = response.name;
              user_id = response.id;
              if ( $(".logo_facebook").length == 0 ){
                  $("#admin_controls").append("<p class='logo_facebook'>Ciao "+nome_utente_fb+" - <span id='logout_link'>Logout</span></p>" );
              }
              $("#app_buttons").addClass('aggiusta_icone_app');
              $("#header").css("height","120px");  
              if(pubblica) { pubblicazione_automatica();}
          });
      } else if (response.status === 'not_authorized') {
          // not_authorized
          $("#facebook").attr('disabled', 'disabled');
          $("#facebook_status").html("Connetti a Facebook").parent().addClass("fb_non_active").removeClass("fb_active");
          $("#facebook_link").show();
          if(action == "forza_login"){
              login();
          }
      } else {
          //alert('non loggato');
          // not_logged_in
          $("#facebook").attr('disabled', 'disabled');
          $("#facebook_status").html("Connetti a Facebook").parent().addClass("fb_non_active").removeClass("fb_active");
          $("#facebook_link").show();
          $(".logo_facebook").remove();
          if(action == "forza_login"){
              login();
          }
      }
  });
};

// modificato per nuovo sistema autenticazione facebook
function post_to_page(titolo,testo_breve,testo,src_immagine){
  var page_id = $("#fb_page_id").val();
  var comunicazione_id = $("#id_comunicazione").val();
  var link_news = document.location['origin'].replace("https","http")+'/portal/servizi/comunicazioni/'+comunicazione_id+'/pubblica';
  
  // recupero l'access token della pagina, utilizzando l'user token per l'autenticazione
  // se l'utente non è amministratore della pagina, verrà restituito un errore
  FB.api(page_id, 'get', { 
    fields: 'access_token',
    access_token: user_token
  }, 
  function(response) {
      console.log('GET access token Response: ' + JSON.stringify(response));
      if (!response || response.error) {
        if(response.error.code == 104) {  
          alert("Per poter pubblicare è necessario accedere a facebook.");
          $('#modal_window').modal("hide");
          $("#errore_pubblicazione_fb").show();
        } else if(response.error.code == 10) {
          alert("Non sei abilitato alla pubblicazione sulla pagina. Verifica di essere amministratore della pagina facebook e riprova, o effettua il login con un altro account.");
          $('#modal_window').modal("hide");
          $("#errore_pubblicazione_fb").show();
        } else {
          alert("Si è verificato un errore, si prega di contattare l'amministratore.");
          $('#modal_window').modal("hide");
          $("#errore_pubblicazione_fb").show();
          console.log(JSON.stringify(response.error));
        }
      } else {
        pageAccessToken = response.access_token;
        // devo forzare lo scraping della pagina per rilevare correttamente la tag fb:page
        FB.api('https://graph.facebook.com/', 'post', {
          id: link_news,
          scrape: true
        }, function(response) {
          console.log('POST scrape Response: ' + JSON.stringify(response));
          if (!response || response.error) {
            alert("Problemi di pubblicazione su Facebook, contattare l'amministratore.");
            $('#modal_window').modal("hide");
            $("#errore_pubblicazione_fb").show();
          } else {
            var request = { 
              // for the newer versions of the Facebook API you need to add the access token
              access_token: pageAccessToken,
              link: link_news,
              message: testo_breve, // compare come testo del post
              // questi ultimi tre parametri si possono specificare solo se la pagina esiste già e contiene la meta tag fb:page 
              // anche in questo caso, l'url dell'immagine verrà ignorato: è possibile modificare solamente name e description rispetto a quelli specificati con le tag og:title (name) e og:description (description) all'interno della pagina web, ma l'immagine sarà sempre e solo quella specificata nella tag og:image 
//               name: titolo, // compare come titolo del link
//               description: testo, // compare come testo del link
//               picture: src_immagine, // url dell'immagine
//               full_picture: src_immagine, // url dell'immagine
            };
            console.log('Request: ' + JSON.stringify(request));
            
            // pubblico sulla pagina utilizzando il token di accesso alla pagina ottenuto nella precedente richiesta
            FB.api('/'+page_id+'/feed', 
            'post', 
            request, 
            function(response) {
              console.log('POST feed Response: ' + JSON.stringify(response));
              if (!response || response.error) {
                alert("Problemi di pubblicazione su Facebook, contattare l'amministratore.");
                $('#modal_window').modal("hide");
                $("#errore_pubblicazione_fb").show();
                if(response.error.code == 200) {
                  console.log("Errore token di autenticazione");
                } else {
                  console.log(JSON.stringify(response.error));
                }
              } else {
                // pubblicato con successo
                $('#modal_window').modal("hide");
                $("#attendi_pubblicazione_fb").show();
                console.log("Post pubblicato! https://www.facebook.com/"+response.id);
              }
            });
          }
        });                
      }
  });
};

/* bisogna passare lo scope alla login per consentire all'applicazione di pubblicare sulle pagine
    https://developers.facebook.com/docs/reference/javascript/FB.login/#permissions
    https://developers.facebook.com/docs/reference/login/extended-permissions/
 */
  function login() {
      FB.login(function(response) {
          check_facebook_status();
      //}, {scope: 'publish_actions,manage_pages,read_stream'});
      }, {auth_type: 'reauthorize', scope: 'manage_pages,publish_pages'});
      // aggiunto auth_type: 'reauthorize' (vedi  https://developers.facebook.com/docs/facebook-login/auth-vs-data/#data-access-expiration)
  };

/* faccio il logout da facebook e richiamo il check_facebook_status per settare i vari messaggi in pagina */
function logout() {
    FB.logout(function(response) {
        //FB.Auth.setAuthResponse(null, 'unknown');
        // Person is now logged out
        check_facebook_status();
    });
};

// /* pubblica sull'account facebook in cui si è fatto il login la notizia */
// function postToFeed(titolo, testo) {
//     var picture_file_path = "http://"+window.location.hostname+"/img/logo_facebook.png";
//     var obj = {
//         method: 'feed',
//         display: 'popup',
//         link: '',
//         picture: picture_file_path,
//         name: titolo,
//         description: testo
//     };

//     FB.ui(obj, function(response) {
//         if (response && response.post_id) {
//             /* se è stato pubblicato faccio il post del form */
//             $("#invia_form").click();
//         } else {
//           alert('Per non pubblicare il post su Facebbok deselezionare il relativo canale di comunicazione.');
//         }
//     });
    
// }

(function($) {
    $.fn.check_input_length = function(){
        var input_length = $("#testo_breve_comunicazione").val().length;
        var car_mancanti = (300 - input_length);
        if(input_length >= 300){
            $('#errore_numero_caratteri').css('color','red').text('N° massimo caratteri: 300');
        } else {
            $('#errore_numero_caratteri').css('color','green').text('Caratteri mancanti:'+car_mancanti);
        } 
    };
})(jQuery); 




$(document).on('click','.link_cancellazione', function(event){
    var risposta = confirm("Confermi di cancellare l'utente?");
        if (risposta == true) {
            var id_utente = $(this).attr('utente-id');
            var id_comunicazione = $("#campi_comunicazione #id_comunicazione").text();
                $C.remote('mostra_utenti', {'comunicazione_id': id_comunicazione, 'id_utente_da_cancellare': id_utente}, function(res){
                        $("#modal_risultati .modal-body").replaceWith("<div class='modal-body'>"+res+"</div>");
                        /* carico il paginatore per l'elenco lungo */
                        init_paginatore();
                }, {dataType: 'html'});
        } else {
            //chiude la window confirm e non fa niente
        }
}); 

$(document).ready(function(){
    if( $("#attendi_pubblicazione_fb").length > 0 ){
      // ho pubblicato su portale e ora devo pubblicare su facebook 
      $("#attendi_pubblicazione_fb").hide();
      $("#errore_pubblicazione_fb").hide();
      $('#modal_window').modal({
          keyboard: false,
          backdrop: "static"
      });
    }
    
    if( $(".send_comunicazione").length > 0 ){
        $(".send_comunicazione").click(function(event){
            $('#modal_window').modal({
                keyboard: false,
                backdrop: "static"
            })
        })     
    }
    
    var fb_dati_utente = null;
    /* se ho il tag con id facebook_link nel menu a sinistra facebook è abilitato */
    if ( $("#facebook_link").length > 0 ){
        $("#facebook").attr('disabled', 'disabled');
        $("#facebook_link").hide();
        /* carico la sdk di facebook in modo asincrono con jquery */
        $.ajaxSetup({ cache: true });
        /* carico l'app id dell'applicazione fatta per il comune */
        var app_id = $("#fb_app_id").val();
        //$.getScript('//connect.facebook.net/en_US/all.js', function(){
        $.getScript('/comunicazioni/public/js/facebook_all.min.js', function(){
          
            FB.init({
                appId: app_id,
                status  : true,
                cookie  : true,
                oauth   : true
            });
            // $("#facebook_link").show();
            // $("#facebook").removeAttr('disabled');
            /* setto i vari messaggi */
            
            if( $("#attendi_pubblicazione_fb").length > 0 ){
              check_facebook_status("forza_login",true);
            } else {
              check_facebook_status();
            }
            /*
            $("#facebook,#facebook_link.fb_non_active").on("click", function(){
                check_facebook_status("forza_login");
                //login();
            });
            */
            $(document).on('click','#facebook_link.fb_non_active', function(event){
                check_facebook_status("forza_login");
                //login();
            });
            $(document).on('click','.logo_facebook', function(event){
                logout();
            });
           
            /* quando clicco su invia pubblico tramite facebook */
            /* quando clicco su 'invia' faccio comparire il popup per pubblicare su facebook */
            $(document).on('click','#button_facebook', function(event){
                event.preventDefault();
                check_facebook_status("forza_login");
                var titolo_com = $("#campi_comunicazione #titolo").text().trim()+": ";
                var testo_breve = $("#testo_breve").text().trim();
                var testo_com = $("#campi_comunicazione #testo").text().trim();
                var img_com = $("#img_comunicazione");
                //VECCHIO METODO PER ESTRARRE L'IMMAGINE
                /*var html_com = $("#campi_comunicazione #testo").html().trim();
                var tmp_div = document.createElement('div');
                tmp_div.setAttribute("id", "tmp_div");
                tmp_div.innerHTML = html_com;
                var srcs = [];
                srcs = Array.prototype.slice.call(tmp_div.querySelectorAll('[src]'),0);
                if(srcs.length>0){
                    var img_src = srcs[0].src;
                */
                if(img_com != null){
                    //invio anche l'immagine
                    var img_src = $("#img_comunicazione").attr('src')
                    console.log("Pubblico immagine"+img_src);
                    post_to_page(titolo_com, testo_breve, testo_com, img_src);
                }
                else{
                    //invio senza immagine
                    console.log("Pubblico senza immagine");
                    post_to_page(titolo_com, testo_breve, testo_com, "http://localhost/noimage");
                }
                

                

            });
            
            // $("#form_pubblicazione").submit(function( event ) {
            //     check_facebook_status("forza_login");
            //     var titolo_com = $("#campi_comunicazione #titolo").text();
            //     var testo_com = $("#campi_comunicazione #testo").text();
            //     post_to_page(titolo_com, testo_com);
            //     //event.preventDefault();
            // });
        });

    }
  

    if( $(".mostra_ris_button").length > 0 ){
        $(".mostra_ris_button").click(function(event){
            var id_comunicazione = $("#campi_comunicazione #id_comunicazione").text();
            $C.remote('mostra_utenti', {'comunicazione_id': id_comunicazione}, function(res){
                $("#modal_risultati .modal-body").html(res);
                $('#modal_risultati').modal({
                    keyboard: false,
                    backdrop: "static"
                })
                /* carico il paginatore per l'elenco lungo */
            init_paginatore();
            $("#aggiorna_lista").click(function(){
                var numero_utenti_aggiornato = $("#num_utenti_aggiornato").text(); 
                $(".num_utenti").replaceWith("<span class='alert alert-info well-small num_utenti'><span id='numero_utenti'>"+numero_utenti_aggiornato+"</span> utenti trovati</span>");
                $('#modal_risultati').modal('hide')
            });
            }, {dataType: 'html'});
            
        });     
    }

    


    /* $("#tabs_lingue_traduzioni").tabs(); */

	check_tipo_comunicazione();
    var num_utenti = parseInt($("#numero_utenti").text());
    var tipo_com = $("input[name='scelta_tipo_comunicazione']:checked").val(); 
	$("input[name='scelta_tipo_comunicazione']").change(
    function(){
    	tipo_com = $(this).val();
    	if(tipo_com == "pubblica"){
    		$('.scelta_destinatari_privati').hide();
            $('.scelta_gruppi_pubblica').show();
            $("#invia_form,#button_facebook").removeAttr('disabled');
    	} else {
    		$('.scelta_destinatari_privati').show();
            $('.scelta_gruppi_pubblica').hide();
            if(isNaN(num_utenti)||parseInt(num_utenti)==0){
                $("#invia_form,#button_facebook").attr('disabled','disabled');
            }
    	}
    });
    /* gestione tasto 'Procedi' in scelta destinatari*/
    if((parseInt(num_utenti)>0)||(tipo_com == "pubblica")){
        $("#invia_form,#button_facebook").removeAttr('disabled');
    }else{
        $("#invia_form,#button_facebook").attr('disabled','disabled');
    }
    /* gestione checkbox esclusivi in scelta destinatari */
    /* gestione click */
    $(".checkbox_servizio").click(function(){
        checkbox_esclusivi(this,'click');
    })
    /* gestione caricamento pagina */
    $(".checkbox_servizio").each(function(){
        if (this.checked) {
            checkbox_esclusivi(this,'ready');
        };
    });

    if ( $("#twitter_connesso").length > 0 ){
        $C.remote('get_auth_nickname', {'id': "twitter_admin"}, function(res){
            var nickname = res['nickname']
            var logout_path = res['logout_path']
            $("#admin_controls").append("<p class='logo_twitter'>Ciao "+nickname+" - <a href='"+logout_path+"'>Logout</a></p>");
            /* sistemo la posizione delle icone delle app */
            $("#app_buttons").addClass('aggiusta_icone_app');
            $("#header").css("height","120px");
        });
         
    };

    /* se clicco sul checkbox di twitter allora mando la comunicazione anche sul portale */
    $('input#twitter').click(function () {
    if($('input#twitter').is(':checked'))
        $("input#portale").attr('checked','checked').attr('disabled','disabled')
    else
        $("input#portale").removeAttr('checked').removeAttr('disabled')
    });
   
   /* se clicco sul checkbox di twitter allora mando la comunicazione anche sul portale */
    $('input#facebook').click(function () {
    if($('input#facebook').is(':checked'))
        $("input#portale").attr('checked','checked').attr('disabled','disabled')
    else
        $("input#portale").removeAttr('checked').removeAttr('disabled')
    });

    /* se clicco sul checkbox degli rss allora mando la comunicazione anche sul portale */
    $('input#rss').click(function () {
    if($('input#rss').is(':checked'))
        $("input#portale").attr('checked','checked').attr('disabled','disabled')
    else
        $("input#portale").removeAttr('checked').removeAttr('disabled')
    });

    if ( $("#testo_breve_comunicazione").length > 0 ){
        $('#testo_breve_comunicazione').check_input_length();
        $('#testo_breve_comunicazione').keyup(function(){
            $(this).check_input_length();
        }).change(function(){ $(this).check_input_length(); });
    }
    



});


