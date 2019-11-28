# -*- encoding : utf-8 -*-
module Moduli

    class WidgetModulo < Spider::Widget
        is_attribute :stile
        is_attribute :autohide
        
        def prepare
            super
            @nome = @id
            if @autohide
                @css_classes << 'autohide'
            end
        end

        #metodo chiamato su una widget per assegnare i dati di un modulo
        #caricato dal db in tabella ModuloSalvato widget.dati = modulo.dati
        def dati(dati_caricati, dimensioni=nil)
            dati_caricati = JSON.parse(dati_caricati) if dati_caricati.is_a?(String)
            @dati = dati_caricati
            imposta_dati(dati_caricati,dimensioni) if @init_widgets_done
        end


        def imposta_dati(dati, dimensioni_caricate=nil)
            dati.each do |k, v|
                w = widgets[k.to_sym]
                if w
                    if w.is_a?(WidgetModulo) && v.is_a?(Hash) && w.class != Moduli::Allegato
                        w.dati(v, dimensioni_caricate)
                    #caso in cui salvo definitivamente e ho già allegato un file e ho errore per mancanza di campi obbligatori     
                    elsif w.respond_to?(:valore=) && w.class == Moduli::Allegato && ( v.is_a?(Hash) && v.has_key?(w.id) ) 
                        #v[w.id] = File.basename(w.input.value)
                        unless v[w.id]['file'].blank?
                            w.valore = File.basename(w.input.value)
                        else
                            w.valore = File.basename(v[w.id]['file_name']) unless v[w.id]['file_name'].blank?
                        end
                    elsif w.respond_to?(:valore=) #se nella widget è definito un metodo valore= uso quello, passando 1 ad una opzione arriva in questo caso
                        w.valore = v
                    else
                        w.value = v # input, assegno direttamente all'attributo il valore v
                    end
                else
                    params[k] = v
                end
                if !dimensioni_caricate.blank? && w.respond_to?(:set_dimensioni)
                    w.set_dimensioni(dimensioni_caricate)
                end
            end
        end

        #aggiorna gli allegati in base ai dati passati come json
        def aggiorna_dati=(dati)
            dati = JSON.parse(dati) if dati.is_a?(String)
            aggiorna_allegati(dati) if @init_widgets_done 
        end

        #dati contiene i dati dal db, questo metodo viene chiamato quando si salva il modulo
        def aggiorna_allegati(dati)
            unless dati.blank?
                dati.each do |k, v|
                    w = widgets[k.to_sym]
                    if w
                        if w.is_a?(WidgetModulo) && v.is_a?(Hash)
                            w.aggiorna_dati = v
                        elsif w.is_a?(Moduli::Allegato)
                            #se ho cancellato il file col 'Pulisci' metto il valore a nil, altrimenti 
                            #se il valore blank ricarico il valore di prima
                            if w.input.value == 'cancellato'
                                w.input.value = nil
                            elsif w.input.value.blank?
                                w.input.value = v
                            end
                        end
                    # else
                    #     params[k] = v
                    end
                end
            end
        end


        def init_widgets(*args)
            super
            imposta_dati(@dati) if @dati
        end

        def serializza
            hash = {}
            if @input
                hash = self.params
            end
            @widgets.each do |id, widget|
                next if widget == @input
                if widget.respond_to?(:serializza)
                    hash[id] = widget.serializza
                else
                    if widget.respond_to?(:params)
                        #controllo se un hash per problemi con widget data-firma 
                        hash[id] = widget.params if hash.is_a?(Hash)
                    end
                end
            end
            codifica_hash_utf8(hash)
        end

        def allegati
            res = []
            @widgets.each do |id, widget|
                if widget.class == Moduli::Allegato
                    res << widget
                elsif widget.respond_to?(:allegati)
                    res += widget.allegati
                end
            end
            res
        end
        
        def codifica_hash_utf8(hash)
            return hash if hash.blank?
            return ( hash.is_a?(String) && hash.respond_to?(:force_encoding) ? hash.force_encoding('UTF-8') : hash ) if hash.is_a?(String)
            hash.each_pair { |k, v| 
                if v.class == Hash
                    hash[k] = codifica_hash_utf8(v)
                else
                    #se ho un file non posso fargli il force_encoding('UTF-8') perchè viene letto da rack con encoding ASCII-8BIT (BINARY)
                    #se ho passato un UploadedFile lo tolgo dai parametri per non dover fare il to_json che da errore
                    if v.is_a?(Spider::UploadedFile)
                        hash[k] = (!v.filename.blank? && v.filename.respond_to?(:force_encoding) && v.filename.is_a?(String) ? v.filename.force_encoding('UTF-8').encode('UTF-8') : v.filename)
                    else
                        hash[k] = (v.respond_to?(:force_encoding) && v.is_a?(String) ? v.force_encoding('UTF-8') : v)
                    end
                end
            }
            hash
        end

    end

end
