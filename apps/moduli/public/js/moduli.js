/* tolto per problemi con defer su js */
/*
if($("#modulo.modulo").length > 0){
    $('#modulo-modal_caricamento').modal({
        keyboard: false,
        backdrop: "static"
    });
};
*/

/* funzione che fa inserire al massimo n_max_char caratteri nel campo con id id_campo_input e messaggio su campo con id id_messaggio */
(function($) {
    $.fn.check_input_length = function(n_max_char,id_campo_input,id_messaggio){
        var input_length = $("#"+id_campo_input).val().length;
        var valore_corrente = $("#"+id_campo_input).val();
        var car_mancanti = (n_max_char - input_length);
        if(input_length >= n_max_char){
            $("#"+id_messaggio).css('color','red').text('N° massimo caratteri: '+n_max_char);
            $("#"+id_campo_input).val(valore_corrente.substring(0, n_max_char-1));
        } else {
            $("#"+id_messaggio).css('color','green').text('Caratteri mancanti:'+car_mancanti);
        } 
    };
})(jQuery); 

function isBlank(str) {
    return (!/\S/.test(str));
};

/* funzione ricorsiva che ritorna gli id dei padri dell'elemento passato */
function get_id(elemento){
    padre = elemento.parent();
    if(!padre.is('body')){
        if((padre.attr('id') != null) && padre.attr('class')!= null && padre.attr('class').match('widget')){
            return get_id(padre)+elemento.parent().attr('id')+",";
        }else{
            return get_id(padre);
            //return ",";
        }
    }else{
        return "";
    }
}

jQuery.fn.addHidden = function (name, value) {
    return this.each(function () {
        var input = $("<input>").attr("type", "hidden").attr("name", name).val(value);
        $(this).append($(input));
    });
};

function checkbox_esclusivi_control(event){
    checkbox_esclusivi(event.target, event.data.evento, event.data.div_contenitore);
};

