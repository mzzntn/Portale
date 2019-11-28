# -*- encoding : utf-8 -*-
module Moduli

    class Dato < WidgetCampo
        tag 'dato'
        is_attribute :tipo

        def load_widgets(template=@template)
            super
            @tipo ||= 'default'
            atr = {}
            case @tipo.to_sym
            when :int
                wt = Spider::Forms::Text
            when :longText
                wt = Spider::Forms::TextArea
            when :data
                wt = Spider::Forms::DateTime
                atr[:"change-month"] = true
                atr[:"change-year"] = true
            else
                wt = Spider::Forms::Text
            end
            atr[:size] = @size if @size
            @input = create_widget(wt, @id.to_sym)
            @input.name = '_w'+param_name(self)
            @input.value = @valore if @valore
            @scene.input = @input
            @widget_attributes[@input.id] ||= {}
            @widget_attributes[@input.id] = atr.merge(@widget_attributes[@input.id])
        end


        # carico da db le dimensioni delle textarea auto adattative, riga con altezza di 22px 
        def set_dimensioni(dimensioni)
            if @tipo.to_sym == :longText && (!self.attributes[:class].blank? && self.attributes[:class].split().include?('adaptive_input'))
                if dimensioni.blank?
                    @input.rows = 1
                else
                    hash_dim = JSON.parse(dimensioni)
                    #carico l'id completo salvato in db
                    id = self.full_id+"-"+self.id
                    #carico il numero di righe
                    @input.rows = (hash_dim[id]) unless hash_dim[id].blank?
                end
            else
                # imposto la dimensione negli altri campi di testo se trovo l'id nel campo dimensioni
                if dimensioni.blank?
                    @input.size = "25" #dimensione di default
                else
                    hash_dim = JSON.parse(dimensioni)
                    #carico l'id completo salvato in db
                    id = self.full_id+"-"+self.id
                    #carico il numero di righe
                    @input.size = (hash_dim[id]) if !hash_dim[id].blank? && @input.respond_to?(:size)
                    #se sto usando una textarea normale faccio cambiare dinamicamente il numero di righe
                    @input.rows = hash_dim[id] if !hash_dim[id].blank? && @input.respond_to?(:rows) && @input.is_a?(Spider::Forms::TextArea)
                end    

            end
            
        end


    end

end

