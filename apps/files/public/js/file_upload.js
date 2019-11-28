Spider.defineWidget('Spider.Files.FileUpload', {
	
	autoInit: true,
	
	ready: function(){
		var self = this;
		var elId = this.el.attr('id');
		var chooseButton = $('.choose_button', this.el);
		if (chooseButton.length > 0){
			$('.choose_button', this.el).attr('id', elId+'_browseButton');
			var config = {
				runtimes: 'gears,html5,flash,silverlight,browserplus',
				required_features: 'progress',
				browse_button : elId+'_browseButton',
				multi_selection: false,
				max_file_size : '100mb',
				multipart: false,
				url: this.backend.urlForMethod('upload'),
				// resize : {width : 320, height : 240, quality : 90},
				flash_swf_url : Spider.baseUrl+'/spider/files/public/plupload/js/plupload.flash.swf',
				silverlight_xap_url : Spider.baseUrl+'/spider/files/public/plupload/js/plupload.silverlight.xap',
				filters: []
				// filters : [
				// 	{title : "Image files", extensions : "jpg,gif,png"},
				// 	{title : "Zip files", extensions : "zip"}
				// ]
			};
			this.uploader = new plupload.Uploader(config);
			this.uploader.bind('Init', function(up, params) {
				this.plUploadParams = params;
                // console.log("Runtime: "+params.runtime);
				//$('.filelist', self.el).html("<div>Current runtime: " + params.runtime + "</div>");
			});
			this.uploader.bind('UploadFile', function(up, file){
				if($('.progressbar') != null) $('.progressbar').progressbar({value: 0});
			});
			this.uploader.bind('UploadProgress', function(up, file) {
				$('.progressbar').progressbar({'value': file.percent});
				/* distruzione tolta, sembra funzionare tutto lo stesso */
				/*if(file.percent == 100 ) $('.progressbar').progressbar('destroy');*/
			});
			this.uploader.bind('FileUploaded', function(up, file, res){
				if (res.response){
					self.trigger('uploaded', res.response);
				}
				
			});
			this.uploader.bind("Error", function(err){
			    chooseButton.remove();
                var msg = "<br>Siamo spiacenti, non è stato possibile attivare il componente di upload.<br><br> ";
                msg += "Ti preghiamo di installare ";
                msg += "<a href=\"http://www.adobe.com/go/getflash/\" target=\"_blank\">Adobe Flash Player</a>";
                msg +=" o <a href=\"http://www.microsoft.com/getsilverlight/\" target=\"_blank\">Microsoft Silverlight.</a>";
                if (err.message) msg += "<br><br>"+err.message;
                self.el.html(msg);
            });
			$('.upload_button', this.el).click(function(e){
				e.preventDefault();
				self.uploader.start();
			});
			this.uploader.init();
			this.uploader.bind('FilesAdded', function(up, files){
				$.each(files, function(i, file) {
					$('.filelist').show();
					$('.filelist .file_name', this.el).text(file.name);
					$('.filelist .file_size', this.el).text(file.size);
					// $('.filelist', this.el).append(
					// 	'<div id="' + elId + '_' + file.id + '">' +
					// 	file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
					// '</div>');
				});
			    chooseButton.hide();
				self.uploader.start();
			});
		}

	},
	
	reset: function(){
		$('.filelist', this.el).html("<div></div>");
		this.uploader.refresh();
	}
	
});