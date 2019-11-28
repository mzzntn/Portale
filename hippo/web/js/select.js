function SelectInput(divId){
  this.div = getObj(divId);
  this.t = new Array();
  this.i = new Array();
}

SelectInput.prototype.rebuild = function(){
  this.clear();
  this.build();
}

SelectInput.prototype.build = function(){
  for(j=0; j<this.t.length; j++){
    this.buildBranch(this.t[j], 0);
  }
}

SelectInput.prototype.buildBranch = function(branchId, level){
  branch = this.i[branchId];
  if (!branch) return;
  html = "";
  for (i=0; i<level; i++){
    html += "-";
  }
  html += branch.l;
  html += "<br>";
  this.div.innerHTML += html;
  for (i=0; i<branch.c.length; i++){
    this.buildBranch(branch.c[i], level+1);
  }

}

SelectInput.prototype.clear = function(){
  this.div.innerHTML = "";
}
