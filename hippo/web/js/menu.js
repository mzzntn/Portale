var currentMenu;
var t_menuHide;

function hideCurrentMenu(){
  if (currentMenu && currentMenu.collapse) currentMenu.collapse();
}

function Menu(title, menu){
  var t = getObj(title);
  t.menuObj = this;
  var m = getObj(menu);
  m.menuObj = this;
  this.title = t;
  this.menu = m;
  m.style.display = 'none';
  m.style.positione = 'absolute';
  t.menuDiv = m;
  t.onmouseover = function(e){ e.target.menuObj.expand(e); };
  this.config = new Array();
  this.config['timeout'] = 200;
  this.config['position'] = 'bottom';
}

Menu.prototype.expand = function(e){
  if (hideCurrentMenu) hideCurrentMenu();  //don't know why the check is needed
  makeCool(this.menu);
  makeCool(this.title);
  normalizeDiv(this.title);
  this.title.addClass('active');
  var posX, posY;
  switch(this.config.position){
    case 'bottom':
      posX = this.title.x;
      posY = this.title.y + this.title.offsetHeight;
      break;
    case 'right':
      posX = this.title.x + this.title.offsetWidth;
      posY = this.title.y - this.title.offsetHeight;
      break;
  }
  this.menu.moveTo(posX, posY);
  //this.menuDiv.moveTo(200, 200);
  //this.menuDiv.style.width = this.offsetWidth;
  this.menu.style.display = '';
  currentMenu = this;
  clearTimeout(t_menuHide);
  this.menu.onmouseover = function(){ clearTimeout(t_menuHide); };
  this.title.onmouseout = function(){ t_menuHide = setTimeout(hideCurrentMenu, this.menuObj.config['timeout']); };
  this.menu.onmouseout = function(){ t_menuHide = setTimeout(hideCurrentMenu, this.menuObj.config['timeout']); };
}

Menu.prototype.collapse = function(){
  this.title.removeClass('active');
  this.menu.style.display = 'none';
}
