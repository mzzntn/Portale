function ajaxfilemanager(fmUrl, field_name, url, type, win, installation_path) {
 var ajaxfilemanagerurl = fmUrl+"?language=it&fileType="+type+"&c="+btoa(installation_path);
 switch (type) {
     case "image":
         break;
     case "media":
         break;
     case "flash": 
         break;
     case "file":
         break;
     default:
         return false;
 }
 var fileBrowserWindow = new Array();
 fileBrowserWindow["url"] = ajaxfilemanagerurl;
 fileBrowserWindow["title"] = "File Manager";
 fileBrowserWindow["width"] = "782";
 fileBrowserWindow["height"] = "440";
 fileBrowserWindow["close_previous"] = "no";
 fileBrowserWindow["inline"] = 1;
 tinyMCE.activeEditor.windowManager.open(fileBrowserWindow, {
   window : win,
   input : field_name,
   resizable : "yes",
   inline : "yes",
   editor_id : tinyMCE.activeEditor.id
 });
 
 return false;
}