function checkbox_esclusivi(checkbox_cliccato, evento, div_contenitore){
        /* se non ho cliccato su un checkbox con classe escludi faccio esclusivita */
    if($(checkbox_cliccato.parentElement.parentElement).hasClass('escludi') == false){
        div_contenitore.find("input:checkbox").each(function(index) {
            if( (checkbox_cliccato != this) && ($(this.parentElement.parentElement).hasClass('escludi') == false) ){
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
    }
};


/* controllo stringa vuota */
String.prototype.isEmpty = function() {
    return (this.length === 0 || !this.trim());
};

function adatta_input(obj){
    //calcolo la larghezza del campo di input e tolgo un margine di sicurezza di 80px
    width_obj = ($(obj).width()-80);
    // setto il testo in uno span per calcolare la width del testo
    txt = $(obj).val();
    body_width = $("body").width();
    $("body").css('width', '500000px');
    $("body").append("<span class='el_tmp'></span>");
    
    
    current_str = $("#modulo-dimensioni").val();
    if(current_str.isEmpty()){
        var hsh = {};
    }else{
        hsh = JSON.parse(current_str.replace(/'/ig,'"'));
    }

    var textLength = 0;
    n_aumenti = 0;
    //ricavo ogni linea del testo e aggiungo delle righe se la riga supera la dimensione in pixel della textarea
    var linee = txt.split(/\r\n|\r|\n/);
    for (var i in linee) {
        if (!!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer 11
        {
            n_aumenti += 2;
        }
        else  // If another browser
        {
            n_aumenti += 1;
        }
        $(".el_tmp").text(linee[i]);
        textLength = $(".el_tmp").width();
        //confronto pixel
        if (textLength >= width_obj){
            linee_da_aggiungere = Math.floor(textLength/width_obj);
            n_aumenti += linee_da_aggiungere;
        }
    }
    $("body").css('width', body_width+'px');
    $(".el_tmp").remove();
    cur_heigth = $(obj).height();

    $(obj).attr('rows', n_aumenti);
    //setto la dimensione nel campo nascosto 
    hsh[$(obj).attr('id')] = n_aumenti;

    $("#modulo-dimensioni").val(JSON.stringify(hsh));
}



function calcola_totale_importi_modulo(){
    var totale_importi_modulo = 0;
    $(".importo_per_totale").each(function(){
        var str = $(this).attr('name');
        var id_importo = str.replace("importi[", "").replace("]",""); 
        if($("#modulo-"+id_importo+":checkbox:checked").length > 0){
            var importo_singolo = $(this).val().replace(",",".");
            console.log("importo: "+importo_singolo); 
            if( !isNaN(parseFloat(importo_singolo)) )
            totale_importi_modulo += parseFloat(importo_singolo);
        }
        
    })
    var str_importi = totale_importi_modulo.toFixed(2).toString();
    return str_importi.replace(".",",");
}


/* funzioni per calcolo date su esperienze lavorative cagliari */
function get_data_inizio_max_e_inizio_servizio(numero_anni){
    //var numero_anni = 15; /* per avere 15 anni */
    var data_fine_pub_bando = $("#data_fine_pubblicazione_bando").val();

    var data_inizio_max = null;
    if(data_fine_pub_bando != ""){
        data_inizio_max = new Date(data_fine_pub_bando);
    }else{
        data_inizio_max = new Date();
    }
    /* cambio il formato, da gg/mm/aaaa a mm/gg/aaaa */
    var str_data_inizio_servizio = $("#inizio_servizio").val();
    var parti_data = str_data_inizio_servizio.split("/");
    var data_inizio_servizio = new Date(parti_data[2], parti_data[1] - 1, parti_data[0]);
    data_inizio_max.setFullYear(data_inizio_max.getFullYear() - numero_anni);
    return [data_inizio_max,data_inizio_servizio];


}

function get_data_fine_max_e_fine_servizio( ){
    var data_fine_pub_bando = $("#data_fine_pubblicazione_bando").val();

    var data_fine_max = null;
    if(data_fine_pub_bando != ""){
        data_fine_max = new Date(data_fine_pub_bando);
    }else{
        data_fine_max = new Date();
    }
    /* cambio il formato, da gg/mm/aaaa a mm/gg/aaaa */
    var str_data_fine_servizio = $("#fine_servizio").val();
    var parti_data = str_data_fine_servizio.split("/");
    var data_fine_servizio = new Date(parti_data[2], parti_data[1] - 1, parti_data[0]);
    return [data_fine_max,data_fine_servizio];
}


function check_ordine_date_corrette(){
    var val_data_inizio = $("#inizio_servizio").val();
    console.log("val_data_inizio: ",val_data_inizio);
    var val_data_fine = $("#fine_servizio").val();
    console.log("val_data_fine: ",val_data_fine);
    /* se una delle due date vuota non mostro errore*/
    if (!val_data_inizio || !val_data_fine ){
        return true;
    }
    /* arriva una data in formato 02/04/2019 */
    let data_inizio_splittata = val_data_inizio.split("/");
    let giorno = data_inizio_splittata[0];
    let mese = data_inizio_splittata[1];
    let anno = data_inizio_splittata[2];
    var data_inizio = new Date(anno,mese,giorno);
    let data_fine_splittata = val_data_fine.split("/");
    giorno = data_fine_splittata[0];
    mese = data_fine_splittata[1];
    anno = data_fine_splittata[2];
    var data_fine = new Date(anno,mese,giorno);
    return (data_inizio < data_fine);
}


/* $('.adaptive_input textarea').hide(); tolto per problemi con defer su js */

$(document).ready(function(){
    /* cancella i dati precaricati di firefox */
    $("form").trigger("reset");

    // prima inizializzo il paginatore e poi rendo responsive la tabella, senò non funziona il paginatore mobile
    init_paginatore();
    $("table").addClass("table-responsive");
    /* tolgo classe per tabelle all'interno dei moduli */
    $("#modulo table").removeClass("table-responsive");
    $allResponsiveTables = $( "table.table-responsive" ); // salvo qui le tabelle della pagina per poter recuperare l'indice durante la conversione
    $allResponsiveTables.each(function() {
      // ma solo se ha la classe table-responsive, se non ce l'ha spider non è aggiornato oppure le tabelle responsive sono disabilitate
      if($(this).not(".no-responsive") && typeof tableToUl === "function") {
        tableToUl($(this));
      }
    });
    /* imposto la data corrente sui campi 'Data corrente' se presente bottone 'Salva definitivamente' o 'Prosegui' */
    if( $("#modulo-stato_modulo").length > 0 && ( $("#modulo-stato_modulo").text() == 'bozza' ) ){
        var currentDate = new Date()
        var day = currentDate.getDate()
        var month = currentDate.getMonth() + 1
        var year = currentDate.getFullYear()
        $(".data_oggi input").val(day + "/" + month + "/" + year);
    }

    
    if( $("#modulo-stato_modulo").length > 0 && $("#modulo-stato_modulo").text() != 'bozza' ){
        $(".data_oggi").val($("#modulo-data_conferma_modulo").text());
    }

    /* attivo l'input adattativo al caricamento della pagina e al rilascio del tasto mentre scrivo  
    $('.adaptive_input textarea').each(function(index){
        adatta_input(this);
    });

    $('.adaptive_input textarea').keyup(function() {
        adatta_input(this);
    });
    */ 

    /* adatto altezze delle textarea normali */
    $('#modulo-contenuto textarea').each(function(index){
        adatta_input(this);
    });

    $('#modulo-contenuto textarea').keyup(function() {
        adatta_input(this);
    });

    /* abilita la visualizzazione degli id se passo parametro 'identificatori' */
    if(/\?identificatori/.test(location.href)){
        $("input").click(function(event){
            event.preventDefault();
            var id = event.target.id;
            var arr_id = id.split("-");
            arr_id.pop(); /* tolgo ultimo id */
            arr_id.shift(); /* tolgo primo id */
            alert(arr_id);
        });
    }
    
    /* gestione del menu laterale fisso, mettendo un ancora del tipo
    <a name="nomeancora" class="anchor_menu" testo="Testo_voce_menu"></a>
    si crea un menu che contiene i testi delle voci dei menu e che porta alle ancore inserite.
    */ 

    if($(".anchor_menu").length > 0){
        /* calcolo la coordinata y in base all'offset della prima widget gruppo */

        /* var y_top = $(".gruppo:first").offset().top; */
        var y_top = 150;

        var x_offset = $("#modulo").offset().left;
         /* sovrascrivo la coordinata y */
        $("#modulo-sidebar_menu").css('top',y_top+'px');
        $(".sidecontentpullout").css('top',y_top+'px');

        /* controllo se rimane spazio per il menu laterale */
        if(x_offset > 230){
            
            left_px = x_offset - 230;
            $("#modulo-sidebar_menu").css('left',left_px+'px');
            $("#modulo-sidebar_menu").show();
        }else{
            /* mostro il tasto per aprire e chiudere il menu */
            $("#modulo-sidebar_menu").css('left','10px');
            $(".sidecontentpullout").show();
            
            $(".sidecontentpullout").click(function(){
                //$("#modulo-sidebar_menu").css('left','20px');
                $("#modulo-sidebar_menu").toggle('200', function(){
                    if($("#modulo-sidebar_menu").css('display') == 'none'){
                        $(".sidecontentpullout").css('left','0px');
                    }else{
                        $(".sidecontentpullout").css('left','222px');
                    }
                          
                });
            });
        }

        $(".anchor_menu").each(function(index){
            $("#modulo-list_sidebar").append("<li><a href='#"+$(this).attr('name')+"' >"+$(this).attr('testo')+"</a></li>");
        });

        var url_a_pratiche = $("#modulo-torna_indietro").attr('href');
        $("#modulo-list_sidebar").append("<li class='button_indietro'><a href='"+url_a_pratiche+"' class='link_torna_indietro'>Torna Indietro</a></li>");
        $("button[type='submit']").each(function(index){
            $("#modulo-list_sidebar").append("<li class='button_submit'><a href='#' class='link_prosegui' name='"+$(this).attr('name')+"' value='true'>"+$(this).text()+"</a></li>");
        });
        /* se clicco sul Prosegui del menu a sinistra faccio il submit */
        $(".link_prosegui").click(function(){
            name_attr = $(this).attr('name');
            value_attr = $(this).attr('value');
            $("#modulo-form_modulo").addHidden(name_attr,value_attr).submit();
        });
       
    };

    /* se non sono presenti i dati della ditta nascondo il blocco che li contiene */
    if($("#modulo-container_ditta").length > 0){
        if($("#modulo-dati_ditta-nome_ditta-nome_ditta").val() == "" ){
            $("#modulo-container_ditta").hide();
        };
    };

    /* gestione checkbox esclusivi e combobox */

    $("div[class*='checkbox_esclusivi']").each(function( index ) {
        /* gestione click */
        var div_contenitore_chekbox = $(this);
        $(this).find("input:checkbox").on( "click", {
            evento: 'click',
            div_contenitore: div_contenitore_chekbox
            }, checkbox_esclusivi_control);
        /* gestione caricamento pagina */
        $(this).find("input:checkbox").each(function(){
            if (this.checked) {
                checkbox_esclusivi(this,'ready',div_contenitore_chekbox);
            };
        });
    });
    
    $("select[class*='input_combobox']").each(function( index ) {
        //$(this).combobox();
        // ricavo l'id numerico
        var id_numerico;
        var classi = $(this).attr('class').split(" ");
        var i;
        for (i=0; i < classi.length; i++) {
            if (classi[i].match('input_combobox')) {
               id_numerico = classi[i].replace('input_combobox','');
            }  
        }
        /* setto il valore sul campo di input che viene salvato in db */
        $(this).on('change', function(){
            var id_padre = get_id($(this));
            if(id_padre.match('modulo-gruppo_')){
                array_id = id_padre.split("modulo-");
                id_padre = (array_id[array_id.length-1]).replace(',','');
            }
            if(id_padre.match('gruppo_')){
                $("input[name='_w[modulo]["+id_padre+"][scelta_tipologia_"+(id_numerico)+"]'").val(this.value);
            }else{
                $("input[name='_w[modulo][scelta_tipologia_"+(id_numerico)+"]'").val(this.value);
            }
        });
    });

    /* al caricamento popolo la select dal campo di testo salvato */

    $("input[name*='[scelta_tipologia_']").each(function(index) {

        var valore = $(this).attr('value');
        var name = $(this).attr('name');
        //ricavo l'id della select
        var array_id_numerici = name.split("scelta_tipologia_");
        var id_numerico = (array_id_numerici[array_id_numerici.length-1]).replace(']','');
        $("select.id-select_"+id_numerico).val(valore);
    });

    /* VECCHIO CONTROLLO SU CHECKBOX E COMBOBOX, CONTROLLARE CON TANTI CHECKBOX ESCLUSIVI */
    // for(var i = 0; i < 30; i++) {

    //     /* gestione click */
    //     $(".checkbox_esclusivi"+i+" input:checkbox").on( "click", {
    //         evento: 'click',
    //         classe: ".checkbox_esclusivi"+i
    //         }, checkbox_esclusivi_control);

    //     /* gestione caricamento pagina */
    //     $(".checkbox_esclusivi"+i+" input:checkbox").each(function(){
    //         if (this.checked) {
    //             checkbox_esclusivi(this,'ready','.checkbox_esclusivi'+i);
    //         };
    //     });

    //     /* gestione combobox */
    //     $(".input_combobox"+i).combobox();
    // }

    /* gestione dei campi precompilati se vengo da PRATICHE EDILIZIE */
    if($("input[name='id_pratica']").length > 0){
        /* gestione dei campi disabled nei dati utente */
        $("#modulo-dati_utente input").attr('readonly', 'readonly');

        /* dati in una widget gruppo solo readable */

        //$(".dati_readable_js input").attr('readonly', 'readonly'); no, uso disabled per problemi con datepicker
        $(".dati_readable_js input").attr('disabled', 'disabled');
        /* chiamo il preventDefault per non far aprire il datepicker */
        $(".dati_readable_js input:text").click(function(event){
            event.preventDefault();
        });

        $(".dati_readable_js input:checkbox").attr('readonly', 'readonly').attr("disabled", "disabled");
        $(".dati_readable_js textarea").attr('readonly', 'readonly');

        /* gestione dei campi disabled per i campi caricati da pratiche */
        //$(".readonly_js input").attr('readonly', 'readonly'); no, uso disabled per problemi con datepicker
       
        /* gestione dei campi disabled per i campi caricati da pratiche */
        $(".write_enable_js input").removeAttr('readonly');
        $(".write_enable_js input:checkbox").removeAttr('readonly').removeAttr('disabled');
        $(".write_enable_js textarea").removeAttr('readonly');
    }


    /* gestione dei campi in sola lettura anche se non vengo da pratiche */
    $(".readonly_js input").attr('disabled', 'disabled');
    $(".readonly_js input").click(function(event){
         //chiamo il preventDefault per non far aprire il datepicker 
        event.preventDefault();
    });
    $(".readonly_js input:checkbox").attr('readonly', 'readonly').attr("disabled", "disabled");
    $(".readonly_js textarea").attr('readonly', 'readonly');

    /* tolgo il readonly altrimenti non inviano il dato nel post */
    $("#modulo-form_modulo").submit(function(){
        $(".readonly_js input").removeAttr("disabled");
        $(".dati_readable_js input").removeAttr("disabled");
    });


    /* visualizzo la classe error se il campo errori contiene l'id */

    if($("#modulo-errori").length > 0 && $("#modulo-errori").text() != "" && $("#modulo-errori").text() != null ){
        var errori = JSON.parse($("#modulo-errori").text());
        if(errori.length > 0){
            $("#modulo-avviso_errore").show();
            for (index = 0; index < errori.length; index++) {
                id = errori[index].campo;
                if($("*[id$='"+id+"']").length > 0){
                    if($("*[id$='"+id+"']").length > 1){
                        var obj_dove_mostrare_errore = $("*[id$='"+id+"']")[0];
                    }else{
                        var obj_dove_mostrare_errore = $("*[id$='"+id+"']");
                    }
                    /* $("*[name*='"+id+"']" ).before("<div class='alert alert-error width_50 clear_both'>"+errori[index].msg+"</div>"); */
                    $(obj_dove_mostrare_errore).prepend("<div class='alert alert-error alert-danger width_50 clear_both'>"+errori[index].msg+"</div>");

                    /* versione che cerca tutti gli elementi figli del moduli:opzioni */
                    var elementi_filtrati = $("*[name*='["+id+"]']");
                    
                    /* controllo la chiave input_type per capire su che tipi di input devo mostrare l'errore */
                    if(errori[index].input_type == 'checkbox'){
                       elementi_filtrati = elementi_filtrati.filter(":checkbox")
                    }
                    /* ho solo gli elementi filtrati */
                    elementi_filtrati.each(function(i){
                        /* se la widget opzione ha la classe escludi non mostro il rosso */
                        if($(this.parentElement.parentElement).hasClass('escludi') == false){
                            // $(this).parent().before("<div class='alert alert-error width_50 clear_both'>"+errori[index].msg+"</div>");
                            $(this).css('border','2px solid #b94a48').css('color','#b94a48');
                            $(this).css('outline','2px solid #b94a48');
                        }
                    });  
                }
                
            }

        }
        
    }

    /* gestione delle sezioni, se presente il codice evento */

    if( $('#modulo-sezione_attiva').length > 0){
        var cod_sezione = $('#modulo-sezione_attiva').text();
        var sez_da_mostrare = "#modulo-sezione_evento_"+$('#modulo-sezione_attiva').text();
        $(sez_da_mostrare).show();
        /* mostro il pezzo comune che ha le classi sezione_comune_X */
        $(".sezione_comune_"+cod_sezione).show();
        /* check su sezione passata */
        $("input:checkbox[id$='scia_tipo_"+cod_sezione+"-input']").prop('checked', true);
        $("input:checkbox[id*='scelta_"+cod_sezione+"_']").each(function () {
            $(this).removeAttr("disabled").removeAttr("readonly");
        });

    }


    /* quando il document caricato tolgo il modal */
    
    if($("#modulo.modulo").length > 0){
        $('#modulo-modal_caricamento').remove();
    }
    
    /* mostro un asterisco sui campi obbligatori nel placeholder */
    $(".obbligatorio input, .obbligatorio textarea").attr("placeholder", "Obbligatorio");

    if($("#modulo-stato_modulo").text() == 'confermato' || $("#modulo-stato_modulo").text() == 'inviato'){
        $("textarea").attr('readonly','readonly');
        $("input").attr('disabled','disabled');
        $(".custom-combobox").remove();
        /* se sto vedendo un modulo confermato o inviato non mostro link per cambiare allegato o per cancellarlo */
        $(".wdgt-Spider-Forms-FileInput .js-change-link").hide();
        $(".wdgt-Spider-Forms-FileInput .clear").hide();
        $(".wdgt-Moduli-Allegato .change").hide();
    }



    

    if($("#upload_modulo_firmato").length > 0 ){
        $("#invia_doc_firmati").attr('disabled','disabled');
        $("#upload_modulo_firmato").change(function (){
            $("#doc_valido").addClass('hide');
            $("#doc_non_valido").addClass('hide');
            
            var pdf_firmato = $('#upload_modulo_firmato')[0].files[0];

            var sFileName = pdf_firmato.name;
            var tipo_file = sFileName.split('.')[sFileName.split('.').length - 1].toLowerCase();
            //var dimensione = pdf_firmato['size'];


            //var tipo_file = pdf_firmato["type"]; non va questo controllo su tutti i browser
            var tipo_firma = $("#tipo_firma").text();
            console.log("tipo firma:"+tipo_firma);
            console.log("tipo estensione:"+tipo_file);
            if(tipo_firma == "solo_p7m"){
                //var tipi_file_validi = ["application/pkcs7-mime"];
                var tipi_file_validi = ['p7m']
            }else{
                //var tipi_file_validi = ["application/pdf", "application/pkcs7-mime"];
                var tipi_file_validi = ['pdf','p7m']
            }
            if ($.inArray(tipo_file, tipi_file_validi) < 0) {
                //alert("Formato non valido");
                $("#doc_non_valido").removeClass('hide');
                $("#invia_doc_firmati").attr('disabled','disabled');
            }else{
                //alert("Formato valido!");
                $("#doc_valido").removeClass('hide');
                $("#invia_doc_firmati").removeAttr('disabled');
            }
        });
    }

      
    
    /* Controllo i file upload che sia stato caricato un file con estensione valida */

    if($("#modulo-contenuto").length > 0 ){
        //$("#invia_doc_firmati").attr('disabled','disabled');
        $("input[name^='_w[modulo][allegato']").change(function (){
            console.log("Controllo formato allegati");
            
            var pdf_firmato = $(this)[0].files[0];

            var sFileName = pdf_firmato.name;
            var tipo_file = sFileName.split('.')[sFileName.split('.').length - 1].toLowerCase();
            //var dimensione = pdf_firmato['size'];

            var id_corrente = $(this).attr('id');
            //var tipo_file = pdf_firmato["type"]; non va questo controllo su tutti i browser
            console.log("tipo estensione:"+tipo_file);
            var tipi_file_validi = ['pdf', 'p7m', 'm7m', 'doc', 'docs', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'png', 'jpg', 'gif', 'jpeg']
            
            if ($.inArray(tipo_file, tipi_file_validi) < 0) {
                alert("Formato non valido");
                //sostituisco il campo file input con se stesso senza i file caricati
                $("#"+id_corrente).replaceWith($("#"+id_corrente).val('').clone(true));
            }else{
                //alert("Formato valido!");
            }

            if(pdf_firmato.size <= 0){
                alert("File vuoto non valido");
                //sostituisco il campo file input con se stesso senza i file caricati
                $("#"+id_corrente).replaceWith($("#"+id_corrente).val('').clone(true));
            }

        });
    }



    /* rendo readonly i checkbox con classe obbligatorio */
    $("input[name^='importi_collegati'].obbligatorio:checked","table.tabella_importi").attr('disabled', 'disabled');
    /* all'invio del form devo togliere il disabled altrimenti non viene mandato il check */
    $("#modulo-form_modulo").on('submit',function(){
        $("input[name^='importi_collegati']:checked","table.tabella_importi").removeAttr('disabled');
    })
    /* carico il totale al caricamento della pagina */
    $("#modulo-totale_importi_modulo").text(calcola_totale_importi_modulo);
    $("#modulo-totale_importi").val(calcola_totale_importi_modulo);
    /* cambio il totale quando inserisco importo su campo testo */
    $(".importo_per_totale").on('keyup', function(){
        $("#modulo-totale_importi_modulo").text(calcola_totale_importi_modulo);
        $("#modulo-totale_importi").val(calcola_totale_importi_modulo);
    });
    /* cambio il totale quando seleziono un importo */
    $(".checkbox_importo").on('click', function(){
        $("#modulo-totale_importi_modulo").text(calcola_totale_importi_modulo);
        $("#modulo-totale_importi").val(calcola_totale_importi_modulo);
    });
    $(".errore_importo").prepend("<span>Importo non valido!</span>");


    /* ciclo sull'hash degli eventi associati, se il codice uguale a quello della pratica mostro il riquadro, se il codice diverso nascondo */
    if($("#modulo-associazione_eventi_sezioni").length > 0 && $("#modulo-evento_pratica").length > 0){
        var hash_eventi = jQuery.parseJSON($("#modulo-associazione_eventi_sezioni").text());
        jQuery.each(hash_eventi, function (id_riquadro, obj_codice) {
            /* se il riquadro ha id diverso da evento_pratica nascondo*/
            if($("#modulo-gruppo_"+id_riquadro).length > 0 && obj_codice['codice'] != $("#modulo-evento_pratica").text()){
                /* uso anche classi di bootstrap per nascondere le sezioni per non ricompilare il css */
                $("#modulo-gruppo_"+id_riquadro).addClass('riquadro_nascosto hidden hide'); 
            }
        });
    }

    /* INIZIO PARTE PER BANDI CAGLIARI */
    
    /* bugfix per slect dei mesi e anni su datepicker con modal bootstrap */
    $('.mio_modal').on('show.bs.modal', function () {
        $.fn.modal.Constructor.prototype.enforceFocus = function () { };
    });

    

    $(document).on('click','#inserisci_servizio',function(){
        $("form").find("input[type=text], textarea, select").val("");
        $("#rid_lavorativa").val("100,00");
        $("#modal_servizi").modal('show');
    }); 

    $("#invia_servizio").click(function(){
        hash_form_params = {};
        var campi_vuoti_servizio = false;
        hash_form_params['tipo_amministrazione'] = $("#tipo_amministrazione").val();
        hash_form_params['cat_giuridica'] = $("#cat_giuridica").val();
        hash_form_params['tipologia_contrattuale'] = $("#tipologia_contrattuale").val();
        if( isBlank($("#amministrazione").val()) ){
            $("#amministrazione").parent().parent().addClass("has-error");
            campi_vuoti_servizio = true;
        }else{
            hash_form_params['amministrazione'] = $("#amministrazione").val();
            $("#amministrazione").parent().parent().removeClass("has-error");
        }
        if( isBlank($("#inizio_servizio").val()) ){
            $("#inizio_servizio").parent().parent().addClass("has-error");
            campi_vuoti_servizio = true;
        }else{
            hash_form_params['inizio_servizio'] = $("#inizio_servizio").val();
            $("#inizio_servizio").parent().parent().removeClass("has-error");
        }
        if( isBlank($("#fine_servizio").val()) ){
            $("#fine_servizio").parent().parent().addClass("has-error");
            campi_vuoti_servizio = true;
        }else{
            hash_form_params['fine_servizio'] = $("#fine_servizio").val();
            $("#fine_servizio").parent().parent().removeClass("has-error");
        }

        hash_form_params['note'] = $("#note").val();
        hash_form_params['rid_lavorativa'] = $("#rid_lavorativa").val().replace(",",".");
        
        if(!campi_vuoti_servizio){
            /* inserisco i dati del modulo */
            hash_form_params['id_modulo'] = $("#id_modulo").text();
            $C.remote('box_servizi_pa', hash_form_params, function(res){
                $('#modal_servizi').modal('hide');
                $("#box_servizi").empty().replaceWith(res);
                if( $("#errori").length > 0 ){
                    $("button.conferma").addClass('disabled').attr('disabled','disabled');
                }else{
                    $("button.conferma").removeClass('disabled').removeAttr('disabled');
                }
            }, {dataType: 'html'});
            
        }else{
            alert("Controllare i campi obbligatori!");
        }
        

    });
    
    $(document).on('click','#inserisci_titolo',function(){
        $("form").find("input[type=text], textarea, select").val("");
        $("#modal_titoli").modal('show');
    }); 

    $("#invia_titolo").click(function(){
        hash_form_params = {};
        var campi_vuoti_titolo = false;
        hash_form_params['tipo_titolo'] = $("#tipo_titolo").val();    
        if( isBlank($("#universita").val()) ){
            $("#universita").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['universita'] = $("#universita").val();
            $("#universita").parent().parent().removeClass("has-error");
        }
        if( isBlank($("#in").val()) ){
            $("#in").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['in'] = $("#in").val();
            $("#in").parent().parent().removeClass("has-error");
        }
        if( isBlank($("#facolta").val()) ){
            $("#facolta").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['facolta'] = $("#facolta").val();
            $("#facolta").parent().parent().removeClass("has-error");
        }    
        if( isBlank($("#anno_accademico").val()) ){
            $("#anno_accademico").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['anno_accademico'] = $("#anno_accademico").val();
            $("#anno_accademico").parent().parent().removeClass("has-error");
        }
        hash_form_params['valutazione'] = $("#valutazione").val();
        /* cambiato valutazione con Titolo/descrizione */
        /*
        if( isBlank($("#titolo_descrizione").val()) ){
            $("#titolo_descrizione").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['titolo_descrizione'] = $("#titolo_descrizione").val();
            $("#titolo_descrizione").parent().parent().removeClass("has-error");
        } 
        */
        if(!campi_vuoti_titolo){
            /* inserisco i dati del modulo */
            hash_form_params['id_modulo'] = $("#id_modulo").text();
            $C.remote('box_titoli_studio', hash_form_params, function(res){
                $('#modal_titoli').modal('hide');
                $("#box_titoli_studio").empty().replaceWith(res);
                /* controlla_presenza_servizi_titoli(); SPARITA?? */
            }, {dataType: 'html'});
        }else{
            alert("Controllare i campi obbligatori!");
        }
    });

    $(document).on('click','#inserisci_titolo_vario',function(){
        $("form").find("input[type=text], textarea, select").val("");
        $("#modal_titoli_vari").modal('show');
    }); 

    $("#invia_titolo_vario").click(function(){
        hash_form_params = {};
        var campi_vuoti_titolo_vario = false;
        hash_form_params['tipo_titolo'] = $("#tipo_titolo_vario").val();
        
        if( isBlank($("#universita_vario").val()) ){
            $("#universita").parent().addClass("has-error");
            campi_vuoti_titolo_vario = true;
        }else{
            hash_form_params['universita'] = $("#universita_vario").val();
            $("#universita_vario").parent().removeClass("has-error");
        }
        if( isBlank($("#in_vario").val()) ){
            $("#in_vario").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['in'] = $("#in_vario").val();
            $("#in_vario").parent().parent().removeClass("has-error");
        }
        if( isBlank($("#facolta_vario").val()) ){
            $("#facolta_vario").parent().parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['facolta'] = $("#facolta_vario").val();
            $("#facolta_vario").parent().parent().removeClass("has-error");
        }  
        if( isBlank($("#anno_accademico_vario").val()) ){
            $("#anno_accademico_vario").parent().addClass("has-error");
            campi_vuoti_titolo_vario = true;
        }else{
            hash_form_params['anno_accademico'] = $("#anno_accademico_vario").val();
            $("#anno_accademico_vario").parent().removeClass("has-error");
        }
        hash_form_params['valutazione'] = $("#valutazione_vario").val();
        /* cambiato valutazione con Titolo/descrizione */
        /*
        if( isBlank($("#titolo_descrizione_vario").val()) ){
            $("#titolo_descrizione_vario").parent().addClass("has-error");
            campi_vuoti_titolo = true;
        }else{
            hash_form_params['titolo_descrizione'] = $("#titolo_descrizione_vario").val();
            $("#titolo_descrizione_vario").parent().removeClass("has-error");
        } 
        */
        if(!campi_vuoti_titolo_vario){
            /* inserisco i dati del modulo */
            hash_form_params['id_modulo'] = $("#id_modulo").text();
            $C.remote('box_titoli_vari', hash_form_params, function(res){
                $('#modal_titoli_vari').modal('hide');
                $("#box_titoli_vari").empty().replaceWith(res);
                    
            }, {dataType: 'html'});
        }else{
            alert("Controllare i campi obbligatori!");
        }
    });


    $(document).on('click','.link_cancellazione_servizi',function(){
        var id_modulo = $(this).attr('id_modulo');
        var id_servizio = $(this).attr('id_servizio');
        $("#id_servizio_da_cancellare").text(id_servizio);
        $("#modal_conferma_canc_servizio").modal();
    });

    $("#cancella_servizio").click(function(){
        hash_form_params = {};
        hash_form_params['id_servizio'] = $("#id_servizio_da_cancellare").text();
        /* inserisco i dati del modulo */
        hash_form_params['id_modulo'] = $("#id_modulo").text();
        $C.remote('cancella_servizio', hash_form_params, function(res){
            $('#modal_conferma_canc_servizio').modal('hide');
            $("#box_servizi").empty().replaceWith(res);
            if( $("#errori").length > 0 ){
                $("button.conferma").addClass('disabled').attr('disabled','disabled');
            }else{
                $("button.conferma").removeClass('disabled').removeAttr('disabled');
            }
        }, {dataType: 'html'});
    });


    $(document).on('click','.link_cancellazione_titoli',function(){
        var id_modulo = $(this).attr('id_modulo');
        var id_titolo = $(this).attr('id_titolo');
        $("#id_titolo_da_cancellare").text(id_titolo);
        $("#modal_conferma_canc_titolo").modal();
    });

    $("#cancella_titolo").click(function(){
        hash_form_params = {};
        hash_form_params['id_titolo'] = $("#id_titolo_da_cancellare").text();
        hash_form_params['id_modulo'] = $("#id_modulo").text();
        $C.remote('cancella_titolo', hash_form_params, function(res){
            $('#modal_conferma_canc_titolo').modal('hide');
            $("#box_titoli_studio").empty().replaceWith(res);
            /* controlla_presenza_servizi_titoli(); SPARITA?? */
        }, {dataType: 'html'});

    });


    $(document).on('click','.link_cancellazione_titoli_vari',function(){
        var id_modulo = $(this).attr('id_modulo');
        var id_titolo_vario = $(this).attr('id_titolo_vario');
        $("#id_titolo_vario_da_cancellare").text(id_titolo_vario);
        $("#modal_conferma_canc_titolo_vario").modal();
    });

    $("#cancella_titolo_vario").click(function(){
        hash_form_params = {};
        hash_form_params['id_titolo_vario'] = $("#id_titolo_vario_da_cancellare").text();
        hash_form_params['id_modulo'] = $("#id_modulo").text();
        $C.remote('cancella_titolo_vario', hash_form_params, function(res){
            $('#modal_conferma_canc_titolo_vario').modal('hide');
            $("#box_titoli_vari").empty().replaceWith(res);
        }, {dataType: 'html'});

    });


    if ( $("#modal_servizi").length > 0 ){
        /* Controllo numero caratteri del campo amministrazione */
        $('#amministrazione').check_input_length(80,'amministrazione','msg_caratteri_amministrazione');
        $('#amministrazione').keyup(function(){
            $(this).check_input_length(80,'amministrazione','msg_caratteri_amministrazione');
        }).change(function(){ $(this).check_input_length(80,'amministrazione','msg_caratteri_amministrazione'); });
        
        /* Controllo numero caratteri del campo note */
        $('#note').check_input_length(150,'note','msg_caratteri');
        $('#note').keyup(function(){
            $(this).check_input_length(150,'note','msg_caratteri');
        }).change(function(){ $(this).check_input_length(150,'note','msg_caratteri'); });
        

        /* controllo data di inizio del periodo che deve essere negli ultimi 15 anni */
        $("#inizio_servizio").on("change", function(){
            var numero_anni = 15; /* per avere 15 anni */
            var data_fine_pub_bando = $("#data_fine_pubblicazione_bando").val();
            var date_array_inizio = get_data_inizio_max_e_inizio_servizio(numero_anni);
            var data_inizio_max = date_array_inizio[0];
            var data_inizio_servizio = date_array_inizio[1];
            var date_array_fine = get_data_fine_max_e_fine_servizio();
            var data_fine_max = date_array_fine[0];
            var data_fine_servizio = date_array_fine[1];
            
            /* se datainizio è compresa negli ultimi 15 anni allora ok, altrimenti mostro messaggio */ 
            if( data_inizio_servizio <= data_inizio_max || data_fine_servizio >= data_fine_max){
                $("#invia_servizio").attr("disabled","disabled");
            }else{
                $("#invia_servizio").removeAttr("disabled");
            }
            if( data_inizio_servizio <= data_inizio_max){
                $("#errore_anni_validi").removeClass('hide');
            }else{
                $("#errore_anni_validi").addClass('hide');
            }
            /* controllo ordine delle date inserite */
            if (check_ordine_date_corrette()){
                $("#errore_ordine_date").addClass('hide');
            }else{ //mostro errore
                $("#errore_ordine_date").removeClass('hide');
            }
        } )

        $("#fine_servizio").on("change", function(){
            var numero_anni = 15; /* per avere 15 anni */
            var data_fine_pub_bando = $("#data_fine_pubblicazione_bando").val();
            var date_array_inizio = get_data_inizio_max_e_inizio_servizio(numero_anni);
            var data_inizio_max = date_array_inizio[0];
            var data_inizio_servizio = date_array_inizio[1];
            var date_array_fine = get_data_fine_max_e_fine_servizio();
            var data_fine_max = date_array_fine[0];
            var data_fine_servizio = date_array_fine[1];
            /* se data fine < data pubblicazione bando ok, altrimenti mostro messaggio */ 
            if( data_inizio_servizio <= data_inizio_max || data_fine_servizio >= data_fine_max){
                $("#invia_servizio").attr("disabled","disabled");
            }else{
                $("#invia_servizio").removeAttr("disabled");
            }
            if(data_fine_servizio >= data_fine_max){
                $("#errore_fine_esperienza").removeClass('hide');
            }else{
                $("#errore_fine_esperienza").addClass('hide');
            }
            /* controllo ordine delle date inserite */
            if (check_ordine_date_corrette()){
                $("#errore_ordine_date").addClass('hide');
            }else{ //mostro errore
                $("#errore_ordine_date").removeClass('hide');
            }
        } )

        $('#inizio_servizio').keyup(function(){
            if (check_ordine_date_corrette()){
                $("#errore_ordine_date").addClass('hide');
            }else{ //mostro errore
                $("#errore_ordine_date").removeClass('hide');
            }
                
            
        });

        $('#fine_servizio').keyup(function(){
            if (check_ordine_date_corrette()){
                $("#errore_ordine_date").addClass('hide');
            }else{ //mostro errore
                $("#errore_ordine_date").removeClass('hide');
            }
        });

    }
    
    $(document).on('focusout','#rid_lavorativa', function(){
        var percentuale_rid_lav = null;
        percentuale_rid_lav = $(this).val().replace(",",".");
        if( $.isNumeric(percentuale_rid_lav) ){
            if(percentuale_rid_lav >= 1 && percentuale_rid_lav <= 100){
                $("#errore_percentuale").addClass('hide');
                $("#invia_servizio").removeAttr('disabled');
            }else{
                $("#errore_percentuale").removeClass('hide');
                $("#invia_servizio").attr('disabled','disabled');

            }
            
        }else{
            $("#errore_percentuale").removeClass('hide');
            $("#invia_servizio").attr('disabled','disabled');
        }
    });


    /* FINE PARTE PER BANDI CAGLIARI */

    /* se ci sono riquadri opzionali */
    if($(".riquadro_opzionale").length > 0){
        /* tolgo disabled su checkbox */
        $(".checkbox_riquadro_opzionale input").removeAttr('disabled');
        /* nascondo riquadri */
        $(".parte_sempre_visibile + fieldset").addClass('hide hidden');
        /* se ho avuto degli errori devo riaprire i riquadri */
        if( !($("#modulo-array_id_riquadri_opzionali").val().isEmpty()) ){
            var riquadri_opzionali = JSON.parse($("#modulo-array_id_riquadri_opzionali").val().replace(/'/ig,'"'));
            for(var id_riquadro_da_visualizzare in riquadri_opzionali){
                if(riquadri_opzionali[id_riquadro_da_visualizzare] == 'attivo'){
                    $("#"+id_riquadro_da_visualizzare).removeClass('hide hidden');
                    id_checkbox = id_riquadro_da_visualizzare.replace("fieldset","checkbox");
                    $("#"+id_checkbox).attr('checked','checked');
                }
            }
        }
        
        $(document).on('click','.checkbox_riquadro_opzionale input', function(){
            current_str_riquadri = $("#modulo-array_id_riquadri_opzionali").val();
            if(current_str_riquadri.isEmpty()){
                var hsh = {};
            }else{
                hsh = JSON.parse(current_str_riquadri.replace(/'/ig,'"'));
            }
            if( $(this).is(":checked") ){
                $(this).parent().parent().parent().next().removeClass('hide hidden');
                hsh[$(this).parent().parent().parent().next().attr('id')] = 'attivo';
            }else{
                $(this).parent().parent().parent().next().addClass('hide hidden');
                hsh[$(this).parent().parent().parent().next().attr('id')] = 'nascosto';
            }
            $("#modulo-array_id_riquadri_opzionali").val(JSON.stringify(hsh))
        });  
    }
    

});

