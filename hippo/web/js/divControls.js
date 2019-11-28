DEBUG_LEVEL = 3;
RESIZE_MARGIN = 10;

var openerInfo = new Object();
var imp = new Object();

var globalDivs = new Array();
var activeDivs = new Array();
var divEvents = new Array();
var tooltipDivs = new Array();
var currentTooltip;
var timeouts = new Array();
var mousePos = new Array();
window.iPopups = new Array();
window.iFrames = new Array();  //holds references to the content window of dynamically created iframes
var idCount = 0;
//var xmlHttp;
var xmlHttpRequests = new Array();
var xmlHttpHandlers = new Array();
window.currentIPopup = 0;

var _appDiv;


function makeDraggable(obj, handle){
  makeCool(obj);
  obj.grabbed = false;
  obj.grab = div_grab;
  obj.ungrab = div_ungrab;
  if (!handle) handle = obj;
  else handle.handleObj = obj;
  handle.onmousedown = obj.grab;
}

function makeResizable(obj, handle){
  obj = getObj(obj);
  makeCool(obj);
  obj.resizeGrabbed = false;
  obj.resizeGrab = div_resizeGrab;
  obj.resizeUngrab = div_resizeUngrab;
  obj.resizeCheck = div_resizeCheck;
  obj.resizeSetCursor = div_resizeSetCursor;
  if (!handle) handle = obj;
  else handle.handleObj = obj;
  handle.onmousedown = obj.resizeGrab;
  handle.onmousemove = obj.resizeSetCursor;
}


function followMouse(e){
  e = normalizeEvent(e);
  div = activeDivs.slide;
  normalizeDiv(div);
  //getObj('debug').innerHTML += '<br>'+e.target;
  //getObj('debug').innerHTML += '<br>'+e.pro.x+','+e.pro.y;
  //if (div.prevX && Math.abs(div.prevX - e.pro.x) > 100) return;
  div.prevX = e.pro.x;
  div.moveTo(div.grabStartX + (e.pro.x - div.mouseStartX), div.grabStartY + (e.pro.y - div.mouseStartY));
}

function resizeFollowMouse(e){
  e = normalizeEvent(e);
  div = activeDivs.resize;
  normalizeDiv(div);
  var width, height;
  //we have to use screenX/Y for it to work over different frames
  var dX = e.screenX - div.mouseStartX;
  var dY = e.screenY - div.mouseStartY;
  switch (div.resizeGrabbed){
    case 'l':
      div.style.width= div.resizeStartWidth - dX;
      div.moveTo(div.resizeStartX + dX, div.resizeStartY);
      break;
    case 'r':
      div.style.width = div.resizeStartWidth + dX;
      break;
    case 't':
      div.style.height = div.resizeStartHeight - dY;
      div.moveTo(div.resizeStartX, div.resizeStartY + dY);
      break;
    case 'b':
      div.style.height = div.resizeStartHeight + dY;
      break;
  }
  if (div.fixResize) div.fixResize();
}

function ungrab(e){
  activeDivs.slide.ungrab();
}

function resizeUngrab(e){
  activeDivs.resize.resizeUngrab();
}

function buildMenu(divId){
  var DHTML = (document.getElementById || document.all || document.layers);
  var div = getObj(divId);
}

function normalizeEvent(e){
  try{
    if (!e) var e = window.event;

    e.pro = new Array();
    if (e.pageX || e.pageY){
      e.pro.x = e.pageX;
      e.pro.y = e.pageY;
    }
    else if (e.clientX || e.clientY){
      e.pro.x = e.clientX + document.body.scrollLeft;
      e.pro.y = e.clientY + document.body.scrollTop;
    }
    if (e.srcElement) e.target = e.srcElement;
    if (e.relatedTarget) e.relTarg = e.relatedTarget;
  	else if (e.fromElement) e.relTarg = e.fromElement;
  	else if (e.toElement) e.relTarg = e.toElement;
  }
  catch(exc){
  } 
  return e;
}

function normalizeDiv(obj){
  var originalObj = obj;
  if (obj.calculateXY || (!obj.x && !obj.y) || (obj.x == 'undefined' || obj.y == 'undefined')){
    originalObj.calculateXY = true;
    if (obj.offsetTop){      //it would be more correct to test for offsetParent, but it
      var originalObj = obj; //misteriously fails in my IE...
      var left = 0;
      var top = 0;
      while (obj.offsetParent){
        left += obj.offsetLeft;
        top += obj.offsetTop;
        obj = obj.offsetParent;
      }
      originalObj.x = left;
      originalObj.y = top;
    }
  }
  if (!obj.id) obj.id = ++idCount;
}

