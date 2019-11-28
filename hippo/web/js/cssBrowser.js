function CSSBrowser(div){
  this.div = div;
  this.divs = new Array();
  this.timeouts = new Array();
  this.config = new Array();
}

CSSBrowser.prototype.init = function(){
  this.getClasses(this.div);
  this.div.cssBrowser = this;
}

CSSBrowser.prototype.getClasses = function(root){
  for (var i=0; i < root.childNodes.length; i++){
    if (root.childNodes[i].nodeType == 1 && !root.childNodes[i].cssBrowser) this.getClasses(root.childNodes[i]);
  }
  if (!root.className) return;
  if (typeof(this.divs[root.className]) != 'object'){
     this.divs[root.className] = new Array();
  }
  this.divs[root.className].push(root);
}

CSSBrowser.prototype.buildMenu = function(){
  this.menu = new Array();
  for (var i in this.divs){
    var div = document.createElement('div');
    div.cssBrowser = this;
    div.innerHTML = i;
    div.browserClass = i;
    makeCool(div);
    div.addEvent('click', function(e){ this.cssBrowser.adminClass(this.browserClass);});
    div.addEvent('mouseover', function(e){ this.cssBrowser.mouseOver(this.browserClass); });
    div.addEvent('mouseout', function(e){ this.cssBrowser.mouseOut(); });
    this.menu.push(div);
  }
  return this.menu;
}

CSSBrowser.prototype.adminClass = function(name){
  if (this.config['admin']) this.popup = window.open(this.config['admin']+'?className='+name);
}

CSSBrowser.prototype.mouseOver = function(className){
  this.activeDivs = this.divs[className];
  for (var i in this.divs[className]){
    this.divs[className][i].oldBackground = this.divs[className][i].style.background;
    this.divs[className][i].style.background = '#FF0000';
  }
  
}

CSSBrowser.prototype.mouseOut = function(){
  for (var i in this.activeDivs){
    this.activeDivs[i].style.background = this.activeDivs[i].oldBackground;
    //this.activeDivs[i].style.background = '';
  }
  this.activeDivs = new Array();
}
