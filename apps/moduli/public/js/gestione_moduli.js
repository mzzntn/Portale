String.prototype.capitalize = function(lower) {return (lower ? this.toLowerCase() : this).replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
};

var my_var = '';
var select_allegati = "";
var select_eventi = "";
var select_interventi = "";
var hash_dati_valorizzati = {}; /* contiene l'hash per il collegamento con gli allegati */
var hash_dati_valorizzati_evento = {}; /* contiene l'hash per il collegamento con gli eventi */
var hash_dati_valorizzati_intervento = {}; /* contiene l'hash per il collegamento con gli interventi */


var classi_width = ['input_xxsmallwidth','input_smallwidth', 'input_mediumwidth_220', 'input_mediumwidth', 'input_largewidth', 'input_bigwidth'];
				        	
function aumenta_dim(classi_presenti,element){
	size_width = parseInt(element.attr('size'));
	if( size_width < (parseInt(classi_width.length)-1) ) {
			size_width = size_width+1;
			element.attr('size',size_width.toString());
			element.attr('class',classi_presenti);
			element.addClass(classi_width[size_width]);
		}
}

function diminuisci_dim(classi_presenti,element){
	size_width = parseInt(element.attr('size'));
	if( size_width > 0  ) {
			size_width = size_width-1;
			element.attr('size',size_width.toString());
			element.attr('class',classi_presenti);
			element.addClass(classi_width[size_width]);
		}
}

function rendi_obbligatorio(classi_presenti,element){
	if(!element.hasClass("obbligatorio")){
		element.addClass('obbligatorio');
	}else{
		element.removeClass('obbligatorio');
	}
}

/* aggiunge la classe .contenitore_checkbox_esclusivi */
function rendi_esclusivi_checkbox(classi_presenti,barra_grigia,contenitore){
	attributi_presenti = barra_grigia.text();
	if(!contenitore.hasClass("contenitore_checkbox_esclusivi")){
		contenitore.addClass('contenitore_checkbox_esclusivi');
		barra_grigia.text(attributi_presenti+' EXCL '); 
	}else{
		contenitore.removeClass('contenitore_checkbox_esclusivi');
		barra_grigia.text(attributi_presenti.replace("EXCL", "")); 
	}
}

/* aggiunge la classe .contenitore_checkbox_obbligatori */
function rendi_obbligatori_checkbox(classi_presenti,barra_grigia,contenitore){
	attributi_presenti = barra_grigia.text();
	if(!contenitore.hasClass("contenitore_checkbox_obbligatori")){
		contenitore.addClass('contenitore_checkbox_obbligatori');
		barra_grigia.text(attributi_presenti+' OBBL '); 
	}else{
		contenitore.removeClass('contenitore_checkbox_obbligatori');
		barra_grigia.text(attributi_presenti.replace("OBBL", ""));
	}
}

/* aggiunge la classe .riquadro_opzionale_checkbox_esclusivi */
function rendi_esclusivi_checkbox_in_riq_opzionale(classi_presenti,barra_grigia,contenitore){
	attributi_presenti = barra_grigia.text();
	if(!contenitore.hasClass("riquadro_opzionale_checkbox_esclusivi")){
		contenitore.addClass('riquadro_opzionale_checkbox_esclusivi');
		barra_grigia.text(attributi_presenti+' EXCL '); 
	}else{
		contenitore.removeClass('riquadro_opzionale_checkbox_esclusivi');
		barra_grigia.text(attributi_presenti.replace("EXCL", "")); 
	}
}

/* aggiunge la classe .riquadro_opzionale_checkbox_obbligatori */
function rendi_obbligatori_checkbox_in_riq_opzionale(classi_presenti,barra_grigia,contenitore){
	attributi_presenti = barra_grigia.text();
	if(!contenitore.hasClass("riquadro_opzionale_checkbox_obbligatori")){
		contenitore.addClass('riquadro_opzionale_checkbox_obbligatori');
		barra_grigia.text(attributi_presenti+' OBBL '); 
	}else{
		contenitore.removeClass('riquadro_opzionale_checkbox_obbligatori');
		barra_grigia.text(attributi_presenti.replace("OBBL", ""));
	}
}

/* aggiunge la classe readonly_js per dati in sola lettura */
function rendi_sola_lettura(classi_presenti,barra_grigia,fieldset){
	attributi_presenti = barra_grigia.text();
	if(!fieldset.hasClass("readonly_js")){
		fieldset.addClass('readonly_js');
		barra_grigia.text(attributi_presenti+' Sola Lettura '); 
	}else{
		fieldset.removeClass('readonly_js');
		barra_grigia.text(attributi_presenti.replace("Sola Lettura", ""));
	}
}

/* layer per campi di testo */
function mostra_layer(current_editor){
	current_editor.$("input[type='text'], input[type='date'], input[type='file']").on('mouseover',function(){
		input_element = $(this);
		var id_elemento = input_element.attr('id');
		if(id_elemento != null && isNaN(parseInt(id_elemento))){
			id_elemento = id_elemento.replace(/_/g, " ").capitalize();
		}else{
			id_elemento = ""
		}
		/* se non ho il layer lo inserisco */
		if(current_editor.$(".layer_input").length == 0){
			if(input_element.hasClass('textarea') || input_element.hasClass('input_file') ){
				current_editor.$("body").append("<div class='layer layer_input hide'><a href='#' class='associa_obbligatorio'>&nbsp;Obbl&nbsp;</a></div>");
			}else{
				current_editor.$("body").append("<div class='layer layer_input hide'>"+id_elemento+"|<a href='#' class='aumenta_dim'>&nbsp;+&nbsp;</li></a>|<a href='#' class='diminuisci_dim'>&nbsp;-&nbsp;</a>|<a href='#' class='associa_obbligatorio'>&nbsp;Obbl&nbsp;</a></div>");
			}
		}
		
		pos_top = $(this).offset().top-10;
		pos_left = $(this).offset().left+5;
		current_editor.$(".layer_input").css({top: pos_top, left: pos_left});
		/* gestione del click */
		indice_classi_width = parseInt(input_element.attr('size'));
		/* se ho un input con la classe per il campo autoadattativo la tengo */
		classi_presenti = "";
		if( (input_element.attr('placeholder') == 'larghezza autoadattativa') || input_element.hasClass("input_dinamico")){
			classi_presenti = "input_dinamico ";
		}
		/* se ho la classe obbligatorio la tengo */
		if( input_element.hasClass("obbligatorio")){
			classi_presenti = "obbligatorio ";
		}
		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".aumenta_dim").off();
		current_editor.$(".diminuisci_dim").off();
		current_editor.$(".associa_obbligatorio").off();


		current_editor.$(".layer_input").removeClass('hide');
		current_editor.$(".aumenta_dim").on('click',function(){
			aumenta_dim(classi_presenti,input_element);
		})
		current_editor.$(".diminuisci_dim").on('click',function(){
			diminuisci_dim(classi_presenti,input_element);
		})
		current_editor.$(".associa_obbligatorio").on('click',function(){
			rendi_obbligatorio(classi_presenti,input_element);
		})
		
		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_input").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_input").remove(); 
			}, 200);	
		});
	});
}

