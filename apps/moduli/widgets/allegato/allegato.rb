# -*- encoding : utf-8 -*-
begin
    require 'ftools'
rescue LoadError
end

module Moduli

    class Allegato < WidgetCampo
        tag 'allegato'
        is_attribute :save_path
        #questo attr_reader rende input accessibile
        attr_reader  :input

        def load_widgets(template=@template)
            super
            wt = Spider::Forms::FileInput
            @input = create_widget(wt, @id.to_sym)
            #imposto il save_path nella widget appena creata che ha un is_attr_accessor :save_path
            #salvo nella cartella data e non in var
            #full_path = File.join(Spider.paths[:var],'/data/uploaded_files/',@save_path,@request.session["hashdir_moduli_allegati"])
            raise "Sessione scaduta" if @request.session.blank? || @request.session["hashdir_moduli_allegati"].blank?
            full_path = File.join(Spider.paths[:data],'/uploaded_files/moduli',@request.session["hashdir_moduli_allegati"])
            FileUtils.mkdir_p(full_path) unless File.directory?(full_path)
            @input.attributes[:save_path] = full_path
            @scene.input = @input
            @input.value = @valore if @valore

        end

        #carico il nome del file dal db e ci concateno prima il percorso 
        def valore=(val)
            unless val.blank?
                full_path = File.join(Spider.paths[:data],'/uploaded_files/moduli',@request.session["hashdir_moduli_allegati"],'/',val)
                @valore = Spider::DataTypes::FilePath.new(full_path)
                @valore.attributes[:base_path] = File.join(Spider.paths[:data],'/uploaded_files/moduli',@request.session["hashdir_moduli_allegati"])
                @input.value = @valore if @input
            end    
        end

        #metodo che salva nel database il valore del campo file_input, salvo solo il nome del file e non il path
        def serializza
            unless @input.value.to_s.blank?
                nome_file = File.basename(@input.value.to_s)
                nome_file.respond_to?(:force_encoding) && nome_file.is_a?(String) ? nome_file.force_encoding('UTF-8') : nome_file
            end
        end

    end

end

