//SETTING UP OUR POPUP  
//0 means disabled; 1 means enabled;  
var popupStatus = 0;

function loadPopup(div){  
	//loads popup only if it is disabled 
	if(popupStatus==0){
		$(div).fadeIn("fast");  
		popupStatus = 1;  
	} 
}

function resetPopup(){  
	// $("#storico_ruoli").css("display","none");
	// $("#storico_sgravi").css("display","none");
	$("#legenda_riduzioni").css("display","none");
	$("#legenda_tariffe").css("display","none");
	popupStatus = 0;  
}

function centerPopup(div){  
	//request data for centering  
	$(div).css({  
		"position": "absolute",  
 		"top": '180px',  
 		"right": '200px'
 	});    
}

function initPopup(div){ 
	centerPopup(div);  
	loadPopup(div);
}

function reset() {
	$("#riduzione2Div").hide();
	$("#flagRiduzione").show();
    // for each select field on the page
    $("select").each( function(){
    // set its value to its first option
    	$(this).val( $("#" + $(this).attr("id") + " option:first").val() );
    });
	$("input").each( function(){
    // set its value to its first option
    	$(this).val('');
    });
}

jQuery.validator.addMethod( 
	  "selectNone", 
	  function(value, element) { 
	    if (element.value == "none") 
	    { 
	      return false; 
	    } 
	    else return true; 
	  }, 
	  "Seleziona una categoria" 
);
jQuery.validator.setDefaults({
	success: "valid"
});


$(document).ready(function(){  

	// CALCOLO TARSU ONLINE
	$("#calcoloTotaleDiv").hide();
	$("#riduzione2Div").hide();
	
	$("#flagRiduzione").click(function() {
		$("#riduzione2Div").show();
		$("#flagRiduzione").hide();
	});

        // nascondo tutti i div
	resetPopup();
	
	// Nasconde nella riduzione2 la selezione della riduzione1, cosi si evita doppia riduzione
	$("#riduzione1").change(function () {
			// resetto tutte le eventuali selezioni precedenti
			$('#riduzione2 option').removeClass("hide");
			// mi salvo l'indice della selezione delle riduzione 1 
			selezione = $('#riduzione1 option:selected').index();
			// aggiungo la class hide alla stessa selezione ma della riduzione 2
			$("#riduzione2 option").eq(selezione).addClass("hide");
	});
	
	$("a.button").click(function () {
		if($("#calcolo").valid()) {
			euroCategoria = $('#categoria').val();	
			categoria = $('#categoria :selected').text();
			mq = $('#mq').val();
			euroParziale = parseFloat(euroCategoria) * parseInt(mq);
			
			// RIDUZIONE1 non obbligatoria
			percRiduzione1 = $('#riduzione1').val();	
			riduzione1 = $('#riduzione1 :selected').text();
			
			if(percRiduzione1 == "none") {
				percRiduzione1 = 0;
				riduzione1 = "nessuna riduzione";
				riduzioneFinale = riduzione1 +" ("+ percRiduzione1 +"%)";
			}
			else {
				percentuale = (euroParziale * parseInt(percRiduzione1)) / 100;
				euroParziale = euroParziale - percentuale;
				riduzioneFinale = riduzione1 +" ("+ percRiduzione1 +"%)";
				
				// RIDUZIONE2 solo dopo la 1
				percRiduzione2 = $('#riduzione2').val();	
				riduzione2 = $('#riduzione2 :selected').text();
			
				if(percRiduzione2 != "none") {
					percentuale = (euroParziale * parseInt(percRiduzione2)) / 100;
					euroParziale = euroParziale - percentuale;
					riduzioneFinale = riduzioneFinale +" / "+ riduzione2 +" ("+ percRiduzione2 +"%)";
				}
			}

			// Processati tutti i dati li inserisce nella tabella di destinazione
			$("<tr><td>"+categoria+"</td><td>"+riduzioneFinale+"</td><td>"+mq+"</td><td class='quantity'>"+euroParziale.toFixed(2)+"</td></tr>" ).insertBefore($("#calcoloTotale .totale"));

			var totals = 0;
			$(".quantity").each(function() {
				euro = parseFloat($(this).text());
				totals += euro;
			});
            // EDIT: aggiungo 15% al totalone
            totals *= 1.15 ;
            // Fine EDIT
			$(".totalone").text(totals.toFixed(2));
			$("#calcoloTotaleDiv").fadeIn('slow');

			// dopo i calcoli resetto la situazione iniziale
			reset();
	        return true;
	    }
        return false;
	});
	
 /*	
	$("#view_storico_ruoli").click(function(){  
		initPopup('#storico_ruoli');
	});
	$("#view_storico_sgravi").click(function(){  
		initPopup('#storico_sgravi');
	});
*/

	$("#view_legenda_riduzioni").click(function(){  
		initPopup('#legenda_riduzioni');
	});
	$("#view_legenda_tariffe").click(function(){  
		initPopup('#legenda_tariffe');
	});
	
	// il close nasconde tutti i div
	$(".popup_close").click(function(){  
		resetPopup();  
	});
	
        if($("#calcolo").length>0)
	{
		$("#calcolo").validate({ 
	        rules: { 
        	  mq: { 
				required: true,
				digits: true
			  }, 
        	  categoria: { 
	            selectNone: true 
        	  }, 
	          comment: { 
        	    required: true 
	          } 
        	}, 
	        messages: { 
        	  mq: "Metratura non valida" 
       	 	} 
      		});
	}
});