/* layer per contenitori di checkbox */
function mostra_layer_checkbox(current_editor){
	current_editor.$(".barra_contenitore_opzioni").on('mouseover',function(){
		input_element = $(this);
		if(current_editor.$(".layer_checkbox").length == 0){
			current_editor.$("body").append("<div class='layer layer_checkbox hide'><a href='#' class='associa_checkbox_obbligatorio'>&nbsp;Obbl&nbsp;</a>|<a href='#' class='associa_checkbox_esclusivi'>&nbsp;Excl&nbsp;</a></div>");
		}
		pos_top = $(this).offset().top-20;
		pos_left = $(this).offset().left+5;
		current_editor.$(".layer_checkbox").css({top: pos_top, left: pos_left});
		
		classi_presenti = "";
		
		/* se ho la classe obbligatorio la tengo */
		if( input_element.hasClass("check_obbligatorio")){
			classi_presenti = "check_obbligatorio ";
		}
		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".associa_checkbox_obbligatorio").off();
		current_editor.$(".associa_checkbox_esclusivi").off();


		current_editor.$(".layer_checkbox").removeClass('hide');
		
		current_editor.$(".associa_checkbox_obbligatorio").on('click',function(){
			rendi_obbligatori_checkbox(classi_presenti,input_element,input_element.parent());
		})

		current_editor.$(".associa_checkbox_esclusivi").on('click',function(){
			rendi_esclusivi_checkbox(classi_presenti,input_element,input_element.parent());
		})
		
		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_checkbox").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_checkbox").remove(); 
			}, 500);
			
		});
	});
}


/* layer per riquadro opzionale */
function mostra_layer_riquadro_opzionale(current_editor){
	current_editor.$(".barra_riquadro_opzionale").on('mouseover',function(){
		input_element = $(this);
		if(current_editor.$(".layer_riquadro_opzionale").length == 0){
			current_editor.$("body").append("<div class='layer layer_riquadro_opzionale hide'><a href='#' class='riq_opz_check_obbl'>&nbsp;Obbl&nbsp;</a>|<a href='#' class='riq_opz_check_exc'>&nbsp;Excl&nbsp;</a></div>");
		}
		pos_top = $(this).offset().top-20;
		pos_left = $(this).offset().left+5;
		current_editor.$(".layer_riquadro_opzionale").css({top: pos_top, left: pos_left});
		
		classi_presenti = "";
		
		/* se ho la classe obbligatorio la tengo */
		if( input_element.hasClass("check_obbligatorio")){
			classi_presenti = "check_obbligatorio ";
		}
		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".riq_opz_check_obbl").off();
		current_editor.$(".riq_opz_check_exc").off();


		current_editor.$(".layer_riquadro_opzionale").removeClass('hide');
		
		current_editor.$(".riq_opz_check_obbl").on('click',function(){
			rendi_obbligatori_checkbox_in_riq_opzionale(classi_presenti,input_element,input_element.parent());
		})

		current_editor.$(".riq_opz_check_exc").on('click',function(){
			rendi_esclusivi_checkbox_in_riq_opzionale(classi_presenti,input_element,input_element.parent());
		})
		
		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_riquadro_opzionale").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_riquadro_opzionale").remove(); 
			}, 500);
			
		});
	});
}

/* layer template, usato anche da dati utente portale, pratiche e iscrizioni muse */
function mostra_layer_template(current_editor){
	current_editor.$(".barra_opzioni_template").on('mouseover',function(){
		var fieldset_element = $(this); /* il fieldset sarebbe il padre */
		var id_template = fieldset_element.attr('id');
		/* mostro il nome del template, se dati utente portale ho anche il tasto per dati sola lettura */
		if(id_template != null){
			id_template_corretto = id_template.replace(/_/g, " ").capitalize();
		}else{
			id_template_corretto = ""
		}
		if((current_editor.$(".layer_template").length == 0) && ( (id_template == "dati_utente_portale") || (id_template == "dati_iscrizione_muse") || (id_template == "dati_ditta") ) ){
			current_editor.$("body").append("<div class='layer layer_template hide'>"+id_template_corretto+" |<a href='#' class='dati_sola_lettura'>&nbsp;Sola Lettura&nbsp;</a>&nbsp;| <a href='#' class='elimina_template'>&nbsp;<strong>X</strong>&nbsp;</a></div>");
		}else{
			current_editor.$("body").append("<div class='layer layer_template hide'>"+id_template_corretto+"</div>");
		}

		pos_top = $(this).offset().top-20;
		pos_left = $(this).offset().left+5;
		current_editor.$(".layer_template").css({top: pos_top, left: pos_left});
		
		classi_presenti = "";
		
		/* se ho la classe obbligatorio la tengo */
		if( fieldset_element.hasClass("readonly_js")){
			classi_presenti = "readonly_js ";
		}

		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".dati_sola_lettura").off();
		
		current_editor.$(".layer_template").removeClass('hide');
		
		current_editor.$(".dati_sola_lettura").on('click',function(){
			rendi_sola_lettura(classi_presenti,fieldset_element,current_editor.$(".mceTmpl fieldset"));
		})

		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_template").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_template").remove(); 
			}, 500);
			
		});
		/* cancello il template */
		current_editor.$(".elimina_template").on('click',function(){
			fieldset_element.parent().parent().remove();
		})

		



	});
}

/* finestra modale per associazione allegato */
function mostra_layer_per_modal(current_editor){
	current_editor.$("input:not('.checkbox_riquadro_opzionale')[type='checkbox']").on('mouseover',function(event){
		input_element = $(this);
		if(current_editor.$(".layer_modal").length == 0){
			current_editor.$("body").append("<div class='layer layer_modal hide'><a href='#' class='apri_modal'>&nbsp;M&nbsp;</a></div>");
		}
		pos_top = $(this).offset().top-10;
		pos_left = $(this).offset().left-5;
		current_editor.$(".layer_modal").css({top: pos_top, left: pos_left});
		
		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".apri_modal").off();
	
		current_editor.$(".layer_modal").removeClass('hide');
		
		current_editor.$(".apri_modal").on('click',function(event){
			event.preventDefault();
			/* chiamo la funzione apri_modal definita a livello di dom esterno all'iframe */
			parent.apri_modal(input_element, current_editor);
		})

		
		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_modal").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_modal").remove(); 
			}, 1500);
			
		});
	});
};

