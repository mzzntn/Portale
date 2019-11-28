jQuery(function($){$.datepicker.regional['it'].dayNames=['Domenica','Luned&#236;','Marted&#236;','Mercoled&#236;','Gioved&#236;','Venerd&#236;','Sabato'];$.datepicker.setDefaults($.datepicker.regional['it']);
});

$(document).ready(function() {

function getDisplay() {
  var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
  var display = [ 1, 2 ];
  if(w<768){display = [ 2, 1 ];}
  console.log("w "+w+" display "+display);
  return display;
}

$.datepicker.setDefaults($.datepicker.regional["it"]);
$( "#giorno_appuntamento" ).datepicker(
{ 
  altFormat: "yy-mm-dd" ,
  minDate: 0,
  maxDate: maxDate,
  yearRange: "+0:+2",
  numberOfMonths: getDisplay(),
  defaultDate: get_default_date(new Date(giornoAppuntamento)),
  onSelect: function(date) 
  {
    $("#giorno").html($.datepicker.formatDate('DD d MM yy', $(this).datepicker('getDate')));
    $('#giorno_appuntamento_hid').val($(this).datepicker('getDate').getTime());
    
    $('#form_appuntamento').submit();
  },
  beforeShowDay: function(date) 
  {
    var open = false;
    var day = date.getDay();
    if(typeof(openingDays[day]) !== 'undefined')
    {
      if(jQuery.inArray(date.getTime()+"", closingDays ) > -1)
      {
	open = false;
      }
      else
      {
	open = true;
      }
    }
    else
    {
      open = false;
    }
    
    return [open];
  }
});

$("#tipo_appuntamento").change(function() 
{
   updateHours();
   $("#tipo_appuntamento_hidden").val($(this).val());
});

$('input[type=radio][name=operatore_appuntamento_visible]').change(function() 
{
  $("#operatore_appuntamento_hidden").val($(this).val());
  attendi();
  $('#form_appuntamento').submit();
});

updateHours();
$("#tipo_appuntamento_hidden").val($("#tipo_appuntamento").val());
$("#operatore_appuntamento_hidden").val($('input[type=radio][name=operatore_appuntamento_visible]:checked').val());
$("#orario_appuntamento_hidden").val($('input[type=radio][name=orario_appuntamento_visible]:checked').val());
$('#giorno_appuntamento_hidden').val($( "#giorno_appuntamento" ).datepicker('getDate').getTime());
});

function get_default_date(selectedDate) {
  console.log("getting default date");
  console.log("selectedDate is "+selectedDate);
  console.log("maxMonths is "+maxMonths);
  var date = new Date();
  var today = new Date();
  var lastAvailable = new Date();
  lastAvailable.setHours(0);
  lastAvailable.setMinutes(0);
  lastAvailable.setSeconds(0);
  lastAvailable.setMilliseconds(0);
  lastAvailable.setMonth(lastAvailable.getMonth() + maxMonths);
//   lastAvailable.setDate(1);
//   lastAvailable.setMonth(lastAvailable.getMonth()+parseInt(maxMonths)+1);
//   lastAvailable.setDate(lastAvailable.getDate() - 1);
//   lastAvailable.setFullYear(today.getFullYear());
  console.log("lastAvailable date is "+lastAvailable);
  console.log("default date is "+date);
  console.log("today is "+today);
  var open = false;
  if(selectedDate.getTime() > date.getTime()) {date = new Date(selectedDate);}
  if(date>lastAvailable) {
    console.log("date was past last available day, reset to "+today);
    date = today;
  }
  date.setHours(0);
  date.setMinutes(0);
  date.setSeconds(0);
  date.setMilliseconds(0);
  var day = date.getDay();  
  var returnDate = false;
  var counter = 0;
  while(!returnDate && counter < 30*maxMonths) {    
    var notPast = date>=today;
    overMaxDate = date>lastAvailable;
    var workingDay = typeof(openingDays[day]) !== 'undefined';
    var closingDay = jQuery.inArray(date.getTime()+"", closingDays )>-1;
    console.log("day: "+day+" - notPast: "+notPast+" - overMaxDate: "+overMaxDate+" - workingDay: "+workingDay+" - closingDay: "+closingDay+"");
    if(notPast && workingDay && !closingDay)
    {
      returnDate = date;
      console.log("date "+date+" is valid, returning");
    }
    else {
      console.log("date "+date+" is invalid, adding a day");
      date.setDate(date.getDate() + 1);
      day = date.getDay();
    }
    counter++;
  }
  console.log("selectedDate is "+giornoAppuntamento+" and date is "+date.getTime()); 
//   debugger;
  if(giornoAppuntamento && giornoAppuntamento<date.getTime() && date.getTime()!=giornoAppuntamento) {
    $('#giorno_appuntamento_hid').val(date.getTime());   
    console.log("form should submit now"); 
    attendi();
    $('#form_appuntamento').submit();
  }
  else {
    console.log("date is fine, don't submit form");
  }
  
  return date;
}

function updateHours()
{
  $("#giorno").html($.datepicker.formatDate('DD d MM yy', $("#giorno_appuntamento").datepicker('getDate')));
  var durataAppuntamento = typeDuration[$("#tipo_appuntamento").val()];
  var id = "#orario_appuntamento_div";
  $(id).empty();
  var giornata = $( "#giorno_appuntamento" ).datepicker('getDate');
  for (var fascia in openingDays[giornata.getDay()]) {
    buildHourSelect(id, fascia, durataAppuntamento,giornata);
  }
}

