tinyMCEPopup.requireLangPack();

var InternalinkDialog = {
	init : function() {
		//var f = document.forms[0];

		// Get the selected contents as text and place it in the input
		//f.selected_text.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		
		// Inizializzazione Variabile per il Filtraggio
		myfilter = new filterlist(document.forms[0].link_id);
	},

    
	insert : function() {
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertLink', false, document.forms[0].link_id.options[document.forms[0].link_id.selectedIndex].value );
		tinyMCEPopup.close();
	},
	
	sort : function(stringa) {		
		myfilter.set(stringa);
	},
	reset : function() {
		myfilter.reset();
	},
	regexp_set : function(stringa) {
		myfilter.set(stringa);
	},
	regexp_clear : function() {
		myfilter.reset();
		document.forms[0].regexp.value='';
	}

};

tinyMCEPopup.onInit.add(InternalinkDialog.init, InternalinkDialog);