function div_resizeSetCursor(e){
  var resize = this.resizeCheck(e);
  switch (resize){
    case 'l':
      this.style.cursor = 'w-resize';
      break;
    case 'r':
      this.style.cursor = 'e-resize';
      break;
    case 't':
      this.style.cursor = 'n-resize';
      break;
    case 'b':
      this.style.cursor = 's-resize';
      break;
    default:
      this.style.cursor = 'default';
  }
}

function div_resizeCheck(e){
  e = normalizeEvent(e);
  if (this.resizeHandleObj) div = this.resizeHandleObj;
  else div = this;
  normalizeDiv(div);
  if ( ((e.pro.x - this.x) < RESIZE_MARGIN) && ((e.pro.x - this.x) > 0)){
    return 'l';
  }
  else if ( ((e.pro.x - this.x - this.offsetWidth) > -RESIZE_MARGIN) && ((e.pro.x - this.x - this.offsetWidth) < 0)){
    return 'r';
  }
  if ( ((e.pro.y - this.y) < RESIZE_MARGIN) && ((e.pro.y - this.y) > 0)){
    return 't';
  }
  else if ( ((e.pro.y - this.y - this.offsetHeight) > -RESIZE_MARGIN) && ((e.pro.y - this.y - this.offsetHeight) < 0)){
    return 'b';
  }
  else{
    return false;
  }
}


function div_resizeGrab(e){
  e = normalizeEvent(e);
  if (this.resizeHandleObj) div = this.resizeHandleObj;
  else div = this;
  normalizeDiv(div);
  div.resizeGrabbed = div.resizeCheck(e);
  if (!div.resizeGrabbed) return;
  activeDivs.resize = div;
  div.resizeStartX = div.x;
  div.resizeStartY = div.y;
  div.resizeStartWidth = div.offsetWidth;
  div.resizeStartHeight = div.offsetHeight;
  div.mouseStartX = e.screenX;
  div.mouseStartY = e.screenY;
  window.onmousemove = resizeFollowMouse;
  window.onselectstart=new Function ("return false");
  window.onmousedown=new Function ("return false");
  addEvent(window, 'mouseup', resizeUngrab);
  for (var i in window.iFrames){
     if (!window.iFrames[i].document) continue;
     addEvent(window.iFrames[i], 'mousemove', resizeFollowMouse);
     addEvent(window.iFrames[i], 'mouseup', resizeUngrab);
  }
}

function div_grab(e){
  e = normalizeEvent(e);
  if (this.handleObj) div = this.handleObj;
  else div = this;
  activeDivs.slide = div;
  normalizeDiv(div); 
  div.grabStartX = div.x;
  div.grabStartY = div.y;
  div.mouseStartX = e.pro.x;
  div.mouseStartY = e.pro.y;
  if (document.layers){
    div.style.clipTop = '';
    div.style.clipRight = '';
    div.style.clipBottom = '';
    div.style.clipLeft = '';
  }
//  else{
//    div.style.clip = '';
//  }
  div.style.overflow = 'visible';
  div.absoluteClip = null;
  div.style.visibility = 'visible'
  window.onmousemove = followMouse;
  window.onselectstart=new Function ("return false");
  window.onmousedown=new Function ("return false");
  addEvent(window, 'mouseup', ungrab);
  for (var i in window.iFrames){
     if (!window.iFrames[i].document) continue;
     addEvent(window.iFrames[i], 'mousemove', followMouse);
     addEvent(window.iFrames[i], 'mouseup', ungrab);
  }
  div.grabbed = true;
}

function div_resizeUngrab(e){
  if (this.resizeHandleObj) div = this.resizeHandleObj;
  else div = this;
  window.onmousemove='';
  div.resizeGrabbed = false;
  window.onmousedown='';
  window.onselectstart='';
  window.onclick='';
  for (var i in window.iFrames){
    removeEvent(window.iFrames[i], 'mousemove', resizeFollowMouse);   
  }
}

function div_ungrab(e){
  if (this.handleObj) div = this.handleObj;
  else div = this;
  window.onmousemove='';
  div.grabbed = false;
  window.onmousedown='';
  window.onselectstart='';
  window.onclick='';
  div.prevX = 0;
  for (var i in window.iFrames){
    removeEvent(window.iFrames[i], 'mousemove', followMouse);   
  }
}


