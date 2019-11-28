Spider.defineWidget('Spider.Images.ImageArchive', {
	
	autoInit: true,
	
	ready: function(){
		this.ajaxify($('.tags a', this.el));
	}
	
});