Spider.defineWidget('Moduli.Opzione', {
    
    autoInit: true,
    
    ready: function(){
        var self = this;
        this.input = $('>.input>input[type=checkbox]', this.el);
        this.input.bind('change', function(e){
            if (self.input.is(':checked')){
                self.onCheck();
            }
            else{
                self.onUncheck();
            }
        });
        $('>.autohide', this.el).hide();
        /* se c'è il check, come negli allegati, mostro il pezzo col file input */
        if (self.input.is(':checked')){
            $('>.autohide', this.el).show();
        }
    },
    
    onCheck: function(){
        $('>.autohide', this.el).show();
    },

    onUncheck: function(){
        $('>.autohide', this.el).hide();
    }
    
    
});