function makeCool(div){
  normalizeDiv(div);
  if (this.isCool) return;
  div.globalId = globalDivs.push(div) - 1;
  div.globalRef = "globalDivs["+div.globalId+"]";
  div.moveTo = div_moveTo;
  div.move = div_move;
  div.absoluteClip = div_absoluteClip;
  div.moveNextTo = div_moveNextTo;
  div.pushOn = div_pushOn;
  div.insertThisBefore = div_insertThisBefore;
  div.append = div_appendTo;
  div.remove = div_remove;
  div.replace = div_replace;
  div.addClass = div_addClass;
  div.hasClass = div_hasClass;
  div.removeClass = div_removeClass;
  div.addEvent = div_addEvent;
  div.removeEvent = div_removeEvent;
  div.setOpacity = div_setOpacity;
  if (!div.id) div.id = ++idCount;
  div.isCool = true;
}

/**
* @param text string
* @param section string optional
* @param level int optional
*/
function debug(text){
  section = arguments[1];
  level = arguments[2];
  if (level && level > DEBUG_LEVEL) return;
  if (!section) section = 'js';
  if (!imp.debugConsole) return;
  imp.debugConsole.add(section, text);
}

function div_moveTo(x, y){
  this.style.position = "absolute";
  this.style.left = x + "px";
  this.style.top = y + "px";
  if (this.hasAbsoluteClip){
    if (document.layers){
      this.style.clipTop = this.absoluteClip['top']-x;
      this.style.clipRight = this.absoluteClip['right'];
      this.style.clipBottom = this.absoluteClip['bottom'];
      this.style.clipLeft = this.absoluteClip['left'] - y;
    }
    else{
      this.style.clip = "rect("+(this.absoluteClip['top']-y)+" "+ this.absoluteClip['right'] + " " +this.absoluteClip['bottom'] + " "+(this.absoluteClip['left']-x)+")";
    }
  }
}

function div_move(pxX, pxY){
  normalizeDiv(this);
  this.moveTo(this.x + pxX, this.y + pxY);
}

function div_absoluteClip(absoluteTop, relativeRight, relativeBottom, absoluteLeft){
  this.absoluteClip = new Array();
  this.absoluteClip['top'] = absoluteTop;
  this.absoluteClip['right'] = relativeRight;
  this.absoluteClip['bottom'] = relativeBottom;
  this.absoluteClip['left'] = absoluteLeft;
  this.hasAbsoluteClip = true;
}

function div_setOpacity(opacity){
  this.style.opacity = opacity;
  this.style['-moz-opacity'] = opacity;
  this.style.filter = "alpha(opacity="+opacity*100+")"; //ie
}

function div_fade(){
}

function div_slide(){
}

function div_moveNextTo(div, pos){
  normalizeDiv(div);
  if (pos == 'right') this.moveTo(div.x+div.offsetWidth, div.y);
  else if (pos == 'bottom') this.moveTo(div.x, div.y+div.offsetHeight);
  else if (pos == 'left') this.moveTo(div.x-this.offsetWidth, div.y);
  else if (pos == 'top') this.moveTo(div.x, div.y-this.offsetHeight);
}

function div_pushOn(div, slide){
  normalizeDiv(div);
  makeCool(div);
  this.moveTo(div.x, div.y);
  div.moveNextTo(this, slide);
}

function div_insertThisBefore(div){
  div.parentNode.insertBefore(this, div);
}

function div_appendTo(div){
  div.appendChild(this);
}

function div_remove(){
  this.parentNode.removeChild(this);
}

function div_replace(div){
  div.parentNode.insertBefore(this, div);
  div.parentNode.removeChild(div);
}

//function div_restore(){
//  if (!removedDivs[div.id]) return;
//  if (removedDivs[div.id]['next']) this.insertThisBefore(removedDivs[div.id]['next']);
//  else this.appendTo(removedDivs[div.id]['parent']); 
//}

function div_addClass(n){
  this.removeClass(n);
  this.className += " "+n;
}

function div_removeClass(n){
  classes = this.className.split(" ");
  className = "";
  for (var i=0; i<classes.length; i++){
    if (classes[i] != n) className += classes[i] + " ";
  }
  this.className = ""+className;
}

