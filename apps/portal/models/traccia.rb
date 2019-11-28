# -*- encoding : utf-8 -*-
module Portal

    class Traccia < Spider::Model::Managed
        element :utente, Portal::Utente, :add_multiple_reverse => :log_applicazioni
        element :ip, String
        element :pagina, String
        element :parametri, Text
        #:computed_from dice che è calcolato a partire da :pagina col metodo pagina_tracciata
        element :pagina_tracciata, String, :computed_from => [:pagina], :label => 'Pagina Tracciata'
        element :parametri_ricercati, String, :computed_from => parametri
        #id del pagamento
        element :id_transazione_app, String
        element :tipologia_servizio, String
        element :tipologia_richiesta, String


        #serve per tradurre i nomi reali delle views in nomi pagina parlanti
        def pagina_tracciata
            case pagina
            when 'index'
                'Home Page'
            else
                pagina.capitalize
            end
        end

        def parametri_ricercati
            return {} if parametri.blank?
        	#converto la stringa dei parametri in un hash
        	str_parametri_ricercati = ""
        	hash_parametri = JSON.parse(parametri)
        	hash_parametri.each{ |key, value|
        		if !value.blank? && !['cerca','_w','_wt','current_page'].include?(key)
        			if str_parametri_ricercati.blank?
        				str_parametri_ricercati = "#{key}=#{value}, "
        			else
        				str_parametri_ricercati = str_parametri_ricercati+"#{key}=#{value}, "
        			end
        		end
        	}
        	str_parametri_ricercati.gsub(/[,]\s$/,'')
        end


        def self.salva_traccia(ip, pagina, utente_portale, parametri, id_transazione_app, tipologia_servizio, tipologia_richiesta)
            self.create(
                :ip => ip,
                :pagina => pagina,
                :utente => utente_portale,
                :parametri => parametri,
                :id_transazione_app => id_transazione_app,
                :tipologia_servizio => tipologia_servizio,
                :tipologia_richiesta => tipologia_richiesta
            )
        end



    end

end