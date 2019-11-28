Spider.defineWidget('Spider.Files.FileArchive', {
	
	autoInit: true,
	
	ready: function(){
		this.ajaxify($('.tags a', this.el));
	}
	
});
