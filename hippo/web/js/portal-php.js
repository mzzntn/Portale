/*
 * Il codice contenuto in questo file js viene eseguito
 * su ogni pagina di portal
 * 
 */

$(document).ready(function() {

  $("form").append('<input type="hidden" name="CSRF" value="'+openweb_csrf+'">');
  $("a").each(function(){
    if($(this).prop("href")!="" && $(this).prop("href").includes(".php") && !$(this).prop("href").includes(".php?")) {
      $(this).prop("href", $(this).prop("href")+"?");
    }
    if($(this).prop("href")!="" && !$(this).prop("href").includes("javascript:") && $(this).prop("href").includes("?") && !$(this).prop("href").includes("CSRF")) {
      $(this).prop("href", $(this).prop("href")+"&CSRF="+openweb_csrf);
    }
  });

  /* =============================================================
 * bootstrap-typeahead.js v2.0.1
 * http://twitter.github.com/bootstrap/javascript.html#typeahead
 * =============================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================ */

!function( $ ){

  "use strict"

  var Typeahead = function ( element, options ) {
    this.$element = $(element)
    this.$keyField = this.$element.data( "key-field" )!=""?this.$element.nextAll("#"+this.$element.data( "key-field" )):{};
    this.options = $.extend({}, $.fn.typeahead.defaults, options)
    this.matcher = this.options.matcher || this.matcher
    this.sorter = this.options.sorter || this.sorter
    this.highlighter = this.options.highlighter || this.highlighter
    this.$menu = $(this.options.menu).appendTo('body')
    this.source = this.options.source
    this.keys = this.options.keys
    this.shown = false
    this.listen()
  }

  Typeahead.prototype = {

    constructor: Typeahead

  , select: function () {
      var val = this.$menu.find('.active').attr('data-value')
      var index = $.inArray(val, this.source);
      if (index < 0) {
	this.$element.val("");
	if(this.$keyField.length) { this.$keyField.val(""); }
      }
      else {
	this.$element.val(val);
	if(this.$keyField.length) { this.$keyField.val(this.keys[index]); }
      }
      
      return this.hide()
    }

  , show: function () {
      var pos = $.extend({}, this.$element.offset(), {
        height: this.$element[0].offsetHeight
      })

      this.$menu.css({
        top: pos.top + pos.height
      , left: pos.left
      })

      this.$menu.show()
      this.shown = true
      return this
    }

  , hide: function () {
      this.$menu.hide()
      this.shown = false
      return this
    }

  , lookup: function (event) {
      var that = this
        , items
        , q

      this.query = this.$element.val()

      if (!this.query) {
        return this.shown ? this.hide() : this
      }

      items = $.grep(this.source, function (item) {
        if (that.matcher(item)) return item
      })

      items = this.sorter(items)

      if (!items.length) {
        return this.shown ? this.hide() : this
      }

      return this.render(items.slice(0, this.options.items)).show()
    }

  , matcher: function (item) {
      return ~item.toLowerCase().indexOf(this.query.toLowerCase())
    }

  , sorter: function (items) {
      var beginswith = []
        , caseSensitive = []
        , caseInsensitive = []
        , item

      while (item = items.shift()) {
        if (!item.toLowerCase().indexOf(this.query.toLowerCase())) beginswith.push(item)
        else if (~item.indexOf(this.query)) caseSensitive.push(item)
        else caseInsensitive.push(item)
      }

      return beginswith.concat(caseSensitive, caseInsensitive)
    }

  , highlighter: function (item) {
      return item.replace(new RegExp('(' + this.query + ')', 'ig'), function ($1, match) {
        return '<strong>' + match + '</strong>'
      })
    }

  , render: function (items) {
      var that = this

      items = $(items).map(function (i, item) {
        i = $(that.options.item).attr('data-value', item)
        i.find('a').html(that.highlighter(item))
        return i[0]
      })

      items.first().addClass('active')
      this.$menu.html(items)
      return this
    }

  , next: function (event) {
      var active = this.$menu.find('.active').removeClass('active')
        , next = active.next()

      if (!next.length) {
        next = $(this.$menu.find('li')[0])
      }

      next.addClass('active')
    }

  , prev: function (event) {
      var active = this.$menu.find('.active').removeClass('active')
        , prev = active.prev()

      if (!prev.length) {
        prev = this.$menu.find('li').last()
      }

      prev.addClass('active')
    }

  , listen: function () {
      this.$element
        .on('blur',     $.proxy(this.blur, this))
        .on('keypress', $.proxy(this.keypress, this))
        .on('keyup',    $.proxy(this.keyup, this))

      if ($.browser.webkit || $.browser.msie) {
        this.$element.on('keydown', $.proxy(this.keypress, this))
      }

      this.$menu
        .on('click', $.proxy(this.click, this))
        .on('mouseenter', 'li', $.proxy(this.mouseenter, this))
    }

  , keyup: function (e) {

      switch(e.keyCode) {
        case 40: // down arrow
        case 38: // up arrow
	  e.stopPropagation()
	  e.preventDefault()
          break

        case 9: // tab
	  if (!this.shown) return
	  this.select()
	  break;
        case 13: // enter
	  e.stopPropagation()
	  e.preventDefault()
          if (!this.shown) return
          this.select()
          break

        case 27: // escape
	  e.stopPropagation()
	  e.preventDefault()
          this.hide()
          break

        default:
	  e.stopPropagation()
	  e.preventDefault()
          this.lookup()
      }

  }

  , keypress: function (e) {
      e.stopPropagation()
      if (!this.shown) return

      switch(e.keyCode) {
        case 9: // tab
        case 13: // enter
        case 27: // escape
          e.preventDefault()
          break

        case 38: // up arrow
          e.preventDefault()
          this.prev()
          break

        case 40: // down arrow
          e.preventDefault()
          this.next()
          break
      }
    }

  , blur: function (e) {	    
      var val = this.$element.val();
      var index = $.inArray(val, this.source);
      if (index < 0) {
	this.$element.val("");
	if(this.$keyField.length) { this.$keyField.val(""); }
      }
      var that = this
      e.stopPropagation()
      e.preventDefault()
      setTimeout(function () { that.hide() }, 150)
    }

  , click: function (e) {
      e.stopPropagation()
      e.preventDefault()
      this.select()
    }

  , mouseenter: function (e) {
      this.$menu.find('.active').removeClass('active')
      $(e.currentTarget).addClass('active')
    }

  }


  /* TYPEAHEAD PLUGIN DEFINITION
   * =========================== */

  $.fn.typeahead = function ( option ) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('typeahead')
        , options = typeof option == 'object' && option
      if (!data) $this.data('typeahead', (data = new Typeahead(this, options)))
      if (typeof option == 'string') data[option]()
    })
  }

  $.fn.typeahead.defaults = {
    source: []
  , items: 8
  , menu: '<ul class="typeahead dropdown-menu"></ul>'
  , item: '<li><a href="#"></a></li>'
  }

  $.fn.typeahead.Constructor = Typeahead


 /* TYPEAHEAD DATA-API
  * ================== */

  $(function () {
    $('body').on('focus.typeahead.data-api', '[data-provide="typeahead"]', function (e) {
      var $this = $(this)
      if ($this.data('typeahead')) return
      e.preventDefault()
      $this.typeahead($this.data())
    })
  })

}( window.jQuery );
  /*
   * Ogni elemento con classe form-hidable verrà dotato di un pulsante per mostrare il form (sopra lo stesso)
   * ed un pulsante per nascondere il form (all'interno dello stesso, nel div con classe .buttons-row)
   * 
   * La classe form-hidable viene aggiunta automaticamente nel template dei widget quando $W->config['hidable'] == true
   */
  $(".form-hidable").each(function() {
      $form = $(this);
      $form.find(".buttons-row").append('<input type="button" class="btn btn-default btn-hide mt10" value="Nascondi ricerca">');
      $form.before('<div class="col-xs-12 mb15"><input type="button" class="btn btn-default btn-show" value="Ricerca"></div>');
      
      $hideButton = $form.find(".btn-hide");
      $showButton = $form.prev().find(".btn-show");
      
      $hideButton.click(function() {$form.hide(); $showButton.show();});
      $showButton.click(function() {$form.show(); $showButton.hide();});

      var searchActive = false;
      $form.find("input,select,textarea").not(':button').not(':hidden').not(':submit').each(function(){
	if($(this).val()!="") {
	  searchActive = true;
	}
      });      

      if(!searchActive){$form.hide();}
      else {$showButton.hide();}
    });
  $(".nav-pills a").click(function(e) {
      console.log($(this).text());
      $("html").scrollTop( $($(this).attr("href")).offset().top-116 );
  });
  
  if($('input.datepicker').length > 0) {
    $('input.datepicker').each(function(){
      var value = $(this).val();
      $(this).datepicker({
	"dateFormat": "dd/mm/yy",
	constrainInput: true,
	dayNames: [ "Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato" ],
	dayNamesShort: [ "Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab" ],
	dayNamesMin: [ "Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa" ],
	monthNames: [ "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre" ],
	monthNamesShort: [ "Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic" ],
	firstDay: 1,
	setDate: value,
      });      
    });
  }
  
  if($('#current_page').length > 0) {
    var current_page = $('#current_page').val();
    if(!page && current_page) {page=current_page;}
    var show_per_page = parseInt($('body').find("#items_per_page").val());
    var number_of_items = parseInt($('body').find('.pagination_content .paginated_element').size());
    var last_page_number = Math.ceil(number_of_items/show_per_page);
    
    
    function paginatorListeners($element) {
      var ulContent = $element.find('ul').html();
      if(ulContent) {
	$element.html(ulContent);
      }
      $element.find('li').each(function() {
	$(this).find('a').click(function() {
	  var href = $(this).attr('href');
	  var targetPage = 1;
	  if(href.indexOf("go_to_page") > -1) {
	    targetPage = href.replace("javascript:go_to_page(","").replace(", 'body')","").replace(",'body')","");
	  }
	  else if(href.indexOf("first_page") > -1) {
	    targetPage = 1;
	  }
	  else if(href.indexOf("previous") > -1) {
	    targetPage = parseInt($('body').find('#current_page').val())-1;
	  }
	  else if(href.indexOf("next") > -1) {
	    targetPage = parseInt($('body').find('#current_page').val())+1;
	  }
	  else if(href.indexOf("last_page") > -1) {
	    targetPage = last_page_number;
	  }
	  if(targetPage<1) {targetPage=1;}
	  console.log("setting target to "+targetPage);
	  $.post( paginatorPOSTurl, { paginatorPOSTparam: targetPage } );
	});
      });
    }
    
    if(number_of_items>show_per_page) {
      
      init_paginatore();
      
      paginatorListeners($('#paginator_div'));
      paginatorListeners($('.lista_pagine'));
      $('#paginator_div').bind("DOMSubtreeModified",function(event){
	paginatorListeners($(this));
	paginatorListeners($('.lista_pagine'));
	current_page = $('#current_page').val();
      });
      
      if (page && page>1) 
      { 
	go_to_page(page, 'body'); 
      } 
    } else {
      $('#paginator_div').remove();
    }
    
  }

  $('.row_linked').on('click', 'tr', function(){
    if(typeof($(this).find('a').attr('href'))!="undefined") {
      console.log("Going to "+$(this).find('a').attr('href'));
      window.location = $(this).find('a').attr('href');
    }
  });
  /*$('.row_linked tbody tr').each(function() {
    $(this).css( "cursor", "pointer" );
    $(this).click( function() {
        window.location = $(this).find('a').attr('href');
    }).hover( function() {
        $(this).toggleClass('hover');
    });
  });*/

  
  var $labels = $("label:contains('*')" );
  if($labels.length) {
    var forms = [];
    $labels.each(function(){
      $(this).next("div").find("input, select, textarea").attr("required", true);
      var noval = $(this).closest("form").attr("novalidate");
      if(typeof noval === typeof undefined || noval === false || noval == "force") {
	$(this).closest("form").attr("novalidate", true);
	forms.push($(this).closest("form"));
      }
    });
    for (var i in forms) {
      initValidation(forms[i]);
    }
  }
  
  $("#menu_responsive_laterale").removeAttr("style");
});