function div_hasClass(name){
  var pos = this.className.indexOf(name);
  if (pos > 0 && (this.className.length <= pos + name.length || this.className.charAt(pos+1) == ' ')) return true;
  return false;
}

function addEvent(obj, name, func){
  try{
    removeEvent(obj, name, func);
  } catch(exc){
  }
  if (obj.addEventListener) obj.addEventListener(name, func, false);
  else if (obj.attachEvent) obj.attachEvent('on'+name, func);
}

function removeEvent(obj, name, func){
  try{
    if (obj.removeEventListener) obj.removeEventListener(name, func, false);
    else if (obj.detachEvent) obj.detachEvent('on'+name, func);
  }
  catch(exc){}
}

function stopPropagation(e){
  if (!e) var e = window.event;
	e.cancelBubble = true;
	if (e.stopPropagation) e.stopPropagation();
}

function div_addEvent(name, func){
  addEvent(this, name, func);
}

function div_removeEvent(name, func){
  removeEvent(this, name, func);   
}


function getObj(name){
  var obj;
  if (typeof(name) == 'object') obj = name;
  else if (document.getElementById) obj = document.getElementById(name);
  else if (document.all) obj = document.all[name];
  else if (document.layers) obj = getNN4Obj(document, name);
  if (obj && !obj.id) obj.id = 'obj'+(idCount++);
  return obj;
}

function getEl(name){
  var el = document.createElement(name);
  makeCool(el);
  return el;
}

function getNN4Obj(obj, name){
  var x = obj.layers;
  for (var i=0; i<x.length; i++){
    if (x[i].id == name) return x[i];
    else if (x[i].layers.length){
      var tmp = getNN4Obj(x[i], name);
      if (tmp) return tmp;
    }
  }
}

function appendObj(div){
  if (!_appDiv){
    _appDiv = document.createElement('div');
    document.body.appendChild(_appDiv);
  }
  _appDiv.appendChild(div);
}

/*  www.webreference.com ---> */

function setCookie(name, value, expires, path, domain, secure) {
  var curCookie = name + "=" + escape(value) +
      ((expires) ? "; expires=" + expires.toGMTString() : "") +
      ((path) ? "; path=" + path : "") +
      ((domain) ? "; domain=" + domain : "") +
      ((secure) ? "; secure" : "");
  document.cookie = curCookie;
}

function getCookie(name) {
  var dc = document.cookie;
  var prefix = name + "=";
  var begin = dc.indexOf("; " + prefix);
  if (begin == -1) {
    begin = dc.indexOf(prefix);
    if (begin != 0) return null;
  } else
    begin += 2;
  var end = document.cookie.indexOf(";", begin);
  if (end == -1)
    end = dc.length;
  return unescape(dc.substring(begin + prefix.length, end));
}

function deleteCookie(name, path, domain) {
  if (getCookie(name)) {
    document.cookie = name + "=" +
    ((path) ? "; path=" + path : "") +
    ((domain) ? "; domain=" + domain : "") +
    "; expires=Thu, 01-Jan-70 00:00:01 GMT";
  }
}

// date - any instance of the Date object
// * hand all instances of the Date object to this function for "repairs"

function fixDate(date) {
  var base = new Date(0);
  var skew = base.getTime();
  if (skew > 0)
    date.setTime(date.getTime() - skew);
}
/*  ---> www.webreference.com */


function getWindowSize(){
  if (window.innerWidth){
    window.width = window.innerWidth;
    window.height = window.innerHeight;
  }
  else if (document.body.offsetWidth){
    window.width = document.body.offsetWidth;
    window.height = document.body.offsetHeight;
  }
  else{
    window.width = 630;
    window.height = 460;
  }
}

function stopBubbling(e){
  if (e.stopPropagation) e.stopPropagation();
  else e.cancelBubble = true;
  
}

function addTooltip(div, text){
  makeCool(div);
  div.addEvent('mouseover', function(e){tooltip(e, text); });
  div.addEvent('mouseout', function(e){ untooltip(e); });
}

function removeTooltip(){
  if (currentTooltip){
    makeCool(currentTooltip);
    try{
      currentTooltip.remove();
    } catch (exc){
    }
  }
}


