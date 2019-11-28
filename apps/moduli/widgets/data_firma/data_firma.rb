# -*- encoding : utf-8 -*-
module Moduli

    class DataFirma < WidgetCampo
        tag 'data-firma'
        is_attribute :"label-firma", :default => 'Il richiedente'

        def prepare
            super
            @widgets[:data].value ||= Date.today
        end

        #aggiunto per caricare nome e cognome nel campo
        def load_widgets(template=@template)
            super
            atr = {}
            wt = Spider::Forms::Text
            @input = create_widget(wt, @id.to_sym)
            @input.name = '_w'+param_name(self)
            @input.value = @valore if @valore
            @scene.input = @input
        end

    end

end

