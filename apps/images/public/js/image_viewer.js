Spider.defineWidget('Spider.Images.ImageViewer', 'Spider.Files.FileViewer', {
	
	autoInit: true,
	
	ready: function(){
	    this._super();
	    this.originalWidth = parseInt($('.image-width', this.el).text(), 10);
	    this.originalHeight = parseInt($('.image-height', this.el).text(), 10);
	    this.ratio = this.originalWidth/this.originalHeight;
	    var self = this;
	    $('.width-input').keyup(function(){
	        if (!$('.keep-ratio-checkbox', self.el).is(':checked')) return;
	        var height = Math.round(parseInt($(this).val(), 10)/self.ratio);
            if (isNaN(height)) return;
            $('.height-input').val(height);
	    });
	    $('.height-input').keyup(function(){
	        if (!$('.keep-ratio-checkbox', self.el).is(':checked')) return;
	        var width = Math.round(parseInt($(this).val(), 10)*self.ratio);
            if (isNaN(width)) return;
            $('.width-input').val(width);
	    });
	}
	
});