function tooltip(e, text){
  for (var i in tooltipDivs){
    if (i != 0) untooltipDiv(i);
  }
  tooltipDivs = new Array();
  normalizeEvent(e);
  div = e.target;
  normalizeDiv(div);
  var tt = document.createElement('div');
  tt.className = 'tooltip';
  tt.style.position = 'absolute';
  tt.id = 'tt_'+div.id;
  tt.innerHTML = text;
  //var t = document.createTextNode(text);
  //tt.appendChild(t);
  tt.style.display = 'none';
  appendObj(tt);
  makeCool(tt);
  div.tt = tt;
  div.addEvent('mousemove', function(e){ moveTooltip(e); });
  tooltipDivs.push(div);
  stopBubbling(e);
  
}

function moveTooltip(e){
  normalizeEvent(e);
  getWindowSize();
  var destX, destY;
  var ttWidth = e.target.tt.offsetWidth;
  var ttHeight = e.target.tt.offsetHeight;
  if (e.pro.x < window.width-ttWidth-30) destX = e.pro.x + 10;
  else destX = e.pro.x - ttWidth  - 10;
  if (e.pro.y < window.height-ttHeight-30) destY = e.pro.y + 10;
  else destY = e.pro.y - ttHeight - 10;
  currentTooltip = e.target.tt;
  e.target.tt.style.display = '';
  e.target.tt.moveTo(destX, destY);
}

function untooltip(e){
  normalizeEvent(e);
  untooltipDiv(e.target);
}

function untooltipDiv(div){
  makeCool(div);
  div.removeEvent('mousemove', moveTooltip);
  tt = getObj('tt_'+div.id);
  if (tt) tt.remove();
}

function closeIPopups(){
  for (var i in window.iPopups) if (i.remove) i.remove();
  window.iPopups = new Array();
}

function closeIPopup(num){
//  if (window.iPopup){
    try{
      document.body.removeChild(window.iPopups[num]);
      window.iPopups[num] = null;
    }
    catch(exc){
    }
//  }
//  else window.close();
}

function iPopupClose(){
  if (window.parent && window.parent.closeIPopup && window.iPopup) window.parent.closeIPopup(window.iPopup.iPopupNum);
  else window.close();
}

/**
* Opens an url as a DHTML popup
*
* @param src string		address of the html document to display
* @param name string		will be the name of the iframe
* @param title string optional	descriptive title to display
* @return object div		a reference to the outer div
*/
function openIPopup(src, name, title){
//  closeIPopups();
  var iframe = createIFrame(name, src);
  div = makeIPopup(iframe, title);
  iframe.iPopupNum = div.iPopupNum;
  iframe.contentWindow.iPopup = div;
  iframe.contentWindow.iPopupNum = div.iPopupNum;
  div.iPopupIframe = iframe;
  return div;
}

function createIPopup(title){
  var div = document.createElement('div');
  return makeIPopup(div, title);
}

/**
* @param div object
* @param title string optional
*/
function makeIPopup(div, title){
  makeCool(div);
  div.style.width = '100%';
  div.style.height = '100%';
  var container = document.createElement('div');
  window.iPopups.push(container);
  var iPopupNum = window.iPopups.length-1;
  makeCool(container);
  container.iPopupNum = iPopupNum;
  container.className = 'iPopup container';
  var handle = document.createElement('div');
  handle.className = 'iPopup handle';
  var aspan = document.createElement('span');
  var a = document.createElement('a');
  a.className = 'iPopup action close';
  a.href = 'javascript: window.parent.closeIPopup('+iPopupNum+')';
  a.innerHTML = 'X';
  aspan.appendChild(a);
  handle.appendChild(aspan);
  if (title){
    var caption = document.createElement('span');
    caption.className = 'iPopup caption';
    caption.innerHTML = " "+title;
    handle.appendChild(caption);
  }  
  //div.insertBefore(handle, div.firstChild);
  //div.appendChild(handle);
  container.appendChild(handle);
  div.addClass('iPopup content');
  container.appendChild(div);
  makeDraggable(container, handle);
  makeResizable(container);
  container.handle = handle;
  container.content = div;
  container.addEvent('click', function(e){window.currentIPopup = this.iPopupNum;});
  document.body.appendChild(container);
  position(container);
  container.content = div;
  container.handle = handle;
  //this is an awful hack, but I have no other ideas...
  container.fixResize = function (){ this.content.style.height = this.offsetHeight - this.handle.offsetHeight - 6;};
  container.fixResize();
  return container;
}

