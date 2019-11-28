//
//                   Calendario vers. 1.0
//                     by Maurizio Mauri
//                   mailto: M.Mauri@mclink.it
//
//   

var xwidth = 220;
var xheight = 180;
var browser = document.layers ? 0 : document.all ? 1 : 2;


// colori
var backtable = '#e9ebeb';//var backtable = '#90C0FF';
var calendback = '#DDDDBB';
var lines = '#404040';
var bright = '#000000';
var festa = '#FD9139';

// ------ Non cambiare ----------
var mesi = new Array('Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
               'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre');

var days = new Array('Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa', 'Do');
var dd = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

var today = new Date();
var anno = today.getFullYear();
var mese = today.getMonth();
var currday = today.getDate();
var cellwidth = Math.floor(xwidth / 7);
xwidth = cellwidth * 7;
var xmese = 0;
var Aboutx = false;
var isOn = false;


function action(g, m , a) {
   function format(x) {
      return x > 9 ? x : "0" + x;
   }
   getId().showIt(false);

   if (tipo == 0) {
      source[0].value = format(g);
      source[1].value = format(m + 1);
      source[2].value = a;
   }
   else
      source.value = format(g) + '/' + format(m + 1) + '/' + a;
   source.onkeyup();
}

document.write('<style type="text/css">\n<!--');
document.write('.cal { font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: bold; color: #000099}');
document.write('.num { font-family: Arial, Helvetica, sans-serif; font-size: 12px; text-decoration:none}');
document.write('.mese {  font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold; color: #663300}');
document.write('.butt {  font-family: Arial, Helvetica, sans-serif; font-size: 10px}');
document.write('-->\n</style>');


function comparedate(d1, d2) {
   if (d1.getFullYear() == d2.getFullYear() && d1.getMonth() == d2.getMonth() && d1.getDate() == d2.getDate()) 
      return true;
   return false;
}

function pasqua(aa) {
   var xx = new Array(22, 22, 23, 23, 24, 24);
   var yy = new Array(2, 2, 3, 4, 5, 5);
   var a = aa % 19;
   var b = aa % 4;
   var c = aa % 7;

   var i = Math.floor(aa / 100) - 15;
   var x = xx[i], y = yy[i];
   var d = (19*a + x) % 30;
   var e = (2*b + 4*c + 6 * d + y) % 7;

   var p = 22 + d + e;

   var m = 300;
   if (p > 31) {
      m = 400;
      p = p - 31;
   }
   return m + p;
}


function isfesta(d) {
   var feste = new Array(0, 101, 106, 425, 501, 602, 815, 1101, 1208, 1225, 1226);

   if (d.getDay() == 0)
      return true;

   var pp = pasqua(d.getFullYear()) + 1;     // Pasquetta
      if (pp % 100 > 31)
         pp = 401;

   feste[0] = pp;
   for (var i = 0; i < feste.length; i++)
      if (Math.floor(feste[i] / 100) == d.getMonth() + 1) {
         var gg = feste[i] % 100;
         if (gg == d.getDate())
            return true;
      }

   return false;
}


function calendar(currday, mese, anno) {

   var dx = 2 - (new Date(anno, mese, 1)).getDay();
   if (dx == 2)
      dx = -5;
   var daysInMonth = dd[mese];

   if (mese == 1) {
      if (anno % 4 == 0 && anno % 100 != 0  || anno % 400 == 0)
         daysInMonth++
   }

   var tx = '<div class="mese">&nbsp;&nbsp;' + mesi[mese] + ' ' + anno +'</div>\n';
   tx += '<table width="' + xwidth + '" border="0" cellspacing="1" cellpadding="1">\n';
   tx += '<tr align="right">\n';

   for (var i = 0; i < 7; i++)
      tx += '<td width="' + cellwidth+ '" class="cal">' + days[i] + '&nbsp;</td>\n';

   tx += '</tr>\n';
   for (var j = 0; j < 6; j++) {
      tx += '<tr bgcolor="' + calendback + '" align="right">\n';
      for (var i = 0; i < 7; i++) {
         var bgstring = "";
         var fgcol = lines;
         var fgstring = '&nbsp;';

         if (dx > 0 && dx <= daysInMonth) {
            fgstring = dx;
            var ddx = new Date(anno, mese, dx);
            if (isfesta(ddx)) {
               bgstring = ' bgcolor="' + festa + '"';
            }
            if (comparedate(ddx, today)) {
               fgcol = bright;
               fgstring = '<b><u>' + dx + '</u></b>\n';
            }
            var tempdate = ddx.getDate() + ", " + ddx.getMonth() + ", " + ddx.getFullYear();
            fgstring = '<a href="javascript:action(' + tempdate + ')" class = "num"><font color="' + fgcol + '">' + fgstring + '</font></a>\n';
         }
         tx += '<td width="' + cellwidth + '" class="num"' + bgstring + '>\n';
         tx += fgstring;
         tx += '</td>\n';
         dx++;
      }
      tx += '</tr>\n';
   }
   tx += '</table>\n';

   return tx;
}


