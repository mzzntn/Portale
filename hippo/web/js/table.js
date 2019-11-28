var sortingTable;

function Table(divId){
  this.div = getObj(divId);
  this.name = '';
  this.o = new Array();
  this.d = new Array();
  this.columns = new Array();
  this.names = new Array();
  this.rows = new Array();
  this.selectedRows = new Array();
  this.config = new Table.config();
  this.colNum = 0;
}

Table.config = function(){
  this.idCol = 0;
  this.admin = '';
  this.adminId = 'id';
  this.action = '';
  this.idLinkLabel = 0;
  this.selectRows = 1;
}

Table.prototype.rebuild = function(){
  this.clear();
  this.build();
}

Table.prototype.loadXml = function(xmlNode){
  var cnt=0;
  this.d = new Array();
  this.o = new Array();
  for (var i = 0; i < xmlNode.childNodes.length; i++){ //rows
    var row = xmlNode.childNodes[i];
    if (row.nodeType != 1) continue;
    this.d[cnt] = new Array(this.colNum);
    this.o[cnt] = cnt;
    this.parseXmlNode(row, cnt);
    cnt++;
  }
  this.rebuild();
  this.div.style.color = '#000000';
  //fadeAndBack(this.name+'_tableEl', 30, 300, 150, '#9B8FFF');
}

Table.prototype.parseXmlNode = function(xmlNode, cnt, prefix){
  if (!prefix) prefix = '';
  //prefix += xmlNode.tagName+'.';
  for (var j = 0; j < xmlNode.childNodes.length; j++){ //cols
    var col = xmlNode.childNodes[j];
    if (col.nodeType != 1) continue;
    var parsedChildren = false;
    for (var k = 0; k < col.childNodes.length; k++){ //look for text node
      if (col.childNodes[k].nodeType != 3 && col.childNodes[k].nodeType != 4){
        if (col.childNodes[k].nodeType == 1 && !parsedChildren){
          parsedChildren = true;
          this.parseXmlNode(col, cnt, prefix+col.tagName+'.');
        }
        continue;
      }
      var pos = this.columns[prefix+col.tagName];
      //alert(col.childNodes[k].nodeValue);
      if (typeof(pos) != 'undefined') this.d[cnt][pos] = col.childNodes[k].nodeValue; 
    }
  }
}

Table.prototype.build = function(){
  for(i=0; i<this.o.length; i++){
    row = document.createElement('tr');
    makeCool(row);
    /*row.className = this.className+" row";*/
    /*if (i%2 == 0) row.className += " even";
    else row.className += " odd";*/
    row.table = this;
    var id = this.d[this.o[i]][this.config.idCol];
    if (this.writable && !this.writable[id]) row.className += ' readOnly';
    adminId = this.d[this.o[i]][this.columns[this.config.adminId]];
    row.tableId = id;
    if (this.d[i].length > this.colNum) this.colNum = this.d[i].length;
    for(j=0; j<this.d[i].length; j++){
      if (j == this.config.idCol && !this.config.showId) continue;
      cell = document.createElement('td');
      if (this.d[this.o[i]][j] != 'undefined'){
        if (this.config.linkEl && j == this.columns[this.config.linkEl] && (this.config.admin || this.config.action)){
          tag = document.createElement('a');
          if (this.config.admin) tag.href=this.config.admin+adminId;
          else if (this.config.action){
            tag.href='#';
            tag.action = this.config.action;
            tag.tableId = adminId;
            tag.onclick = function(e){ this.action(this.tableId); };
          }
        }
        else tag = document.createElement('span');
        if (j == this.config.idCol && this.config.idLinkLabel) tag.innerHTML = this.config.idLinkLabel;
        else tag.innerHTML = this.d[this.o[i]][j];
        if (tag.innerHTML == 'undefined') tag.innerHTML = '';
        if (tag.innerHTML == '') tag.innerHTML = '&nbsp;';
        cell.appendChild(tag);
        if (this.config['tdClasses'][t.names[j]]){
          cell.className = this.config['tdClasses'][t.names[j]];
        }
      }
      else cell.innerHTML = '&nbsp;';
      row.appendChild(cell);
    }
    cell = document.createElement('td');
    var checkbox = document.createElement('input');
    checkbox.type="checkbox";
    cell.appendChild(checkbox);
    if (this.config.selectableRows) row.onclick = function(e){ this.table.rowClick(this); };
    if (this.config.admin && this.config.selectRows){
      row.admin = this.config.admin;
      row.ondblclick = function(e){ window.location=this.admin+this.tableId; };
      if (this.config.actionOnClick) row.onclick = function(e){ window.location=this.admin+this.tableId; };
      if (this.selectedRows[id])
      {
	row.addClass('selected');    
	checkbox.checked = true;
      }
    }
    else if (this.config.action){
      row.action = this.config.action;
      row.ondblclick = function(e){ this.action(this.tableId); };
    }
    row.appendChild(cell);
    this.div.appendChild(row);
    this.rows[this.rows.length] = row;
  }
}

Table.prototype.clear = function(){
  for (var i in this.rows){
    node = this.rows[i];
    try{
      this.div.removeChild(node);
    }
    catch(exc){
    }
  }
  this.rows = new Array();
}

Table.prototype.sort = function(column, dir){
  sortingTable = this;
  this.sortColumn = column;
  if (dir) this.o.sort(tableSortDown);
  else this.o.sort(tableSortUp);
  this.rebuild();
}

Table.prototype.rowClick = function(row){
  if (this.selectedRows[row.tableId]){
    row.removeClass('selected');
    row.getElementsByTagName('input')[0].checked = false;
    this.selectedRows[row.tableId] = 0;
  }
  else{
    row.addClass('selected');
    row.getElementsByTagName('input')[0].checked = true;
    this.selectedRows[row.tableId] = 1;
  }
}

Table.prototype.sendSelected = function(url, widget){
  var l = "";
  for (var id in this.selectedRows){
    if (!this.selectedRows[id]) continue;
    if (l) l += "&";
    l += widget+"["+id+"]=1";
  }
  window.location = url+l;
}

function tableSortUp(a, b){
  var t = sortingTable;
  if (t.d[a][t.sortColumn] > t.d[b][t.sortColumn]) return 1;
  if (t.d[b][t.sortColumn] > t.d[a][t.sortColumn]) return -1;
  return 0;
}

function tableSortDown(a, b){
  var t = sortingTable;
  if (t.d[a][t.sortColumn] > t.d[b][t.sortColumn]) return -1;
  if (t.d[b][t.sortColumn] > t.d[a][t.sortColumn]) return 1;
  return 0;
}