/* mostra barra scura per associazione evento/intervento su contenitore sezione */
function mostra_layer_per_evento_intervento(current_editor){
	current_editor.$(".barra_contenitore_evento_intervento").on('mouseover',function(event){
		input_element = $(this).parent();
		if(current_editor.$(".layer_evento_intervento").length == 0){
			current_editor.$("body").append("<div class='layer layer_evento_intervento hide'>Associa<a href='#' class='associa_evento'>&nbsp;Evento&nbsp;</a>|<a href='#' class='associa_intervento'>&nbsp;Intervento&nbsp;</a></div>");
		}
		pos_top = $(this).offset().top-10;
		pos_left = $(this).offset().left-5;
		current_editor.$(".layer_evento_intervento").css({top: pos_top, left: pos_left});
		
		/* pulisco gli handler per non fare n volte l'azione */
		current_editor.$(".associa_evento").off();
		current_editor.$(".associa_intervento").off();
	
		current_editor.$(".layer_evento_intervento").removeClass('hide');
		
		current_editor.$(".associa_evento").on('click',function(event){
			event.preventDefault();
			/* chiamo la funzione apri_modal definita a livello di dom esterno all'iframe */
			parent.apri_modal_evento(input_element, current_editor);
		})

		current_editor.$(".associa_intervento").on('click',function(event){
			event.preventDefault();
			/* chiamo la funzione apri_modal definita a livello di dom esterno all'iframe */
			parent.apri_modal_intervento(input_element, current_editor);
		})

		
		/* gestione della scomparsa dei tasti */
		current_editor.$(".layer_evento_intervento").on('mouseleave',function(){
			setTimeout(function(){ 
				current_editor.$(".layer_evento_intervento").remove(); 
			}, 1500);
			
		});
	});
};

/* funzione ricorsiva che ritorna gli id dei padri dell'elemento passato */
function get_id(elemento){
	padre = elemento.parent();
	if(!padre.is('body')){
		if(padre.attr('id') != null){
			if(padre.is('div')){ //se è un div sono in un contenitore di checkbox
				return get_id(padre)+"gruppo_checkbox_"+elemento.parent().attr('id')+",";
			}else{ //se non è un div è un fieldset
				return get_id(padre)+" gruppo_"+elemento.parent().attr('id')+",";
			}
			
		}else{
			return get_id(padre);
			//return ",";
		}
	}else{
		return "";
	}
}

/* mostra la finestra modale e fa scegliere allegato, salva in un tag dell'editor un hash */
window.apri_modal = function(element, editor){
	var id_padre = get_id(element);
	/* problemi con codice numerici */
	// if (isNaN(element.attr('id')))
 //    { 
 //    	id_elemento = element.attr('id').trim();
 //    }else{
 //    	id_elemento = "input_checkbox_"+element.attr('id').trim();
 //    }
	
	var id_elemento = element.attr('id').trim();
	/* ricavo id dell'allegato, tra $ dell'd dell'input */
	var id_allegato_select = id_elemento.split('$')[1];
	input_presente = "<input type='checkbox' id='all_presente' value='presente' />";
    input_assente = "<input type='checkbox' id='all_non_presente' value='assente' />";
	select_allegati_clone = ($(select_allegati))[0].outerHTML;
	console.log('ID ELEMENTO: '+id_elemento);
    if(hash_dati_valorizzati[id_elemento] != null){
		console.log('ID ELEMENTO: '+id_elemento);
    	select_allegati_clone = ($(select_allegati).clone().find("option[value='"+id_allegato_select+"']").attr('selected','selected').parent())[0].outerHTML;
    	if(hash_dati_valorizzati[id_elemento]['presente'] != null){
    		input_presente = "<input type='checkbox' id='all_presente' value='presente' checked='checked' />";
    	}
    	if(hash_dati_valorizzati[id_elemento]['assente'] != null){
    		input_assente = "<input type='checkbox' id='all_non_presente' value='assente' checked='checked' />";
    	} 
    }

    bootbox.dialog(
    	"Associazione allegati <br>"+
    	"<form id='form_allegati_ajax'> Id: <span id='id_contenitore'>"+id_padre+"</span><span id='id_corrente'>"+id_elemento+" </span><br>"+
    	select_allegati_clone +"<br>"+
    	"Selezionare questo checkbox se"+
    	" Presente "+input_presente+" o Assente "+input_assente+" questo allegato nella pratica </form>", 
    	[{
		    "label" : "Annulla",
		    "class" : "",
		    "callback": function() {
		        //console.log("annullo");
		    }
		}, {
		    "label" : "Cancella Associazione",
		    "class" : "btn-danger",
		    "callback": function() {
		        id_elemento = $("#id_corrente").text().trim();
		        $(element).removeClass('associato_allegato');
		        $(element).removeClass('presente');
		        $(element).removeClass('assente');
		        //editor.$("#"+id_elemento).removeClass('associato_allegato');
		        id_elemento = $("#id_corrente").text().trim();
		        if(hash_dati_valorizzati[id_elemento] != null)
		        	delete hash_dati_valorizzati[id_elemento];
		        parent.$("#hash_dati_allegati").val(JSON.stringify(hash_dati_valorizzati));
		    }
		}, {
		    "label" : "Salva",
		    "class" : "btn-success",
		    "callback": function() {
				/* prendo l'id dell'allegato e lo uso come id del checkbox */
				allegato_id = $("#id_corrente").text().trim();
				
		        //$(element).attr('name', allegato_id);
		    	/* devo cambiare anche gli id dei contenitori padre */

		    	/* hash con dati selezionati nella finestra modale */
		    	var h={};
		    	h['codice'] = allegato_id;
		    	/* tolgo la virgola iniziale se presente */
		    	var stringa_id_pre_ass = $("#id_contenitore").text()
		    	if( stringa_id_pre_ass.charAt(0) === ',' )
					stringa_id_pre_ass = stringa_id_pre_ass.slice(1);
				/* salvo nell'hash h gli id completi del checkbox che servono per pratiche edilizie */
				var stato = null;
				if($("#all_presente").is(":checked")){
					stato = 'presente';
					delete h['assente'];
					delete h['presente'];
					h['presente'] = stringa_id_pre_ass+"$"+allegato_id+"$"+stato;
					$(element).addClass('presente');
					$(element).removeClass('assente');
					
		    	};
		    	if($("#all_non_presente").is(":checked")){
					stato = 'assente';
					delete h['presente'];
					delete h['assente'];
					h['assente'] = stringa_id_pre_ass+"$"+allegato_id+"$"+stato;
					$(element).addClass('assente');
					$(element).removeClass('presente');
					
				};
				var id_univoco_checkbox = "";
				id_univoco_checkbox = ( !!stringa_id_pre_ass ? stringa_id_pre_ass+"_" : "" );
				//if(allegato_id.includes(stringa_id_pre_ass))
				id_univoco_checkbox += ("$"+allegato_id+"$"+stato);
				id_univoco_checkbox = id_univoco_checkbox.trim().replace(/,/g,"_");
				hash_dati_valorizzati[id_univoco_checkbox] = h;

		    	//hash_dati_valorizzati[nuovo_id_elemento] = h; /* chiave dell'hash uguale all'id dell'allegato */
				$(element).addClass('associato_allegato');
				element.attr('id',id_univoco_checkbox);

		    	/* salvo in un id l'hash con i valori per l'associazione tra allegati e checkbox */
		    	parent.$("#hash_dati_allegati").val(JSON.stringify(hash_dati_valorizzati));
		    }
		} 
	]);



	$("#selez_allegato").on('change', function() {
  		$("#id_corrente").text(this.value);
	});	
};