function createIFrame(name, src){
  debug("Creating iframe "+name);
  var iframe = document.createElement('iframe');
  iframe.onload = function(){ if (this.contentWindow && parent.iFrames){ parent.iFrames[name]=this.contentWindow;} };
  iframe.name = name;
  iframe.src = src;
  return iframe;
}

function position(div, x, y){
  makeCool(div);
  getWindowSize();
  if (x==undefined) div.moveTo((window.width-div.offsetWidth)/2, (window.height-div.offsetHeight)/2);
  else{
    if (x+div.offsetWidth > window.width) x -= div.width;
    if (y+div.offsetHeight > window.height) y -= div.height;
    div.moveTo(x, y);
  }
}

var titlesArray;
function getTitles(div){
  titlesArray = new Array();
  recGetTitles(div);
  return titlesArray.length;
}

function buildIndex(container){
  for (var i in titlesArray){
    div = titlesArray[i];
    a = document.createElement('a');
    a.name = 'index'+i;
    div.parentNode.insertBefore(a, div);
    index = document.createElement('a');
    index.href='#index'+i;
    index.innerHTML = div.innerHTML;
    container.appendChild(index);
    br = document.createElement('br');
    container.appendChild(br);
  }
}

function recGetTitles(div){
  spans = div.getElementsByTagName('span');
  for (var i in spans){
    if (spans[i].className == 'sottotitolo'){
      titlesArray.push(spans[i]);
      //recGetTitles(spans[i]);
    }
  }
}

function toggle(divId, indicator, htmlOn, htmlOff){
  div = getObj(divId);
  var divIndicator;
  if (!div || !div.style) return;
  if (indicator) divIndicator = getObj(indicator);
  if (div.style.display == 'none'){
    if (divIndicator) divIndicator.innerHTML = htmlOff;
    div.style.display = '';
  }
  else{
    if (divIndicator) divIndicator.innerHTML = htmlOn;
    div.style.display = 'none';
  }
}

var fixedDivs = new Array();
var fixedDivsTimeout;

function makeFixedPos(div){
  if (div.style.display == 'none') div.style.display = 'block';
  makeCool(div);
  div.fixedPosX = div.x;
  div.fixedPosY = div.y;
  div.moveTo(div.fixedPosX, div.fixedPosY);
  fixedDivs.push(div);
  if (!fixedDivsTimeout) fixedDivsTimeout = setTimeout('updateFixedPos()', 100);
}

function updateFixedPos(){
  if (window.innerHeight) pos = window.pageYOffset
	else if (document.documentElement && document.documentElement.scrollTop) pos = document.documentElement.scrollTop
	else if (document.body) pos = document.body.scrollTop
  for (var i in fixedDivs){
    var div = fixedDivs[i];
    div.moveTo(div.fixedPosX, div.fixedPosY + pos);
  }
  fixedDivsTimeout = setTimeout('updateFixedPos()', 500);
}


function startTimeout(func, interval){
  var r = setTimeout(func, interval);
  timeouts[r] = new Array();
  timeouts[r][0] = func;
  timeouts[r][1] = interval;
  return r;
}

//NOTE: this will reset even if the timeout is already expired.
function resetTimeout(t){
  if (!t) return;
  var func, interval;
  if (timeouts[t]){
    func = timeouts[t][0];
    interval = timeouts[t][1];
  }
  if (t) clearTimeout(t);
  var nt;
  if (interval && func){
    nt = startTimeout(func, interval);
  }
  return nt;
}



function trackMouse(e){
  normalizeEvent(e);
  mousePos.x = e.pro.x;
  mousePos.y = e.pro.y;
}

function mouseInside(div){
  normalizeDiv(div);
  if (mousePos.x > div.x && mousePos.x < div.x+div.offsetWidth && mousePos.y > div.y && mousePos.y < div.y+div.offsetHeight) return true;
  return false;
}

makeCool(document);
document.addEvent('mousemove', trackMouse);

function getAllChildren(div, array){
  if (!array) array = new Array();
  
}

function getObjectsByClasses(){
}

function makeViewport(div){
  var container = document.createElement('div');
  container.className = 'viewport container';
  var handle = document.createElement('div');
  handle.className = 'viewport handle';
  container.appendChild(handle);
  container.appendChild(div);
  makeCool(div);
  div.addClass('viewportEl');
  makeDraggable(container, handle);
}

