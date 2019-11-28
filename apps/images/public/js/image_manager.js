Spider.defineWidget('Spider.Images.ImageManager', 'Spider.Files.FileManager', {
	tmpName: 'imageManagerWidger',
	
	autoInit: true,
	
	
	startup: function(){
		this._super();
		var self = this;
		var viewerWidget = this.widget('viewer');
		this.onWidget('viewer', function(w){
			w.on('previewImageLoaded', function(){
				self.trigger('imageViewed');
			});
		});
		this.onWidget('search', function(w){
			w.onReady(function(){
				self.ajaxify($('.image-link', w.el));
			});
		});
        this.onWidget('archive/table', function(w){
            w.onReady(function(){
                self.ajaxify($('.image-link', w.el));
            });
        });
        if (window.opener && window.opener.CKEDITOR){
            self.on('saved', function(img){
                var paramName = 'CKEditorFuncNum';
                var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
                var match = window.location.search.match(reParam) ;

                var funcNum = (match && match.length > 1) ? match[1] : '' ;
                
                window.opener.CKEDITOR.tools.callFunction( funcNum, img.url, function() {
                  // Get the reference to a dialog.
                  var element, dialog = this.getDialog();
                  // Check if it is an Image dialog.
                  if (dialog.getName() == 'image') {
                    // Get the reference to a text field that holds the "alt" attribute.
                    element = dialog.getContentElement( 'info', 'txtAlt' );
                    // Assign the new value.
                    if ( element )
                      element.setValue( img.params.title );
                  }
                    
                  // Return false to stop further execution - in such case CKEditor will ignore the second argument (fileUrl)
                  // and the onSelect function assigned to a button that called the file browser (if defined).
                  //return false;
                });
                window.close();
            });
        }

		// this.on('ajaxifyLoad', function(el){
		// 	if (el.get(0).tagName == 'img' && el.parent().hasClass('image-link')){
		// 		self.trigger('imageViewed', el);
		// 	}
		// });
	}
	
});