function dataframe(x) {
   var s = '<table width="' + xwidth + '" border="1" cellspacing="0" cellpadding="0" bgcolor="' + backtable + '">';
   s += '<tr>\n<td class="mese" valign="middle">';

   s += x; // calendar(currday, mese, anno);
   
   s += '</td></tr><tr><td>';
   s += '<form class="butt"><table width="100%" border="0" cellspacing="0" cellpadding="2">';
   s += '<tr>';
   //s += '<td><input type="button" name="about" value="about" class="butt" onclick="xabout()"></td>';
   s += '<td align="right"><input type="button" name="prev" value=" - " class="butt" onclick="altro_mese(-1)">';
  // s += '<input type="button" name="current" value="Default" class="butt" onclick="altro_mese(9)">';
   s += '<input type="button" name="next" value=" + " class="butt" onclick="altro_mese(1)"></td>';
   s += '</tr></form></table>';

   s += '</td>\n</tr>\n</table>';
   return s;
}


function altro_mese(x) {
   xmese += x;
   if (x == 9)
      xmese = 0;
   var xday = (xmese == 0)? currday : 99;
   var mm = (mese + xmese) % 12;

   while (mm < 0)
      mm += 12;

  //per default metto anno uguale alla data di sistema
   //anno = today.getFullYear();
   var s = dataframe(calendar(xday, mm, anno + Math.floor((mese + xmese) / 12)));
   writeLayer(s);
   aboutx = false;
}


function xabout() {
   if (aboutx)
      altro_mese(0);
   else {
      var aboutstr = '<table border="0" cellspacing="0" cellpadding="10" align="center"><tr><td nowrap class="mese">';
      aboutstr += '<br>Calendario vers. 1.0<br><br>';
      aboutstr += '&copy;2002 by Maurizio Mauri<br><br>';
      aboutstr += '<a href="mailto:M.Mauri@mclink.it" class="num">M.Mauri@mclink.it</a>';
      aboutstr += '</td></tr></table><br>';
   
      writeLayer(dataframe(aboutstr));
   }
   aboutx = true;
}


function getId() {
   switch (browser) {
      case 0:
         return document.layers.calframe;
      case 1:
         return eval('calframe');
      case 2:
         return document.getElementById("calframe");
   }
}


function moveLayer(xPos, yPos) {
   switch (browser) {
   case 0:
      this.left = xPos;
      this.top = yPos;
      break;
   case 1:
      this.style.pixelLeft = xPos;
      this.style.pixelTop = yPos;
     break;
   default:
      this.style.left = xPos;
      this.style.top = yPos;
   }
}


function createlayer() {
   var s;
   if (document.layers)
      s = '<layer id="calframe" visibility="hide" z-index=1>';
   else
      s = '<div id="calframe" style="position:absolute; visibility: hidden">';
   if (document.layers)
      s += '</layer>';
   else
      s += '</div>';

   document.writeln(s);
}


function showIt(on) {
   isOn = on;
   if (browser)
      this.style.visibility = (on) ? "visible" : "hidden";
   else
      this.visibility = (on) ? "show" : "hide"
  this.style['z-index'] = 10;
}


function writeLayer(s) {
   var id = getId();
   if (browser)
      id.innerHTML = s;
   else {
      id.document.open();
      id.document.write(s);
      id.document.close();
   }
}

function init() {
   var id = getId();
   id.moveLayer = moveLayer;
   id.showIt = showIt;
   id.showIt(false);
}


function showCalendar(d, xpos, ypos) {
   if (!isOn) {
           
      if (d) {
            anno = d.getFullYear();
            mese = d.getMonth();
            currday = d.getDate();
         } else {
            anno = today.getFullYear();
            mese = today.getMonth();
            currday = today.getDate();
      }
      if (arguments.length > 2) {
         var id = getId();
         id.moveLayer(xpos, ypos);
      }
      else
         getId().moveLayer(100, 100);

      xmese = 0;

      writeLayer(dataframe(calendar(currday, mese, anno)));
      
   }
   getId().showIt(!isOn);
   return getId();
}



createlayer(1);
onload = init;