function buildHourSelect(id, periodo, durataAppuntamento, giornata)
{
  var day = giornata.getDay();
//   console.log("day "+day+" is an opening day: ["+openingDays[day]+"]");
  var openToday = typeof(openingDays[day]) !== 'undefined' && 
		  typeof(openingDays[day][periodo]) !== 'undefined';
		  
  if(openToday && jQuery.inArray(giornata.getTime()+"", closingDays ) > -1) {
    openToday = false;
  }
  
  if(openToday)
  {
    var apertura = new Date(giornata.getTime());
    apertura.setHours(openingDays[day][periodo]["oH"],openingDays[day][periodo]["oM"],0,0);
    var chiusura = new Date(giornata.getTime());
    chiusura.setHours(openingDays[day][periodo]["cH"],openingDays[day][periodo]["cM"],0,0);
      
    var first = typeof($('input[type=radio][name=orario_appuntamento_visible]:checked')[0]) === 'undefined';
    while(apertura<chiusura)
    {
      var stringMinutes = apertura.getMinutes();
      var durataMs = durataAppuntamento*60000;
      var durataMinimaMs = durataMinima*60000;
      if(stringMinutes<10){stringMinutes = "0"+stringMinutes;}
      var stringOrario = apertura.getHours()+":"+stringMinutes;      
      var fineAppuntamento = apertura.getTime()+durataMs;
//       console.log("durata: "+durataMs);
//       console.log("fine: "+fineAppuntamento);
//       console.log("fine > chiusura? "+(fineAppuntamento<=chiusura.getTime()));
      if(!isBooked(apertura.getTime(), durataMs) && (fineAppuntamento<=chiusura.getTime()) ) {
        // the chosen time is free to book!
        $(id).append("<input type='radio' name='orario_appuntamento_visible' value='"+stringOrario+"'> "+stringOrario+"<br>");
        apertura = new Date(apertura.getTime() + durataMs);
//         console.log("apertura: "+apertura);
//         console.log("chiusura: "+chiusura);
      }
      else {
        // let's shift everything a bit forward and recheck	
        var testDate = new Date(apertura.getTime() + durataMinimaMs);
        var oreI = apertura.getHours();
        var minI = apertura.getMinutes();
        if(minI<10){minI = "0"+minI;}
        var oreF = testDate.getHours();
        var minF = testDate.getMinutes();
        if(minF<10){minF = "0"+minF;}
        console.log("prenotato dalle "+oreI+":"+minI+" alle "+oreF+":"+minF);
        if(mostraPrenotati)
        {
          if(isBooked(apertura.getTime(), durataMinimaMs)) {
            $(id).append("prenotato dalle "+oreI+":"+minI+" alle "+oreF+":"+minF+"<br>");
          }
          else {
            $(id).append("libero dalle "+oreI+":"+minI+" alle "+oreF+":"+minF+" ma troppo breve per questo tipo di appuntamento<br>");
          }
        }
        apertura = new Date(apertura.getTime() + durataMinimaMs);
//         console.log("apertura: "+apertura);
//         console.log("chiusura: "+chiusura);
      }	
    }
    $('input[type=radio][name=orario_appuntamento_visible]').each(function(){
      $(this).change(function() 
      {
	$("#orario_appuntamento_hidden").val($(this).val());
      });
    });
    $('input[type=radio][name=orario_appuntamento_visible]').first().attr('checked', true);
  }
  
  var hasHours = typeof($('input[type=radio][name=orario_appuntamento_visible]:checked')[0]) !== 'undefined';
  var hasAlert = typeof($("#avvisoOrario").html()) !== 'undefined';
  
  if(hasHours)
  {
    $("#avvisoOrario").remove();
    $("#richiediSubmit").removeAttr('disabled');
  }
  else if(!hasAlert)
  {
      $(id).append("<i id='avvisoOrario'>Appuntamento non disponibile in questa data per la tipologia scelta. Si prega di selezionare una data diversa.</i>");
      $("#richiediSubmit").attr('disabled', true);
  }
}

function isBooked(timestamp, duration)
{
  booked = false;
  for (var i in bookedHours)
  {
    if(
      (timestamp>=bookedHours[i]["inizio"] && timestamp<bookedHours[i]["fine"]) || 
      ((timestamp+duration)>bookedHours[i]["inizio"] && (timestamp+duration)<=bookedHours[i]["fine"]) ||
      (bookedHours[i]["inizio"]>timestamp && bookedHours[i]["fine"]<(timestamp+duration))
    )
    {
      booked = bookedHours[i]["fine"]-bookedHours[i]["inizio"];
      break;
    }
  }
  return booked;
}

function attendi() {  
  var w = $( window ).height();
  var b = $(".modal-dialog").height();
  // should not be (w-h)/2
  var h = (w-b)/2;
  var margin = h+"px";
  $("body, *").css("cursor","wait");
  bootbox.hideAll();
  bootbox.dialog({ 
    message: '<br><br><div class="text-center"><i class="fa fa-2x fa-spin fa-spinner"></i> Attendi...</div><br><br>', 
    closeButton: false,
    size: 'small'
  }).find(".modal-dialog").css({'margin-top': margin});
}
