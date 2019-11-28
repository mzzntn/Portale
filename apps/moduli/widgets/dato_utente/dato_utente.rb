# -*- encoding : utf-8 -*-
module Moduli

    class DatoUtente < WidgetCampo
        tag 'dato-utente'

        def prepare
            @campo = @id
            super
        end

        def load_widgets(template=@template)
            super
            atr = {}
            case @campo.to_sym
            when :nome_cognome
                wt = Spider::Forms::Text
                atr[:size] = 80
            when :luogo_nascita
                wt = Spider::Forms::Text
            when :prov_nascita
                wt = Spider::Forms::Text
            when :data_nascita
                wt = Spider::Forms::DateTime
                atr[:"change-month"] = true
                atr[:"change-year"] = true
            when :codice_fiscale
                wt = Spider::Forms::Text
            when :luogo_residenza
                wt = Spider::Forms::Text
            when :prov_residenza
                wt = Spider::Forms::Text
            when :indirizzo_residenza
                wt = Spider::Forms::Text
            when :civico_residenza
                wt = Spider::Forms::Text
            when :numero_telefono
                wt = Spider::Forms::Text
            when :comune
                wt = Spider::Forms::Text
            when :cap
                wt = Spider::Forms::Text
            when :email
                wt = Spider::Forms::Text
            when :cellulare
                wt = Spider::Forms::Text
            end
            atr[:size] = @size if @size
            @input = create_widget(wt, @campo.to_sym)
            @input.name = '_w'+param_name(self)
            @input.value = @valore if @valore
            @scene.input = @input
            @widget_attributes[@input.id] ||= {}
            @widget_attributes[@input.id] = atr.merge(@widget_attributes[@input.id])
        end

        def self.get_id(el)
            el.get_attribute('campo')
        end

    end

end