var typeaheads = {};
function enableTypeahead($element, keys, values)
{
  typeaheads[$element.prop("id")] = $element;
  $elementTypeahead = $element.clone(true);
  $elementTypeahead.insertAfter($element);
  $element.remove();
  $elementTypeahead.val("");
  $elementTypeahead.attr( 'autocomplete', 'off' );
  
  $elementTypeahead.typeahead({source: values, keys: keys});
  $elementTypeahead.data("has-typeahead", true);
  console.log("typeahead enabled on "+$element.attr("id"));
}

function disableTypeahead($element) {
  if(typeof(typeaheads[$element.prop("id")])!="undefined") {
    typeaheads[$element.prop("id")].insertAfter($element);
    $element.remove();    
    delete typeaheads[$element.prop("id")];
    $elementTypeahead.removeData("has-typeahead");
    console.log("typeahead disabled on "+$element.attr("id"));
  }
}

function stradario($input, comune) {
  console.log("stradario on "+$input.attr("id")+" for comune "+comune);
  disableTypeahead($input);
  var $wait = $('#'+$input.attr("id")+'_wait');
  var $container = $input.parent();
  if(!$wait.length) {
    $wait = $("<input>");
    $wait.attr("type", "text").attr("id", "referente_comuneResidenza_via_wait").val("caricamento stradario...").attr("disabled","true").addClass("form-control");
    $container.append($wait);
  }
  $wait.hide();
  if(comune!="") {
    $input.hide();
    $wait.show();
    $.get( "get_indirizzi.php?comune="+comune, function( data ) {
      $wait.hide();
      $input.show();
      var res = jQuery.parseJSON(data.replace(/'/g,'"'));
      if(res.length>0) {
	var ids = [];
	var descrizioni = [];
	for (var i in res) {
	  ids.push(res[i].codice);
	  descrizioni.push(decodeEntities(res[i].descrizione));
	}
	enableTypeahead($input, ids, descrizioni);
	if($input.val()){$('#'+$input.attr("id")).val($input.val());}
      }
      else {
	disableTypeahead($input);
      }
    });
  }
  else {
    $wait.hide();
    $input.show();
  }
}

var decodeEntities = (function() {
  // this prevents any overhead from creating the object each time
  var element = document.createElement('div');

  function decodeHTMLEntities (str) {
    if(str && typeof str === 'string') {
      // strip script/html tags
      str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
      str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
      element.innerHTML = str;
      str = element.textContent;
      element.textContent = '';
    }

    return str;
  }

  return decodeHTMLEntities;
})();

function initValidation($form, callback) {
  if($form && $form.length) {
    console.log("initValidation on "+$form.attr("id"));
    console.log("received callback ["+callback+"]");
    savedCallback = callback;
    $form.find('input, select, textarea').blur(function(){
      // valida al blur solo se non è datepicker
      if(Object.keys($(this).data()).indexOf("datepicker")===-1){validate($(this));}
    });
    $form.find('input[type=radio]').change(function(){validate($(this));});
    $form.find('input, select').keypress(function (e) {
      if (e.which == 13) {
        return false;
      }
    });
    
    $form.submit(function( event, triggered ) {
      event.stopImmediatePropagation(); // avoid double submission
      console.log("-----------------");
      console.log("form submitted");
      if(triggered) {
        console.log("form is triggered, return true without checking");
        $form.find("input[name='submit']").removeAttr("name"); // remove input with name="submit" in form, which overrides the native form.submit (programmatically submits the form) method that is being called internally in jQuery causing the form not to submit
        return true;
      }
      else if(validateForm($(this), savedCallback)) {
        console.log("callback is "+savedCallback);
        console.log("form is valid, return true");
        $form.find("input[name='submit']").removeAttr("name"); // remove input with name="submit" in form, which overrides the native form.submit (programmatically submits the form) method that is being called internally in jQuery causing the form not to submit
        return true;
      }
      else {
        console.log("form is not valid, return false");
        console.log("prevent default");
        event.preventDefault();
        return false;
      }
    });
  }
}

function validate($field) {
  if($field.next('.help-block').length>0) {$field.next('.help-block').remove();}
  var valid = false;
  if($field.data("validate") && $field.is(":visible")) {
    console.log("calling validatePhpType for field "+$field.attr("name"));
    valid = validatePhpType($field,$field.data("validate"));
  }
  else if($field.attr("required") && $field.is(":visible")) {
    if($field.attr("type")=="radio") {
      //if($field.parent().prev("td").find('.help-block').length>0) {$field.parent().prev("td").find('.help-block').remove();}
      /*var radios = $("[name="+$field.attr("name").replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" )+"]");
      for(var r in radios) {
        if($field.is(':checked')) { valid = true; }        
      }*/
      var checkedVal = $("input[name="+$field.attr("name").replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" )+"]:checked").val();
      valid = checkedVal == "1" || checkedVal == "0";
      if($field.parent().prop("tagName")=="TD") {
        if($field.parent().prev("td").find('.help-block').length<1) {$('<span class="help-block"></span>').appendTo($field.parent().prev("td"));}
	$field.parent().parent().toggleClass("has-error",!valid);
        $field.parent().parent().toggleClass("has-success",valid);
        if(!valid) {
          //$('<span class="help-block">&Egrave; obbligatorio specificare una risposta.</span>').appendTo($field.parent().prev("td"));
          $field.parent().prev("td").find(".help-block").html("&Egrave; obbligatorio specificare una risposta.");
        } else {
          $field.parent().prev("td").find(".help-block").html("Risposta impostata correttamente.");
        }
      } else {
	if($field.parent().prev("td").find('.help-block').length>0) {$field.parent().prev("td").find('.help-block').remove();}
        $field.parent().toggleClass("has-error",!valid);
        $field.parent().toggleClass("has-success",valid);
        if(!valid) {
          $('<span class="help-block">&Egrave; obbligatorio scegliere un\'opzione.</span>').insertAfter($field);
        }
      }
    } else {
      valid = $field.val()!="";
      if($field.next('.help-block').length>0) {$field.next('.help-block').remove();}
      $field.parent().toggleClass("has-error",!valid);
      $field.parent().toggleClass("has-success",valid);
      if(!valid) {
        $('<span class="help-block">Questo campo &egrave; obbligatorio.</span>').insertAfter($field);
      }
    }
  }
  else {
    valid = true;
    $field.parent().toggleClass("has-error",!valid);
    $field.parent().toggleClass("has-success",valid);
  }
  return valid;
}

function validatePhpType($field, type) {
  var value = $field.val().replace("C:\\fakepath\\","");
  var valid = false;
  var required = $field.attr("required");
  var minLength = $field.data("min-length");
  var maxLength = $field.data("max-length");
  var maxSize = $field.data("max-size");
  var fixedLength = $field.data("fixed-length");
  var notFuture = $field.data("not-future");
  var warning = $field.data("error-type")&&$field.data("error-type")=="warning";
  var message = "";
  
  if(required && value == "") {
    valid = false;
    warning = false;
    message = "&egrave; obbligatorio";
  }
  else if(required && value != "" && type=="money" && isNumber(value) && value.replace(".","").replace(",",".")<=0) {
    valid = false;
    warning = false;
    message = "dev'essere maggiore di 0";
  }
  else if(!required && value == "") {
    valid = true;
  }
  else if(value != "" && minLength && value.length<minLength)
  {
    valid = false;
    message = "deve contenere almeno "+minLength+" caratteri";
  }
  else if(value != "" && maxLength && value.length>maxLength)
  {
    valid = false;
    message = "non pu&ograve; contenere pi&ugrave; di "+maxLength+" caratteri";
  }
  else if(value != "" && fixedLength && value.length!=fixedLength)
  {
    valid = false;
    message = "deve contenere "+fixedLength+" caratteri";
  }
  else {
    switch(type) {
      case "piva":
	valid = /^\d{11}$/.test(value);
	message = "non contiene una Partita IVA valida";
	break;
      case "cf":
	valid = isCodiceFiscale(value, $('#'+$field.data('nascita-field')).val(), $('#'+$field.data('sesso-field')).val());
	if(warning && !valid && value.length!=11 && value.length!=16) {
	  warning = false;
	}
	message = "non contiene un codice fiscale valido";
	break;
      case "cforpiva":
        var validPiva = /^\d{11}$/.test(value);
        var validCF = isCodiceFiscale(value, $('#'+$field.data('nascita-field')).val(), $('#'+$field.data('sesso-field')).val());
        valid = validPiva || validCF;
        if(warning && !valid && value.length!=11 && value.length!=16) {
          warning = false;
        }
        message = "non contiene un codice fiscale o una p.iva valida";
        break;
      case "number":
        valid = /^\d+$/.test(value);
        message = "deve contenere un numero valido";
        break;
      case "alphanumeric":
        valid = /^[a-zA-Z0-9]+$/.test(value);
        message = "puo' contenere solo lettere e numeri";
        break;
      case "string":
        valid = /^[a-zA-Z0-9 \.\-&\u00C0-\u017F]+$/.test(value);
        message = "puo' contenere solo lettere, lettere accentate, numeri, spazi, . - e &";
        break;
      case "email":
        valid = emailIsValid(value);
        message = "deve contenere un indirizzo email valido";
        break;
      case "money":
        valid = isNumber(value);
        message = "deve contenere un importo valido";
        break;
      case "filesize":
        var notZeroSize = isNotZeroSize($field);
        if(!notZeroSize) {
          valid = false;
          message = "deve contenere un file di dimensioni maggiori di 0 byte";
        } else {
          if(maxSize) {
            valid = isBelowMaxSize($field, maxSize);
            if(!valid) {
              message = "contiene un file troppo grande. Le dimensioni massime consentite sono di "+((maxSize / 1048576).toFixed(0))+ "MB ("+maxSize+" byte)";
            }
          } else {
            valid = true;
          }
        }
        break;
      case "filename":
        var filenameOk = isFilename(value);
        var sizeOk = isNotZeroSize($field);
        valid = filenameOk && sizeOk;
        message = !sizeOk?"deve contenere un file di dimensioni maggiori di 0 byte":"pu&ograve; contenere solo un nome file composto da lettere non accentate, numeri, punti singoli, trattini ed underscore (trattino basso). Non vengono accettati caratteri accentati, simboli, doppi punti, spazi e caratteri speciali"
        break;
      case "date":
        valid = isDate(value, notFuture);
        message = "deve contenere una data valida";
        break;
      default:
        valid = true;
        break;
    }
  }

  $field.parent().toggleClass("has-success",valid);
  $field.parent().toggleClass("has-warning",!valid&&warning);
  $field.parent().toggleClass("has-error",!valid&&!warning);
  if(!valid) {
    $('<span class="help-block">Questo campo '+message+'.</span>').insertAfter($field);
  }
  if(warning&&!valid){valid = warning;}
  return valid;
}

// mi salvo qui callback, altrimenti diventa undefined tempo che facciamo il submit del form 
var savedCallback;
function validateForm($form, callback) {
  if(typeof callback === typeof undefined) { callback = false; }
  $form.find(".has-success").removeClass("has-success");
  $form.find(".has-warning").removeClass("has-warning");
  $form.find(".has-error").removeClass("has-error");
  var formIsValid = true;
  $form.find('input, select, textarea').each(function() {
    if(typeof($(this).attr("id")) != "undefined" && $(this).is(":visible")) {
      if(!validate($(this))){formIsValid=false;}
    }
  });
  var warnings = $form.find(".has-warning").size();
  console.log("Form is valid: "+formIsValid);
  console.log("Warnings: "+warnings);
 
  if(!formIsValid) {
    // se ci sono errori nel form interrompo l'esecuzione del submit ancora prima di verificare callback
    bootbox.alert("I dati inseriti contengono degli errori.", function(){
      var addOffset = 0;
      if($('#header_border_fixed').length) {
        addOffset = $('#header_border_fixed').height()+20;
      }
      var offset = $form.find(".has-error").first().length?$form.find(".has-error").first().offset().top-addOffset:0;
      $('html, body').animate({scrollTop: offset}, 200);
    })
    return false;
  } else {
    // nessun errore sul form, verifichiamo callback
    var callbackResult = {result: true, message: ""};
    console.log("check callback function "+callback);
    if(callback) { 
      console.log("callback function "+callback+" exists");
      callbackResult = callback();    
    } else {
      console.log("callback function "+callback+" is not a valid callback");
    }
    formIsValid = formIsValid && callbackResult.result;
    var message = callbackResult.message && callbackResult.message!=""?callbackResult.message:"Si &egrave; verificato un errore.";
    console.log("Callback result: "+callbackResult.result);
    if(!formIsValid && callbackResult.result===false) {
      console.log("Showing error message for !formIsValid (after callback)");
      bootbox.alert(message, function(){ }); 
      return false;
    }
    
    /*if(!formIsValid) {
      bootbox.alert("I dati inseriti contengono degli errori.", function(){ 
	var addOffset = 0;
	if($('#header_border_fixed').length) {
	  addOffset = $('#header_border_fixed').height()+20;
	}
        var offset = $form.find(".has-error").first().length?$form.find(".has-error").first().offset().top-addOffset:0;
	$('html, body').animate({scrollTop: offset}, 200);	
      });   
      return false;
    }*/
    else { 
      // nessun errore anche su callback, verifichiamo i warning
      console.log("Warnings: "+warnings);
      if(warnings) {
	console.log("Showing force dialog");
	bootbox.dialog({
	  message: "Attenzione: i dati inseriti non risultano corretti. Proseguire comunque?",
	  buttons: {
	    "force": {
	      label: "Forza inserimento",
	      className: "btn-default",
	      callback: function() { console.log("Force dialog closing and forcing"); $form.trigger('submit', true); }
	    },
	    "fix": {
	      label: "Modifica dati",
	      className: "btn-primary",
	      callback: function() { 
		console.log("Force dialog closing and staying");
		var addOffset = 0;
		if($('#header_border_fixed').length) {
		  addOffset = $('#header_border_fixed').height()+20;
		}
		$('html, body').animate({scrollTop: $form.find(".has-warning").first().offset().top-addOffset}, 200);  
	      }
	    }
	  }
	});	
	return false; // restituisco sempre false perchè bootbox non interrompe il processo in attesa di user input
	// eventualmente faccio il submit se l'utente decide di forzare
      }
      else {  return true; }
    }
  }
}

function isFilename(fileName)
{

  var fileNameSplit = fileName.split("\\");
  if(fileNameSplit.length<2)
  {
    fileNameSplit = fileName.split("/");
  }
  fileName = fileNameSplit[fileNameSplit.length-1];
  
  var regex = RegExp(/^[a-zA-Z_\.\-0-9]*$/);
  var regex2 = RegExp(/\.{2,}/);
  var fileOk = false;
  
  return regex.test(fileName)&&!regex2.test(fileName);
}

function isBelowMaxSize($field, maxSize) {
  var belowMaxSize = false;
  if(!window.FileReader) {
    // impossibile leggere il file
  }
  var input = $field[0];
  if (!input) {
    // impossibile leggere il file
  }
  else if (!input.files) {
    // impossibile leggere il file
  }
  else if (!input.files[0]) {
    // impossibile leggere il file
  }
  else {
    file = input.files[0];
    belowMaxSize = file.size<=maxSize;
  }
  return belowMaxSize;
}

function isNotZeroSize($field) {
  var notZeroSize = false;
  if(!window.FileReader) {
    notZeroSize = true;
  }
  var input = $field[0];
  if (!input) {
    notZeroSize = true;
  }
  else if (!input.files) {
    notZeroSize = true;
  }
  else if (!input.files[0]) {
    notZeroSize = true;
  }
  else {
    file = input.files[0];
    notZeroSize = file.size>0;
  }
  return notZeroSize;
}

function isCodiceFiscale(cf, dataNascita, sesso)
{
  var str = cf.replace(/\s/g,'').toUpperCase(); // converte in maiuscolo e rimuove spazi bianchi
  
  if(cf.length!=16) {
    return false;
  }
  
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
  var day = str.substr(9,2);
  // TODO: gestire anni di nascita > 1999?
  var year = '19'+str.substr(6,2);
  
  var female = false;  
  if(day > 31){day = day -40; female = true;}
  
  var months = 'ABCDEHLMPRST';  
  var month = months.indexOf(str.charAt(8));
  if(dataNascita)
  {
    var dateString = day+'/'+(month+1)+'/'+year;
    
    if(!isDate(dateString))
    {
      return false;
    }
  }
  
  // verifica corrispondenza date nascita
  var cfDate = new Date(year, month, day, 0,0,0,0);
  if(dataNascita)
  {
    var data = dataNascita.split('/');
    var birthDate = new Date(data[2], data[1]-1, data[0], 0, 0, 0, 0);
    
    if(cfDate.getTime() != birthDate.getTime())
    {
      return false;
    }
  }
  
  // verifica corrispondenza sesso
  if(!sesso || sesso == 'F' && female || sesso == 'M' && !female)
  {
    // ok
  }
  else
  {
    return false;
  }
  
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

function isNumber(n) {
  var nIT = n.replace(".","").replace(",",".");
  var numeroIT = !isNaN(parseFloat(nIT)) && isFinite(nIT);
  var numeroEN = !isNaN(parseFloat(n)) && isFinite(n);
  return (numeroIT && isFinite(numeroIT)) || (numeroEN && isFinite(numeroEN));
}

function emailIsValid(email)
{
  var isValid = false;
  var user, domain;
  var emailsplit = email.split('@');
  if(emailsplit.length == 2)
  {
    user = emailsplit[0];
    domain = emailsplit[1];
    if(domain.indexOf('.')>-1)
    {
      isValid = true;
    }
  }
  return isValid;
}

function isDate(dateString, notFuture)
{
  var data = dateString.split('/');
  var dataDate = new Date(data[2], data[1]-1, data[0], 0, 0, 0, 0);
  var nowDate = new Date();
  var dataMonth = data[1] - 1;
  var dataDateMonth = dataDate.getMonth();
  var validDay = data[0] == dataDate.getDate();
  var validMonth = data[1] - 1 == dataDate.getMonth();
  var validYear = data[2] == dataDate.getFullYear() && dataDate.getFullYear() >= 1900;
  return validDay && validMonth && validYear && (!notFuture || (notFuture && dataDate.getTime() < nowDate.getTime()));
}
