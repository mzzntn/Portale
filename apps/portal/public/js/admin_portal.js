

$(document).ready(function(){
    /* disabilito campi per tracciatura federa */
    $("#admin-switcher-portal_utentefederaemiliaromagna .form form .fields input").attr('readonly','readonly').attr('disabled','disabled');
    $("#admin-switcher-portal_utentefederaemiliaromagna .form form .fields textarea").attr('readonly','readonly').attr('disabled','disabled');
    
    $("#admin-switcher-portal_gdpr-form-titolo").attr('readonly','readonly');
	$("#admin-switcher-portal_gdpr input[name$='[portal_gdpr][delete]']").hide();
    $("#admin-switcher-portal_gdpr input[name$='submit_and_stay]']").hide();
	$("#admin-switcher-portal_gdpr input[name$='submit_and_new]']").hide();	


	var lista_pulsanti_editor = 'undo redo | bold italic | bullist | alignleft aligncenter alignright alignjustify | fullscreen | pagebreak | code | visualblocks | link ';

	tinymce.init({
    	//language: 'it',
    	/* uso lo skin su CDN per problemi nell'iframe che va a scaricare i css e non usa il file compresso da spider */
    	skin_url: '//cdnjs.cloudflare.com/ajax/libs/tinymce/4.3.7/skins/lightgray',
        selector: '#admin-switcher-portal_gdpr-form-informativa , #admin-switcher-portal_gdpr-form-autorizzazione',
        content_css : ['//cdnjs.cloudflare.com/ajax/libs/tinymce/4.3.7/plugins/visualblocks/css/visualblocks.css' ],
        height: 400,
        width: 1024,
        fontsize_formats: "8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 22pt 26pt 36pt",
        remove_trailing_brs: false,
        //plugins : 'advlist autolink link image lists charmap print preview',
        //fixed_toolbar_container: '#mytoolbar'   //da provare, toolbar fixed
        plugins: 'code fullpage hr fullscreen table nonbreaking importcss paste template advlist pagebreak visualblocks link',
        table_toolbar: "tableprops tabledelete tablecellprops tablemergecells tablesplitcells | tableinsertrowbefore tableinsertrowafter tabledeleterow | tablerowprops | tableinsertcolbefore tableinsertcolafter tabledeletecol",
        menubar: 'edit | view | table | format | insert ',
        toolbar: lista_pulsanti_editor,
        link_context_toolbar: true,
        statusbar: true,
        pagebreak_separator: "<div class='break evidenzia_bordo width_full'><!-- pagebreak --></div>",
        //br_in_pre: false,
        pagebreak_split_block: true,
        forced_root_block : false,      // non racchiude tutto in paragrafi, con false mette br
        importcss_append: true,
        //importcss_selector_filter: ".input_"
		/* proprietà da mantenere quando copio da word */
		//paste_retain_style_properties: "color font-size",
		//paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,h4,h5,h6,table,tr,td",
		style_formats_merge: true,
		code_dialog_height: $(window).height()-220,
		code_dialog_width: $(window).width()-220,

    })

})

