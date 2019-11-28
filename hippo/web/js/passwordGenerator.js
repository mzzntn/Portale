function generatePassword(field, length){
  if (!length) length = 8;
  var chars = "abcdefghijklmnopqrstuvwxyz0123456789";
  var password = "";
  for (count = 0; count < length; count++){
    password += chars.charAt(Math.floor(Math.random()*chars.length));
  }

  var popup = window.open('', 'password', 'width=250,height=150,left=300,top=300');
  popup.document.open();
  popup.document.write('<html><body><center>Password generata:<br><br><b>'+password+'</b><br><br><a href="javascript: window.close()">Ok</a></body></html>');
  popup.document.close();
    var input = getObj(field);
  input.value = password;
}
