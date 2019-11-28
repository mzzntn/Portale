var combos = new Array();

function createComboBox(name, div){
  var input = document.createElement("input");
  input.type = "text";
  input.id = name+"_input";
  div.appendChild(input);
  var a = document.createElement("a");
  a.innerHTML = '+';
  a.href = '#';
  a.id = name+"_button";
  div.appendChild(a);
  return new ComboBox(input, a);
}

function closeCombos(){
  for (var i in combos){
    try{
      combos[i].clear();
    }catch (exc){
    }
  }
}


function ComboBox(textInput, button){
  this.input = getObj(textInput);
  this.button = getObj(button);
  this.rows = new Array();
  this.v = new Array();
  this.menuTexts = new Array();
  normalizeDiv(this.input);
  this.config = new Array();
  this.texts = new Array();
  this.config = new Array();
  combos.push(this);
  this.comboNum = combos.length-1;
  this.input.className += " comboBox field enabled";
  this.button.className += " comboBox";
}

ComboBox.prototype.init = function(){
//  this.v.sort();
  this.div = document.createElement('div');
  makeCool(this.div);
  this.div.className = 'comboBox comboMenu';
  this.div.innerHTML = '';
  document.body.appendChild(this.div);
  normalizeDiv(this.input);
  this.div.moveTo(this.input.x, this.input.y +20);
  this.input.value = '';
  this.input.comboBox = this;
  this.input.onkeyup = function(e){ this.comboBox.valueChange(); };
  this.input.onkeydown = function(e){ this.comboBox.getKey(e); };
  //this.input.onclick = function(e){ this.comboBox.input.value = '' };
  this.input.setAttribute("autocomplete","off");
  this.button.comboBox = this;
  //this.button.onclick = function(e){ this.comboBox.buttonClick; return false;};
  addEvent(this.button, 'click', function(e){ normalizeEvent(e); e.target.comboBox.buttonClick(); stopPropagation(e); return false;});
  if (this.config['hiddenInput']) this.setHiddenInput(this.config['hiddenInput']);
  if (this.hidden ) this.setValue(this.v[this.hidden.value]);
  this.menuSize = 0;
  for (var i in this.v){
    this.texts[i] = this.v[i];
    this.menuSize++;
  }
  if (this.menuSize < 1) this.button.style.display = 'none';
  addEvent(document.body, 'click', closeCombos);
}

ComboBox.prototype.setHiddenInput = function(hiddenInputId){
  this.hidden = getObj(hiddenInputId);
}

ComboBox.prototype.setModeInput = function(modeInputId){
  this.modeInput = getObj(modeInputId);
}

ComboBox.prototype.parseValue = function(){
  string = this.input.value;
  if (string == ""){
    this.completions = new Array;
    return;
  }
  this.completions = new Array();
  for (var i in this.v){
    if (this.v[i].indexOf(string) == 0) this.completions[i] = this.v[i];
    if (this.hidden){
      if (this.v[i] == string){
        this.hidden.value = i;
        if (this.modeInput) this.modeInput.value = 'key';
      }
      else{
        this.hidden.value = string;
        if (this.modeInput) this.modeInput.value = 'val';
      }
    }
  }
}

ComboBox.prototype.buttonClick = function(){
  if (this.rows.length == this.menuSize) this.clear();
  else this.showFullMenu();
  return false;
}

ComboBox.prototype.showFullMenu = function(){
  this.completions = this.v;
  this.rebuild();
}

ComboBox.prototype.rebuild = function(){
  this.clear();
  this.build();
}

ComboBox.prototype.clear = function(){
  for (var i in this.rows){
    node = this.rows[i];
    this.div.removeChild(node);
  }
  this.rows = new Array();
  this.div.style.visibility = 'hidden';
}

ComboBox.prototype.build = function(){
  normalizeDiv(this.input);
  this.div.moveTo(this.input.x, this.input.y +20);
  for (var i in this.completions){
    var row = document.createElement('div');
    row.className = 'comboBox comboMenuRow';
    row.comboBox = this;
    row.keyId = i;
    if (this.menuTexts[i]) row.innerHTML = this.menuTexts[i];
    else row.innerHTML = this.completions[i];
    row.onclick = function(e){ this.comboBox.menuClick(this) };
    this.div.appendChild(row);
    this.rows.push(row);
  }
  if (this.div.offsetWidth < this.input.offsetWidth){
    this.div.style.width = this.input.offsetWidth;
  }
  if (this.rows.length > 0) this.div.style.visibility = 'visible';
  disableSelection(this.div);
}

ComboBox.prototype.setValue = function(val){
  if (val == undefined) return;
  if (this.hidden) this.hidden.value = val;
  if (this.texts[val]) this.input.value = this.texts[val];
  else this.input.value = val;
}

ComboBox.prototype.valueChange = function(){
  this.parseValue();
  this.rebuild();
}

ComboBox.prototype.menuClick = function(target){
  if (this.hidden) this.hidden.value = target.keyId;
  if (this.modeInput) this.modeInput.value = 'key';
  this.input.value = target.innerHTML;
  this.clear();
  if (this.config['action']) this.config['action'](target.keyId, this);
  if (this.config['menuAction']) this.config['menuAction'](target.keyId, this);
}

ComboBox.prototype.getKey = function(e){
  var code;
  if (!e) var e = window.event;
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  normalizeEvent(e);
  var target = e.target;
  switch (code){
    case 9: //tab
      this.menuClick(this.rows[0]);
      return false;
      break;
    case 13: //return
      if (this.config['returnAction']) this.config['returnAction'](target.value, this);
      break;
    case 27: //esc
      this.clear();
      break;
  }
  return false;
}

ComboBox.prototype.disableInput = function(){
  this.input.disabled = true;
  makeCool(this.input);
  this.input.removeClass('enabled');
}
