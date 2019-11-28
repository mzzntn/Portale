var calendarDiv;

function Cal(name, div){
  this.name = name;
  this.div = getObj(div);
  makeCool(div);
  div.addClass('calendar');
  this.config = new Array();
  this.config.days = ['L','M','M','G','V','S','D'];
  this.config.months = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio',
                        'Agosto','Settembre','Ottobre','Novembre','Dicembre'];
  this.setDate();
}

Cal.prototype.dayClick = function(day){
  if (day.length < 2) day = '0'+day;
  var month = this.month+'';
  if (month.length < 2) month = '0'+month;
  if (this.config.action) this.config.action(day+'/'+month+'/'+this.year);
}

Cal.prototype.setDate = function(day, month, year){
  if (!day || !month || !year) this.date = new Date();
  else this.date = new Date(year, month-1, day);
  this.day = this.date.getDate();
  this.month = this.date.getMonth()+1;
  if (this.date.getFullYear) this.year = this.date.getFullYear();
  else this.year = this.date.getYear();
}

Cal.prototype.daysInMonth = function(){
  return 32 - new Date(this.year, this.month-1, 32).getDate();
}

Cal.prototype.rebuild = function(){
  this.clear();
  this.build();
}

Cal.prototype.build = function(){
  this.buildHead();
  var center = document.createElement('center');
  var table = document.createElement('table');
  var tbody = document.createElement('tbody');
  var tr = document.createElement('tr');
  for (var i=0; i<7; i++){
    var th = document.createElement('th');
    th.innerHTML = this.config.days[i];
    tr.appendChild(th);
  }
  tbody.appendChild(tr);
  this.date.setDate(1);
  var day = this.date.getDay();
  if (day == 0) day = 7;
  var td;
  var col = 0;
  tr = document.createElement('tr');
  for (var i=0; i < day-1; i++){
    td = document.createElement('td');
    tr.appendChild(td);
    col++;
  }
  for (var i=0; i<this.daysInMonth(); i++){
    if (col%7 == 0){
      tbody.appendChild(tr);
      tr = document.createElement('tr');
      col = 0;
    }
    td = document.createElement('td');
    a = document.createElement('a');
    a.href='javascript: noa()';
    a.calendar = this;
    a.onclick = function(e){this.calendar.dayClick(this.innerHTML);};
    a.innerHTML = i+1;
    td.appendChild(a);
    tr.appendChild(td);
    col++;
  }
  for (var i=col; i<7; i++){
    td = document.createElement('td');
    tr.appendChild(td);
  }
  tbody.appendChild(tr);
  table.appendChild(tbody);
  center.appendChild(table);
  center.style.display = 'inline';
  this.div.appendChild(center);
  this.table = table;
  this.center = center;
}

Cal.prototype.buildHead = function(){
  var div = document.createElement('div');
  var a = document.createElement('a');
  a.cal = this;
  a.href='javascript: noa()';
  a.appendChild(document.createTextNode('<<'));
  a.onclick = function(e){ this.cal.monthBack() };
  div.appendChild(a);
  var b = document.createElement('b');
  b.innerHTML = ' '+this.config.months[this.month-1]+' '+this.year+' ';
  div.appendChild(b);
  a = document.createElement('a');
  a.cal = this;
  a.href='javascript: noa()';
  a.appendChild(document.createTextNode('>>'));
  a.onclick = function(e){ this.cal.monthForward() };
  div.appendChild(a);
  this.heading = div;
  this.div.appendChild(div);
}

Cal.prototype.clear = function(){
  this.div.removeChild(this.heading);
  this.div.removeChild(this.center);
}

Cal.prototype.monthBack = function(){
  var month, year;
  if (this.month == 1){
    month = 12;
    year = this.year-1;
  }
  else{
    month = this.month-1;
    year = this.year;
  }
  this.setDate(this.day, month, year);
  this.rebuild();
}

Cal.prototype.monthForward = function(){
  var month, year;
  if (this.month == 12){
    month = 1;
    year = this.year+1;
  }
  else{
    month = this.month+1;
    year = this.year;
  }
  this.setDate(this.day, month, year);
  this.rebuild();
}  
