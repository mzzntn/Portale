Spider.defineWidget('Spider.Files.FileViewer', {
	
	autoInit: true,
	
	ready: function(){
		var w = this;
		var form = $('form', this.el);
		$('input[type=submit]', form).click(function(e){
			e.preventDefault();
			w.submitForm();
		});
		$('img', this.el).load(function(e){
			w.trigger('previewImageLoaded');
		});
	},
	
	
	submitForm: function(callback){
		var descr = $('input.title');
		if (descr.hasClass('required') && !descr.val()){
			return this.error('title_required');
		}
		this.resetErrors();
		this.setSaving();
		this.trigger('submit');
		var w = this;
		var form = $('form', this.el);
		var data = form.formToArray();
		var params = {};
		for (var i=0; i<data.length; i++){
			params[data[i].name] = data[i].value;
		}
		w.remote('save', params, function(res){
			w.removeSaving();
			if (callback) callback(res.saved);
			w.trigger('saved', res.saved);
		});
	},
	
	setSaving: function(){
		this.el.addClass('saving');
		$('form input[type=submit]', this.el).attr('disabled', true);
		try{
		    $('.saving_div', this.el).show(); 
		    
            // .Loadingdotdotdot({
            //              "speed": 400,
            //              "maxDots": 3
            //          });
        } catch(err){ };
	},
	
	removeSaving: function(){
		this.el.removeClass('saving');
		$('form input[type=submit]', this.el).attr('disabled', false);
		try{
		    $('.saving_div', this.el).hide(); //.Loadingdotdotdot("Stop");
        } catch(err){ };
	},
	
	error: function(name){
		$('.errors', this.el).show();
		$('.errors .error_'+name, this.el).show();
	},
	
	resetErrors: function(name){
		$('.errors').hide();
		$('.errors div').hide();
	}
	
});