/* mostra la finestra modale e fa scegliere evento, salva in un tag dell'editor un hash */
window.apri_modal_evento = function(element, editor){
	var id_padre = get_id(element);
 	var id_elemento = $(element).attr('id').trim();
    select_eventi_clone = ($(select_eventi))[0].outerHTML;
    var evento_associato = hash_dati_valorizzati_evento[id_elemento];
    /* se ho associato qlcs mostro una select con una option selected */
    if(evento_associato != null){
    	select_eventi_clone = ($(select_eventi).clone().find("option[value='"+evento_associato['codice']+"']").attr('selected','selected').parent())[0].outerHTML;
    }

    bootbox.dialog(
    	"Associazione Evento <br>"+
    	"<form id='form_eventi_ajax'> Id Riquadro: <span id='id_contenitore'>"+id_padre+"</span><span id='id_corrente'>"+id_elemento+" </span><br>"+
    	"Il riquadro viene visualizzato con la presenza dell'evento: "+
    	select_eventi_clone +"<br></form>", 
    	[{
		    "label" : "Annulla",
		    "class" : "",
		    "callback": function() {
		        //console.log("annullo");
		    }
		}, {
		    "label" : "Cancella Associazione",
		    "class" : "btn-danger",
		    "callback": function() {
		        id_elemento = $("#id_corrente").text().trim();
		        $(element).removeClass('associato_evento');
		        //editor.$("#"+id_elemento).removeClass('associato_allegato');
		        id_elemento = $("#id_corrente").text().trim();
		        /* cancello scritta evento associato dalla barra grigia */
		        $(element).find(".barra_contenitore_evento_intervento").text("");
		        $(element).find("#codice_evento").remove();
		        if(hash_dati_valorizzati_evento[id_elemento] != null)
		        	delete hash_dati_valorizzati_evento[id_elemento];
		        parent.$("#hash_dati_eventi").val(JSON.stringify(hash_dati_valorizzati_evento));
		    }
		}, {
		    "label" : "Associa",
		    "class" : "btn-success",
		    "callback": function() {
		        //element.attr('id', $("#id_corrente").text().trim());
		    	id_da_associare = $("#id_corrente").text().trim();
		    	/* hash con dati selezionati nella finestra modale */
		    	h={};
		    	var cod_evento = "";
				if($("#selez_evento option").is(":selected")){
					cod_evento = $("#selez_evento").val();
					descr_evento = $("#selez_evento option:selected").text();
					h['codice'] = cod_evento;
		    	};
		    	/* visualizzo sulla barra l'evento associato */
		    	$(element).find(".barra_contenitore_evento_intervento").text("Evento Associato: "+descr_evento);
		    	/* salvo su un campo per poter salvare su db i dati */
		    	hash_dati_valorizzati_evento[id_da_associare] = h; /* chiave dell'hash uguale all'id dell'allegato */
		    	/* cancello eventuale associazione dell'intervento */
		    	hash_dati_valorizzati_intervento[id_da_associare] = null;
		    	$(element).addClass('associato_evento');
		    	$(element).find("#codice_evento").remove();
		    	$(element).append("<span class='hide' id='codice_evento'>"+cod_evento+"</span>");
		    	if($(element).find("#codice_intervento").length > 0 )
		    		$(element).find("#codice_intervento").remove();
		    	/* salvo in un id l'hash con i valori per l'associazione tra eventi e riquadro */
		    	parent.$("#hash_dati_eventi").val(JSON.stringify(hash_dati_valorizzati_evento));
		    }
		} 
	]);



	$("#selez_evento").on('change', function() {
  		$("#id_corrente").text(id_elemento);
	});	
};


/* mostra la finestra modale e fa scegliere evento, salva in un tag dell'editor un hash */
window.apri_modal_intervento = function(element, editor){
	var id_padre = get_id(element);
 	id_elemento = element.attr('id').trim();
    select_interventi_clone = ($(select_interventi))[0].outerHTML;
    var intervento_associato = hash_dati_valorizzati_intervento[id_elemento];
    /* se ho associato qlcs mostro una select con una option selected */
    if(intervento_associato != null){
    	select_interventi_clone = ($(select_interventi).clone().find("option[value='"+intervento_associato['codice']+"']").attr('selected','selected').parent())[0].outerHTML;
    }

    bootbox.dialog(
    	"Associazione Intervento <br>"+
    	"<form id='form_allegati_ajax'> Id Riquadro: <span id='id_contenitore'>"+id_padre+"</span><span id='id_corrente'>"+id_elemento+" </span><br>"+
    	"Il riquadro viene visualizzato con la presenza dell'intervento: "+
    	select_interventi_clone +"<br></form>", 
    	[{
		    "label" : "Annulla",
		    "class" : "",
		    "callback": function() {
		        //console.log("annullo");
		    }
		}, {
		    "label" : "Cancella Associazione",
		    "class" : "btn-danger",
		    "callback": function() {
		        //console.log("cancello");
		        id_elemento = $("#id_corrente").text().trim();
		        $(element).removeClass('associato_intervento');
		        /* cancello scritta evento associato dalla barra grigia */
		        $(element).find(".barra_contenitore_evento_intervento").text("");
		        $(element).find("#codice_intervento").remove();
		        if(hash_dati_valorizzati_intervento[id_elemento] != null)
		        	delete hash_dati_valorizzati_intervento[id_elemento];
		        parent.$("#hash_dati_interventi").val(JSON.stringify(hash_dati_valorizzati_intervento));

		    }
		}, {
		    "label" : "Associa",
		    "class" : "btn-success",
		    "callback": function() {
		        //element.attr('id', $("#id_corrente").text().trim());
		    	id_da_associare = $("#id_corrente").text().trim();
		    	/* hash con dati selezionati nella finestra modale */
		    	h={};
		    	var cod_intervento = "";
				if($("#selez_intervento option").is(":selected")){
					cod_intervento = $("#selez_intervento").val();
					descr_intervento = $("#selez_intervento option:selected").text();
					h['codice'] = cod_intervento;
		    	};
		    	/* visualizzo sulla barra l'evento associato */
		    	$(element).find(".barra_contenitore_evento_intervento").text("Intervento Associato: "+descr_intervento);
		    	/* salvo su un campo per poter salvare su db i dati */
		    	hash_dati_valorizzati_intervento[id_da_associare] = h; /* chiave dell'hash uguale all'id dell'intervento */
		    	/* cancello eventuale associazione dell'evento */
		    	hash_dati_valorizzati_evento[id_da_associare] = null;
		    	$(element).addClass('associato_intervento');
		    	$(element).find("#codice_intervento").remove();
		    	$(element).append("<span class='hide' id='codice_intervento'>"+cod_intervento+"</span>");
		    	if($(element).find("#codice_evento").length > 0 )
		    		$(element).find("#codice_evento").remove();
		    	/* salvo in un id l'hash con i valori per l'associazione tra interventi e id del riquadro */
		    	parent.$("#hash_dati_interventi").val(JSON.stringify(hash_dati_valorizzati_intervento));
		    }
		} 
	]);



	$("#selez_intervento").on('change', function() {
  		$("#id_corrente").text(id_elemento);
	});	
};