function getXmlHttp(){
  var x=false;
  try {
    x = new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) {
    try {
      x = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (E) {
      x = false;
    }
  }
  if (!x && typeof XMLHttpRequest!='undefined') {
    x = new XMLHttpRequest();
  }
  return x;
}

function buildQueryString(form){
  var qstr = "";
  var tmp = "";
  fl =  form.elements.length;
  for(i=0;i<fl;i++) {
    el = form.elements[i];
    if((el.type == 'text' ) || (el.type == 'checkbox' && el.checked == true) ||
       (el.type == 'textarea') || (el.type == 'radio' && el.checked == true) ||
       (el.type == 'hidden' )) { //&& el.value.length > 0
      tmp = el.name+'='+escape(el.value)+'&';
    }
    if(typeof(el.selectedIndex) != 'undefined' && el.selectedIndex != -1){
      for(j=0;j<el.options.length;j++) { if(el.options[j].selected == true) {
        tmp += el.name+'='+escape(el.options[j].value)+'&'; }
      }
    }
    if(tmp.length > 0) { qstr += tmp;  tmp = "";}
  }
  qstr = qstr.substring(0,qstr.length - 1);
  return qstr;
}

//optional param: method (POST, GET)
function xmlHttpQuery(url, query, method, name, timeout, failCallback, requestName, xmlHttp){
  //var method = arguments[2];
  if (!method) method = 'POST';
  if (!timeout) timeout = 5000;
  var xmlHttp;
  if (name){
    //not working
    xmlHttp = xmlHttpRequests[name];
    if (xmlHttp && callInProgress(xmlHttp)){
      xmlHttp.aborting = 1;
      xmlHttp.abort();
    }
  }
  if (!xmlHttp) xmlHttp = getXmlHttp();
  if (name) xmlHttpRequests[name] = xmlHttp;
  xmlHttp.open(method, url,true);
  if (method == 'POST'){
    xmlHttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
  }
  xmlHttp.onreadystatechange=function() {
    if (xmlHttp.readyState==4) {
      //alert(xmlHttp.responseText);
      //getObj('debug').innerHTML = xmlHttp.responseText;
      if (xmlHttp.aborting){
        xmlHttp.aborting = 0;
        return;
      }
      if (xmlHttp.status != 200){
        if (failCallback) failCallback;
        //alert(xmlHttp.status);
      }
      try{
        var root = xmlHttp.responseXML.documentElement;
        for (var i=0; i<root.childNodes.length; i++){
          if (root.childNodes[i].nodeType == 1 && xmlHttpHandlers[root.childNodes[i].nodeName]){
            if (typeof(xmlHttpHandlers[root.childNodes[i].nodeName]) == 'object'){
              for (var j=0; j<xmlHttpHandlers[root.childNodes[i].nodeName].length; j++){
                xmlHttpHandlers[root.childNodes[i].nodeName][j](root.childNodes[i]);
              }
            }
            else xmlHttpHandlers[root.childNodes[i].nodeName](root.childNodes[i]);
          }
        }
      }
      catch(exp){
        if (failCallback) failCallback();
      }
    }
  }
  //not working
  //window.setTimeout(function() {
  //  if ( callInProgress(xmlHttp) ) {
  //      xmlHttp.aborting = 1;
   //     xmlHttp.abort();
   //     if (failCallback) failCallback();
   // }
  //},timeout);
  if (query) query += "&";
  else query = "";
  //query += "o=xml&XDEBUG_PROFILE=1";
  query += "o=xml";
  //getObj('debug').innerHTML = query;
  //alert(query);
  xmlHttp.send(query)
}

function addXmlHttpHandler(name, func){
  if (xmlHttpHandlers[name] && typeof(xmlHttpHandlers[name]) != 'object'){
    xmlHttpHandlers[name] = [xmlHttpHandlers[name]];
  }
  if (!xmlHttpHandlers[name]) xmlHttpHandlers[name] = new Array();
  xmlHttpHandlers[name][xmlHttpHandlers[name].length] = func;
  return xmlHttpHandlers[name].length - 1;
}

function callInProgress(xmlhttp) {
    switch ( xmlhttp.readyState ) {
        case 1, 2, 3:
            return true;
        break;
	
        // Case 4 and 0
        default:
            return false;
        break;
    }
}


//Modified from:
// @name      The Fade Anything Technique
// @namespace http://www.axentric.com/aside/fat/
// @version   1.0-RC1
// @author    Adam Michela

function makeHex(r,g,b){
	r = r.toString(16); if (r.length == 1) r = '0' + r;
	g = g.toString(16); if (g.length == 1) g = '0' + g;
	b = b.toString(16); if (b.length == 1) b = '0' + b;
	return "#" + r + g + b;
}

function fadeAndBack(el, fps, duration1, duration2, target, property){
  var color = getBgcolor(el, property);
  var o = getObj(el);
  callback = "fadeElement('"+o.id+"', "+fps+", "+duration2+", '"+color+"', '', '"+property+"');";
  fadeElement(el, fps, duration1, target, callback, property);
}

function fadeElement(el, fps, duration, to, callback, property){
  var o = getObj(el);
  var frames = Math.round(fps * (duration / 1000));
	var interval = duration / frames;
	var delay = interval;
	var frame = 0;
	var from = getBgcolor(el, property);
	var rf = parseInt(from.substr(1,2),16);
	var gf = parseInt(from.substr(3,2),16);
	var bf = parseInt(from.substr(5,2),16);
	var rt = parseInt(to.substr(1,2),16);
	var gt = parseInt(to.substr(3,2),16);
	var bt = parseInt(to.substr(5,2),16);
		
	var r,g,b,h;
	while (frame < frames){
	  r = Math.floor(rf * ((frames-frame)/frames) + rt * (frame/frames));
	  g = Math.floor(gf * ((frames-frame)/frames) + gt * (frame/frames));
	  b = Math.floor(bf * ((frames-frame)/frames) + bt * (frame/frames));
	  h = makeHex(r,g,b);
		
	  setTimeout("setBgcolor('"+o.id+"','"+h+"','"+property+"')", delay);

		frame++;
		delay = interval * frame; 
	}
	setTimeout("setBgcolor('"+o.id+"','"+to+"','"+property+"')", delay);
	if (callback) setTimeout(callback, delay+interval+100);
	
}

function setBgcolor(el, c, property)
	{
	  if (!property || typeof(property) == 'undefined') property = 'background-color';
	  var parts = property.split('-');
	  if (parts.length > 1){
	    property = parts[0]+parts[1].substring(0,1).toUpperCase()+parts[1].substring(1,parts[1].length);
	  }
		var o = getObj(el);
		if (o) eval('o.style.'+property+' = c');
	}
function getBgcolor(el, property){
		var o = getObj(el);
		if (!property || typeof(property) == 'undefined') property = 'background-color';
		var parts = property.split('-');
	  if (parts.length > 1){
	    ucWProperty = parts[0]+parts[1].substring(0,1).toUpperCase()+parts[1].substring(1,parts[1].length);
	  }
		while(o)
		{
			var c;
			if (window.getComputedStyle) c = window.getComputedStyle(o,null).getPropertyValue(property);
			if (o.currentStyle) eval('c = o.currentStyle.'+ucWProperty);
			if ((c != "" && c != "transparent") || o.tagName == "BODY") { break; }
			o = o.parentNode;
		}
		if (c == undefined || c == "" || c == "transparent") c = "#FFFFFF";
		var rgb = c.match(/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/);
		if (rgb) c = makeHex(parseInt(rgb[1]),parseInt(rgb[2]),parseInt(rgb[3]));
		return c;
}

function noa(){
}

function disableSelection(element, exceptionTags, exceptionIds){
  element.style.MozUserSelect	= 'none' ;
  if (!element.all) return;
  element.unselectable = 'on' ;
  if (!exceptionTags) exceptionTags = new Array();
  if (!exceptionIds) exceptionIds = new Array();

  var e, i = 0 ;
  /*
  while ( e = element.all[ i++ ] ){
    if (exceptionTags[e.tagName]) continue;
    if (exceptionIds[e.id]) continue;
  	disableSelection(e);		
  }*/
  while ( e = element.all[ i++ ] ){
  /*
  switch ( e.tagName.toUpperCase() )
		{
			case 'IFRAME' :
			case 'TEXTAREA' :
			case 'INPUT' :
			case 'SELECT' :
				// Ignore the above tags
				break ;
			default :
				e.unselectable = 'on' ;
		}
		*/
		disableSelection(e);
		}
}
/*
var reportStatus = new Array();
function report ( msg ) {
    reportStatus.push ( msg );
}
function showReport ( err ) {
    alert ( reportStatus.join ( "\n" ) );
}
window.onerror = function ( err, url, line ) {
    report ( err + " [" + url + " - line " + line + "]" );
    showReport();
}

*/
