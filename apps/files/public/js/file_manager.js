Spider.defineWidget('Spider.Files.FileManager', {
	
	autoInit: true,
	
	startup: function(){
		var self = this;
		this.onWidget('viewer', function(w){
			w.on('saved', function(saved){
				self.trigger('saved', saved);
			});
			w.on('previewImageLoaded', function(){
				self.trigger('fileViewed');
			});
		});	
		this.onWidget('upload', function(w){
			w.bind('uploaded', function(res){
				self.reload();
			});
		});
		this.onWidget('archive/list', function(w){
            w.onReady(function(){
                self.ajaxify($('.file-link', w.el));
            });
        });
        if (window.opener && window.opener.CKEDITOR){
            self.on('saved', function(saved){
                var paramName = 'CKEditorFuncNum';
                var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
                var match = window.location.search.match(reParam) ;

                var funcNum = (match && match.length > 1) ? match[1] : '' ;
                window.opener.CKEDITOR.tools.callFunction(funcNum, saved.url);
                window.close();
            });
        }
	},
	
	ready: function(){
		var self = this;
		this.ajaxify();
		
		// var uploadWidget = this.widget('upload');
		// var browserWidget = this.widget('browser');
		// var viewerWidget = this.widget('viewer');
		// uploadWidget.el.hide();
		// $('.button_upload', this.el).click(function(e){
		// 	e.preventDefault();
		// 	self.tabSwitch('upload');
		// });
		// $('.button_files', this.el).click(function(e){
		// 	e.preventDefault();
		// 	self.tabSwitch('files');
		// });
		// this.widget('upload').el.bind('fileUploaded', function(res){
		// 	self.tabSwitch('viewer');
		// });
		// viewerWidget.onReady(function(){
		// 	if (!this.isLoaded()) return;
		// 	this.ajaxifyForm($('form', this.el), {
		// 		onLoad: function(){
		// 			self.tabSwitch('files');
		// 			browserWidget.reload();
		// 		}
		// 	});
		// });
	}
	
	// ,
	// 
	// tabSwitch: function(name){
	// 	var uploadWidget = this.widget('upload');
	// 	var browserWidget = this.widget('browser');
	// 	var viewerWidget = this.widget('viewer');
	// 	if (name == 'upload'){
	// 		browserWidget.el.hide();
	// 		viewerWidget.el.empty().hide();
	// 		uploadWidget.el.show();
	// 		if (!uploadWidget.isLoaded()) uploadWidget.reload();
	// 		else uploadWidget.reset();
	// 	}
	// 	else if (name == 'files'){
	// 		viewerWidget.el.empty().hide();
	// 		uploadWidget.el.empty().hide();
	// 		if (browserWidget.el.is(':empty')) browserWidget.reload();
	// 		browserWidget.el.show();
	// 	}
	// 	else if (name == 'viewer'){
	// 		uploadWidget.el.hide();
	// 		uploadWidget.reset();
	// 		viewerWidget.el.show();
	// 		viewerWidget.reload();
	// 	}
	// 	this.trigger('tabSwitch', name);
	// }
	
});