function ordina_array_allegati(a,b) {
	if (a.descrizione < b.descrizione)
		return -1;
	if (a.descrizione > b.descrizione)
		return 1;
	return 0;
}

/* controllo se nel tag ci sono le classi passate */

$.fn.hasAnyClass = function() {
    for (var i = 0; i < arguments.length; i++) {
        var classes = arguments[i].split(" ");
        for (var j = 0; j < classes.length; j++) {
            if (this.hasClass(classes[j])) {
                return true;
            }
        }
    }
    return false;
}





$(document).ready(function(){
    /* uso la visulizzazione a tab per il filtro ed estrazione dati in csv del log accesso demo*/
    $("#tabs").tabs();

    var max_id_input_text = 0;
    var max_id_input_checkbox = 0;
    var max_id_fieldset = 0;
    var max_id_contenitore = 0;
    var max_id_input_date = 0;
    var max_id_input_dinamico = 0;
    var max_id_textarea = 0;
    var max_id_input_file = 0;
    var max_id_contenitore_select = 0;
   	var max_id_opzione = 0;
   	var max_id_riquadro_opzionale = 0;

   	if($("#url_ws_allegati").length > 0){
		url = $("#url_ws_allegati").text().trim();
		$.getJSON(url, function(data){
			data_ord = data.sort(ordina_array_allegati);
			var items = [];
			select_allegati = "<select id='selez_allegato'><option value='' ></option>";
			$.each(data_ord, function(index, hash) {
				if(hash.descrizione != null){
					select_allegati += "<option value=\""+hash.codice+"\" title=\""+hash.descrizione+"\">"+hash.descrizione+"</option>"
				}
			});
			select_allegati += "</select>";
		});
	}

	if($("#url_ws_eventi").length > 0){
		url = $("#url_ws_eventi").text().trim();
		$.getJSON(url, function(data){
			data_ord = data.sort(ordina_array_allegati);
			var items = [];
			select_eventi = "<select id='selez_evento'><option value='' ></option>";
			$.each(data_ord, function(index, hash) {
				if(hash.descrizione != null){
					select_eventi += "<option value=\""+hash.codice+"\" title=\""+hash.descrizione+"\">"+hash.descrizione+"</option>"
				}
			});
			select_eventi += "</select>";
		});
	}

	if($("#url_ws_interventi").length > 0){
		url = $("#url_ws_interventi").text().trim();
		$.getJSON(url, function(data){
			data_ord = data.sort(ordina_array_allegati);
			var items = [];
			select_interventi = "<select id='selez_intervento'><option value='' ></option>";
			$.each(data_ord, function(index, hash) {
				if(hash.descrizione != null){
					select_interventi += "<option value=\""+hash.codice+"\" title=\""+hash.descrizione+"\">"+hash.descrizione+"</option>"
				}
			});
			select_interventi += "</select>";
		});
	}

   	//tinyMCE.baseURL = "core/components/public/js/tinymce";

	   var array_template_editor = [{title: 'Dati Utente Portale', description: 'Dati dell\'utente collegato', url: "/moduli/public/js/templates/dati_utente_portale.html" },
	   								{title: "Dati Azienda", description: "Dati dell' azienda", url: "/moduli/public/js/templates/dati_ditta.html" },
   									{title: 'Nome e Cognome Utente Portale', description: 'Nome e Cognome dell\'utente', url: "/moduli/public/js/templates/nominativo_firma.html" }
   								];
   	if($("#url_ws_allegati").length > 0){ //controllo che ci sia l'url che viene messa di default, carica sempre tutti i template DA FINIRE
   	//if($("#hash_allegati_da_db").text() != "{}" && $("#hash_allegati_da_db").text() != ""){	??
   		array_template_editor.push({title: 'Pratica Richiedente', description: 'Dati richiedente della pratica', url: "/moduli/public/js/templates/pratiche_richiedente.html" },
		    {title: 'Pratica Progettista', description: 'Dati progettista della pratica', url: "/moduli/public/js/templates/pratiche_progettista.html" },
		    {title: 'Pratica Terza Referenza', description: 'Dati terza referenza della pratica', url: "/moduli/public/js/templates/pratiche_terza_referenza.html" },
		    {title: 'Pratica Dati Territoriali', description: 'Dati territoriali della pratica', url: "/moduli/public/js/templates/pratiche_dati_territoriali.html" },
		    {title: 'Pratica Metadati', description: 'Dati aggiuntivi della pratica', url: "/moduli/public/js/templates/pratiche_metadati.html" },
		    {title: 'Pratica Zone', description: 'Dati sulle Zone Territoriali', url: "/moduli/public/js/templates/pratiche_zone.html" },
		    {title: 'Pratica Vincoli', description: 'Dati sui Vincoli Territoriali', url: "/moduli/public/js/templates/pratiche_vincoli.html" },
		    {title: 'Pratica Oggetto', description: 'Dati di base della pratica', url: "/moduli/public/js/templates/pratiche_oggetto_pratica.html" },
			{title: 'Pratica Evento/Intervento', description: 'Evento/Intervento', url: "/moduli/public/js/templates/pratiche_evento_intervento.html" },
			{title: 'Pratica Elenco Referenti', description: 'Elenco Referenti della pratica', url: "/moduli/public/js/templates/pratiche_elenco_referenti.html" })
   	}
   	if($("#attiva_template_iscrizioni").length > 0 ){
   		array_template_editor.push({title: 'Iscrizione On Line - MuSe', description: 'Dati iscrizione Scolastica MuSe', url: "/moduli/public/js/templates/dati_iscrizioni_muse.html" })
   	}
   	var lista_pulsanti_editor = 'undo redo | bold italic | bullist | alignleft aligncenter alignright alignjustify | fullscreen | pagebreak | code | visualblocks | template | button_campi | button_salva';
   	if($("#abilita_scelta_font").length > 0 ){
   		lista_pulsanti_editor = 'undo redo | fontselect | fontsizeselect | bold italic | bullist | alignleft aligncenter alignright alignjustify | fullscreen | pagebreak | code | visualblocks | template | button_campi | button_salva';
   	}

    tinymce.init({
    	//language: 'it',
    	/* uso lo skin su CDN per problemi nell'iframe che va a scaricare i css e non usa il file compresso da spider */
    	skin_url: '//cdnjs.cloudflare.com/ajax/libs/tinymce/4.3.7/skins/lightgray',
        selector: '#textarea_tinymce',
        content_css : ['/moduli/public/css/sass/stili_editor_tinymce.css', '//cdnjs.cloudflare.com/ajax/libs/tinymce/4.3.7/plugins/visualblocks/css/visualblocks.css' ],
        height: 400,
        width: 1024,
        fontsize_formats: "8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 22pt 26pt 36pt",
        remove_trailing_brs: false,
        //plugins : 'advlist autolink link image lists charmap print preview',
        //fixed_toolbar_container: '#mytoolbar'   //da provare, toolbar fixed
        plugins: 'code fullpage hr fullscreen table nonbreaking importcss paste template advlist pagebreak visualblocks',
        table_toolbar: "tableprops tabledelete tablecellprops tablemergecells tablesplitcells | tableinsertrowbefore tableinsertrowafter tabledeleterow | tablerowprops | tableinsertcolbefore tableinsertcolafter tabledeletecol",
        menubar: 'edit | view | table | format | insert ',
        toolbar: lista_pulsanti_editor,
        statusbar: true,
        pagebreak_separator: "<div class='break evidenzia_bordo width_full'><!-- pagebreak --></div>",
        //br_in_pre: false,
        pagebreak_split_block: true,
        forced_root_block : '',      // non racchiude tutto in paragrafi, con false mette br
        importcss_append: true,
        //importcss_selector_filter: ".input_"
		/* proprietà da mantenere quando copio da word */
		//paste_retain_style_properties: "color font-size",
		//paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,h4,h5,h6,table,tr,td",
		templates: array_template_editor,
		style_formats_merge: true,
		code_dialog_height: $(window).height()-220,
		code_dialog_width: $(window).width()-220,
  		// valid_styles: {
  		//     'tr': 'width'
  		// },
		paste_preprocess: function(plugin, args) {
		    /* invocato prima di copiare nell'editor */
		    //console.log(args.node);
		},
		paste_postprocess: function(plugin, args) {
		    /* invocato prima di inserire i tag nell'editor */
		    //DA-FARE: nel copia incolla in chrome racchiude le cose copiate in un div e si prende la classe del padre
		    // console.log(args.node);
		    // if(args.node.nodeName == 'DIV'){
		    // 	args.node.removeAttribute('class');
		    // }
		},
    //     forced_root_block_attrs: {
    // 		'class': 'myclass',
    // 		'data-something': 'my data'
  		// }
    //     toolbar: [
    // 		'undo redo | styleselect | bold italic | link image | ',
    // 		'alignleft aligncenter alignright alignjustify'
  		// ]
  		setup: function(editor) {
  			
			    editor.addButton('button_campi', {
			      type: 'menubutton',
			      text: 'Campi',
			      icon: false,
			      menu: [
			      /*
			      {
			        text: 'Input',
			        onclick: function() {
			          dd = editor.$("body");
			          dd.append("<input type='text' class='input_text' />");
			        }
			      },
			      */
			       {
				        text: 'Testo',
				        onclick: function() {
				        	if(editor.$("input[type='text']").length > 0 ){
				        		editor.$("input[type='text']").each(function(index){
								  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_text){
								  	max_id_input_text = parseInt($(this).attr('id'));
								  }
								});
				        	}
				        	/* abilito modifica della larghezza cambiando le classi al click sull' input  */
				        	id_input = max_id_input_text+1
				        	editor.insertContent("&nbsp;<input type='text' id='"+id_input+"' class='input_mediumwidth input_testo' size='3' />&nbsp;");
				        	mostra_layer(editor);

				        }
			    	},
			    	{
				        text: 'Testo con altezza dinamica',
				        onclick: function() {
				        	if(editor.$("input.input_dinamico").length > 0 ){
					        		editor.$("input.input_dinamico").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_dinamico){
									  	max_id_input_dinamico = parseInt($(this).attr('id'));
									  }
									});
					        	}	
			        		id_input = max_id_input_dinamico+1
			        		editor.insertContent("&nbsp;<input type='text' id='"+id_input+"' class='input_dinamico input_mediumwidth_220' placeholder='larghezza autoadattativa' size='2' />&nbsp;");
				        	mostra_layer(editor);
			        	}
			    	},
			    	{
				        text: 'Testo grande',
				        onclick: function() {
					        if(editor.$("input.textarea").length > 0 ){
					        		editor.$("input.textarea").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_textarea){
									  	max_id_textarea = parseInt($(this).attr('id'));
									  }
									});
					        	}
					      		editor.insertContent("&nbsp;<input id='"+(max_id_textarea+1)+"' class='textarea input_largewidth' placeholder='textarea' type='text' />&nbsp;");
					      		mostra_layer(editor);
					      	}     	
			    	},
			        {
				        text: 'Flag (Checkbox)',
				        onclick: function() {
				        	if(editor.$("input:not(.associato_allegato)[type='checkbox']").length > 0 ){
					        		editor.$("input:not(.associato_allegato)[type='checkbox']").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_checkbox){
									  	max_id_input_checkbox = parseInt($(this).attr('id'));
									  }
									});
				        	}
				        	editor.insertContent("<span class='cont_check'><input type='checkbox' id='"+(max_id_input_checkbox+1)+"' class='input_checkbox' /></span>&nbsp;");
		        			if($("#url_ws_allegati").length > 0){
		        				mostra_layer_per_modal(editor);
		        			}
			        	}
			    	},
			    	{
				        text: 'Riquadro',
				        onclick: function() {
				        	if(editor.$("fieldset.contenitore_gruppo").length > 0 ){
					        		editor.$("fieldset.contenitore_gruppo").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_fieldset){
									  	max_id_fieldset = parseInt($(this).attr('id'));
									  }
									});
					        	}
					        	editor.insertContent("&nbsp;<fieldset id='"+(max_id_fieldset+1)+"' class='contenitore_gruppo'><div class='barra_contenitore_evento_intervento'></div><br /></fieldset><br />");
			        			mostra_layer_per_evento_intervento(editor);
			        	}
			    	},
			    	{
				        text: 'Riquadro per opzioni',
				        onclick: function() {
				        	if(editor.$("div.contenitore").length > 0 ){
					        		editor.$("div.contenitore").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_contenitore){
									  	max_id_contenitore = parseInt($(this).attr('id'));
									  }
									});
					        	}
					        	editor.insertContent("&nbsp;<div id='"+(max_id_contenitore+1)+"' class='contenitore'><div class='barra_contenitore_opzioni'></div><br /></div>&nbsp;<br />");
			        			mostra_layer_checkbox(editor);
			        	}
			    	},
			    	{
				        text: 'Riquadro opzionale',
				        onclick: function() {
				        	if(editor.$("div.riquadro_opzionale").length > 0 ){
					        		editor.$("div.riquadro_opzionale").each(function(index){
									  if(!isNaN(parseInt( $(this).attr('id').replace("riq_opzionale_","") )) && parseInt($(this).attr('id').replace("riq_opzionale_","") ) > max_id_riquadro_opzionale){
									  	max_id_riquadro_opzionale = parseInt($(this).attr('id').replace("riq_opzionale_","") );
									  }
									});
					        	}
					        	editor.insertContent("&nbsp;<div id='riq_opzionale_"+(max_id_riquadro_opzionale+1)+"' class='riquadro_opzionale'><div class='parte_sempre_visibile'><input id='checkbox_riquadro_opzionale_"+(max_id_riquadro_opzionale+1)+"' disabled='disabled' type='checkbox' class='checkbox_riquadro_opzionale' /> <span>[Testo visibile]</span></div><fieldset id='fieldset_riquadro_opzionale_"+(max_id_riquadro_opzionale+1)+"' class='riquadro_opzionale_scomparsa mostra_in_stampa'></fieldset> </div>&nbsp;<br />");
			        			//editor.insertContent("&nbsp;<div id='riq_opzionale_"+(max_id_riquadro_opzionale+1)+"' class='riquadro_opzionale'><div class='barra_riquadro_opzionale'></div><div class='parte_sempre_visibile'><input id='checkbox_riquadro_opzionale_"+(max_id_riquadro_opzionale+1)+"' disabled='disabled' type='checkbox' class='checkbox_riquadro_opzionale' /> <span>[Testo visibile]</span></div><fieldset id='fieldset_riquadro_opzionale_"+(max_id_riquadro_opzionale+1)+"' class='riquadro_opzionale_scomparsa '></fieldset> </div>&nbsp;<br />");
			        			
			        			//mostra_layer_riquadro_opzionale(editor);
			        	}
			    	},
			    	{
				        text: 'Campo selezione dinamica (Combobox)',
				        onclick: function() {
				        	if(editor.$("div.contenitore_select").length > 0 ){
					        		editor.$("div.contenitore_select").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_contenitore_select){
									  	max_id_contenitore_select = parseInt($(this).attr('id'));
									  }
									});
					        	}
					        	editor.insertContent("&nbsp;<div id='"+(max_id_contenitore_select+1)+"' class='contenitore_select' ></div>&nbsp;<br />");
			        	}
			    	},
			    	{
				        text: 'Opzione per selezione dinamica',
				        onclick: function() {
				        	if(editor.$("div.opzione").length > 0 ){
					        		editor.$("div.opzione").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_opzione){
									  	max_id_opzione = parseInt($(this).attr('id'));
									  }
									});
					        	}
					        	editor.insertContent("<div id='"+(max_id_opzione+1)+"' class='opzione' ></div>&nbsp;");
			        	}
			    	},
			    	{
				        text: 'Data a Richiesta',
				        onclick: function() {
				        	if(editor.$("input[type='date'].sel_data").length > 0 ){
					        		editor.$("input[type='date'].sel_data").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_date){
									  	max_id_input_date = parseInt($(this).attr('id'));
									  }
									});
					        	}
			        		editor.insertContent("&nbsp;<input type='date' id='"+(max_id_input_date+1)+"' class='sel_data input_date input_mediumwidth_220' placeholder='data dd/mm/aaaa' />&nbsp;");
			        	}
			    	},
			    	{
				        text: 'Data Corrente',
				        onclick: function() {
				        	if(editor.$("input[type='date'].data_oggi").length > 0 ){
					        		editor.$("input[type='date'].data_oggi").each(function(index){
									  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_date){
									  	max_id_input_date = parseInt($(this).attr('id'));
									  }
									});
					        	}
			        		editor.insertContent("&nbsp;<input type='date' id='"+(max_id_input_date+1)+"' class='data_oggi input_date input_mediumwidth_220' placeholder='data corrente dd/mm/aaaa' />&nbsp;");
			        	}
			    	},			    	
			    	{
				        text: 'Inserimento Allegati',
				        onclick: function() {
					        if(editor.$("input[type='file']").length > 0 ){
						        		editor.$("input[type='file']").each(function(index){
										  if(!isNaN(parseInt($(this).attr('id'))) && parseInt($(this).attr('id')) > max_id_input_file){
										  	max_id_input_file = parseInt($(this).attr('id'));
										  }
										});
						        	}
						        	editor.insertContent("&nbsp;<input id='"+(max_id_input_file+1)+"' class='input_file' type='file'></input>");
						      	}
			    	},
			    	{
			        	text: 'A capo forzato',
				        onclick: function() {
				          body_editor = editor.$("body");
				          body_editor.append("<br />");
				        }
			      	}
			      ]
			    /* chiudo add button */
			    });
				editor.addButton('button_salva', {
					title : 'Salva',
					text : 'Salva',
					//image : '../jscripts/tiny_mce/plugins/example/img/example.gif',
					onclick : function() {
						$("#form_salva_modulo").submit();
					}
				});
				editor.on('init', function() 
			    {
			        this.getDoc().body.style.fontSize = '12pt';
			    });
				editor.on('paste', function(e) {
					my_var = 'paste';
                });
				editor.on('change', function(e) {
					/* Qui si intercetta l'inserimento dei campi */
					if(my_var == 'paste' || (e.originalEvent != null && e.originalEvent.command == 'mceInsertContent') ){
						my_var = '';

						//var classi_width = ['input_xxsmallwidth','input_smallwidth', 'input_mediumwidth_220', 'input_mediumwidth', 'input_largewidth', 'input_bigwidth'];
					    tinyMCE.activeEditor.$("input[type='text']").each(function(index){
					    	if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						tinyMCE.activeEditor.$("input:not(.associato_allegato)[type='checkbox']").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						/* contenitore gruppo (riquadro) */
						tinyMCE.activeEditor.$("fieldset.contenitore_gruppo").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
					    	/* controllo i campi inseriti all'interno */
					    	$(this).children().each(function(index,element){
					    		if($(element).hasAnyClass('dati_portale dati_ditta pratiche')){
					    			$(element).css('background-color','red');
					    			var msg_alert = alert("Il template non può essere inserito in un riquadro!");
					    			$(element).remove();
					    		}
					    	});
						});
						/* contenitore checkbox */
						tinyMCE.activeEditor.$("div.contenitore").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
					    	/* controllo i campi inseriti all'interno */
					    	$(this).children().each(function(index,element){
					    		if($(element).hasAnyClass('input_dinamico contenitore contenitore_gruppo contenitore_select opzione input_file')){
					    			$(element).css('background-color','red');
					    			var msg_alert = alert("Il campo non può essere inserito in questo punto!");
					    			$(element).remove();
					    		}
					    	});
					    	
						});
						/* riquadro opzionale */
						tinyMCE.activeEditor.$("div.riquadro_opzionale").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
					    	var element = $(this).find(".riquadro_opzionale");
					    	if(element != null && element.length > 0){
								element.css('background-color','red');
								var msg_alert = alert("Il campo non può essere inserito in questo punto!");
								element.remove();
					    	};					    	
						});
						tinyMCE.activeEditor.$("div.contenitore_select").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
					    	/* controllo i campi inseriti all'interno */
					    	$(this).children().each(function(index,element){
					    		if($(element).hasAnyClass('input_testo cont_check input_dinamico textarea contenitore contenitore_gruppo contenitore_select sel_data data_oggi input_checkbox input_file')){
					    			$(element).css('background-color','red');
					    			var msg_alert = alert("Il campo non può essere inserito in questo punto!");
					    			$(element).remove();
					    		}
					    	});
						});
						tinyMCE.activeEditor.$("div.opzione").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
					    	/* controllo se inserisco l'opzione in un contenitore_select */
					    	padre = $(this).parent();
					    	if(!padre.hasClass('contenitore_select')){
					    		$(this).css('background-color','red');
					    		var msg_alert = alert("Il campo deve essere inserito in una combobox!");
					    		$(this).remove();
					    	}
					    	/* controllo i campi inseriti all'interno */
					    	$(this).children().each(function(index,element){
					    		if($(element).hasAnyClass('opzione input_testo cont_check input_dinamico textarea contenitore contenitore_gruppo contenitore_select sel_data data_oggi input_checkbox input_file')){
					    			$(element).css('background-color','red');
					    			var msg_alert = alert("Il campo non può essere inserito in questo punto!");
					    			$(element).remove();
					    		}
					    	});
						});
						tinyMCE.activeEditor.$("input[type='date'].sel_data").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						tinyMCE.activeEditor.$("input[type='date'].data_oggi").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						tinyMCE.activeEditor.$("input[type='file']").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						tinyMCE.activeEditor.$("input.input_dinamico").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});
						tinyMCE.activeEditor.$("input.textarea").each(function(index){
			  				if( !isNaN(parseInt($(this).attr('id'))) ){
					    		$(this).attr('id',index+1);
					    	}
						});

					mostra_layer(editor);
					mostra_layer_checkbox(editor);
					//mostra_layer_riquadro_opzionale(editor);
					mostra_layer_per_evento_intervento(editor);
					mostra_layer_template(editor);
					if($("#url_ws_allegati").length > 0){
						mostra_layer_per_modal(editor);
					}
					//console.log("HO FATTO UN CHANGE");
					// fine del paste


					}
            		
        		});
               

		/* chiudo setup editor */
		},

		init_instance_callback : function(editor) {
			/* callback invocata quando viene caricato l'editor */
			
			classi_width = ['input_xxsmallwidth','input_smallwidth', 'input_mediumwidth_220', 'input_mediumwidth', 'input_largewidth', 'input_bigwidth'];
   			//console.log("carico editor");
			/* aggiungo un div che mi serve per aumentare la dimensione e associare i dati */

			/* layer per input type text */
			if(editor.$(".layer_input").length > 0){
				editor.$(".layer_input").remove();
			}
			mostra_layer(editor);
        	/* layer per input checkbox */
        	if(editor.$(".layer_checkbox").length > 0){
				editor.$(".layer_checkbox").remove();
			}
        	mostra_layer_checkbox(editor);

        	/* layer per riquadro opzionale */
   			//if(editor.$(".layer_riquadro_opzionale").length > 0){
			// 	editor.$(".layer_riquadro_opzionale").remove();
			// }
        	//mostra_layer_riquadro_opzionale(editor);

        	/* layer per evento intervento */
        	if(editor.$(".layer_evento_intervento").length > 0){
				editor.$(".layer_evento_intervento").remove();
			}
        	mostra_layer_per_evento_intervento(editor);

        	/* layer per dati sola lettura template */
        	if(editor.$(".layer_template").length > 0){
				editor.$(".layer_template").remove();
			}
        	
        	mostra_layer_template(editor);

        	/* se ho configurato l'url degli allegati */
        	if($("#url_ws_allegati").length > 0){
	        	/* layer per mostrare finestra modale */
	        	if(editor.$(".layer_modal").length > 0){
					editor.$(".layer_modal").remove();
				}
	        	//editor.$("body").append("<div class='layer layer_modal hide'><a href='#' class='apri_modal'>&nbsp;M&nbsp;</a></div>");
	        	mostra_layer_per_modal(editor);
	        
	        	/* carico da uno span nascosto in una variabile l'hash con i collegamenti tra campi e allegati */
	        	stringa_hash = parent.$("#hash_allegati_da_db").text().trim()
	        	if(stringa_hash != ""){
	        		hash_dati_valorizzati = JSON.parse(stringa_hash);
	        		parent.$("#hash_dati_allegati").val(stringa_hash);
	        	}
				
	        }

	        /* se ho configurato l'url degli eventi */
        	if($("#url_ws_eventi").length > 0 && $("#url_ws_interventi").length > 0){
	        	/* layer per mostrare finestra modale */
	        	if(editor.$(".layer_evento_intervento").length > 0){
					editor.$(".layer_evento_intervento").remove();
				}
	        	//editor.$("body").append("<div class='layer layer_modal hide'><a href='#' class='apri_modal'>&nbsp;M&nbsp;</a></div>");
	        	mostra_layer_per_evento_intervento(editor);
	        
	        	/* carico da uno span nascosto in una variabile l'hash con i collegamenti tra campi e eventi */
	        	stringa_hash_evento = parent.$("#hash_eventi_da_db").text().trim()
	        	if(stringa_hash_evento != ""){
	        		hash_dati_valorizzati_evento = JSON.parse(stringa_hash_evento);
	        		parent.$("#hash_dati_eventi").val(stringa_hash_evento);
	        	}
				
	        	/* carico da uno span nascosto in una variabile l'hash con i collegamenti tra campi e interventi */
	        	stringa_hash_intervento = parent.$("#hash_interventi_da_db").text().trim()
	        	if(stringa_hash_intervento != ""){
	        		hash_dati_valorizzati_intervento = JSON.parse(stringa_hash_intervento);
	        		parent.$("#hash_dati_interventi").val(stringa_hash_intervento);
	        	}

	        }

        	
    	}
	//chiudo init tinymce
    });
	

	/* popola la select dei tipo modulo in base al settore */
  	if($("select#settore").length > 0){ 		
  		$('select#settore').on('change', function() {
	  		var settore_selezionato = $("#settore option:selected").val();
	  		/* mando l'id del settore: 0 generico, da 1 in poi sono gli id della tabella */
	  		$C.remote('popola_select_tipo_modulo', {'settore' : settore_selezionato}, function(res){
					$("select#select_tipo_modulo").replaceWith(res);
			}, {dataType: 'html'});
  		});
  	}


	init_paginatore();





/* chiudo ready */
});