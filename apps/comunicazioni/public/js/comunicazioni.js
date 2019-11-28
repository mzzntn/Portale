
function isBlank(str) {
    return (!/\S/.test(str));
};

(function($) {
    $.fn.ellipsis_vert = function(){
        // //Versione solo testo 
        // this.each(function(index) {
        //     //alert(index + ': ' + $(this).text());
        //     var orig_text = $.trim($(this).html());
        //     var n_car = (orig_text).length;
        //     if(n_car > 350){
        //         alert($(this).html());
        //         $(this).html(orig_text.substr(0, 350)+ "...");
        //         alert($(this).html());
        //     }
        // });
        this.each(function(index) {
            nuovo_html="";
            //rawData =  rawData.replace(/[<>^a-zA-Z 0-9]+/g,'');
            stringa = $(this).html();
            //$.htmlClean.defaults.formatIndent = 0;
            stringa = $.htmlClean(stringa, { format: true});
            stringa = stringa.replace(/\t/g,"");
            arr = stringa.split('');
            var i=0;
            var j=0;
            //conta 7 caratteri in meno
            max_car = 250;
            tot_car = 250;
            lungh = arr.length;
            while(i<lungh && tot_car!=0)
            {
                nuovo_html=nuovo_html+arr[i];
                if(arr[i]=="<"){
                    j=i;
                    while(arr[j]!=">"){
                        j++;
                        nuovo_html=nuovo_html+arr[j];
                    }
                    i=j;
                }
                else{
                    tot_car--;
                }
                i++;
            }
            if(tot_car==0){
                nuovo_html+="...";
            }
            //alert(nuovo_html);
            $(this).html(nuovo_html);
        });
    };
    
})(jQuery);    
    

$(document).ready(function(){
    
    if($(".well_testo_comunicazione").length > 0) {
        $(".well_testo_comunicazione").ellipsis_vert();
    };

   

});
