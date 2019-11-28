# -*- encoding : utf-8 -*-
begin
    require 'ftools'
rescue LoadError
end

module Moduli

    class ModuloSalvato < Spider::Model::Managed
        
        element :chiave, String, :default => Proc.new{ SecureRandom.hex(12) }
        element :utente, Portal::Utente, :add_multiple_reverse => :moduli
        element :dati, Text #bisogna convertirlo in longtext a mano in db
        element :dimensioni, Text
        element :inviato, DateTime
        element :confermato, DateTime
        element :stampato, Spider::Bool
        element :hashdir_allegati, String
        element :numero_modulo, Integer
        element :stato, Spider::OrderedHash[
            'bozza', 'Salvato come bozza',
            'confermato', 'Modulo non inviato', #'Salvataggio confermato', #Modificato 12/12/2018 per problema di invio mail che fa fare rollback a salvataggio, non diventa inviato e resta sempre confermato ma è errore
            'da_pratiche', 'Salvato in Pratiche',
            'da_firmare', 'Da Firmare',
            'firmato', 'Firmato',
            'da_pagare', 'Da Pagare',
            #'pagato', 'Pagato', #non servirebbe, se i pagamenti sono pagati invio il modulo
            'inviato', 'Inviato'
        ]
        element :id_pratica, Integer  #usato per contenere l'id della pratica
        element :id_iscrizione, String #usato per contenere l'id dell'iscrizione muse
        element :importi, Text #contiene un json con i vari importi, presi da tipo_modulo -> importi
        element :modulo_firmato, String
        element :spid_code, String #contiene l'informazione se l'utente che ha inviato il modulo si è autenticato tramite Spid
        element :completare_servizi_titoli, Spider::Bool #se true si va nella pagina per servizi e titoli, se false e campi servizi_svolti e titoli_studio non vuoti
                                                         #si considera che siano stati inseriti servizi e titoli studio
        element :servizi_svolti, Text #contiene json con servizi svolti per bandi
        element :titoli_studio, Text #contiene json con titoli studio per bandi
        element :titoli_vari, Text #contiene json con titoli vari per bandi
        #definisco dei bigdecimal che vengono presi da mysql come DECIMAL(M,D) con precision = M e scale = D, 
        #Precision è il numero di cifre totali e scale i decimali da usare. Spider::DataTypes::Decimal ha la scale fissa a 2 e fa casini
        element :punteggio_servizi, ::BigDecimal, :precision => 20, :scale=> 11, :default => 0
        element :punteggio_titoli, ::BigDecimal, :precision => 20, :scale=> 11, :default => 0
        element :punteggio_titoli_vari, ::BigDecimal, :precision => 20, :scale=> 11, :default => 0
        element :punteggio_totale, ::BigDecimal, :precision => 12, :scale=> 6, :default => 0
        element :protocollo_numero, String
        element :protocollo_data, DateTime
        element :protocollo_id, Integer #Serve per Folium in quanto è l'id interno di protocollazione da
                                       #usare per l'invio degli eventuali Allegati
        
        element :extra_dati, Text #contiene in formato json vari dati usati dall'applicazione. Es riquadri_opzionali
        
        if defined?(Pagamenti) != nil
            many :pagamenti_collegati, ::Pagamenti::Pagamento, :add_multiple_reverse => :modulo_collegato #TOLTO ADD_REVERSE CHE NON FUNZIONA..
                                                             # con add_multiple_reverse funziona e si passa per la tabella di raccordo..
        end
        # def tipo_modulo
        #     @tipo_modulo ||= TipoModulo.new(self.tipo)
        # end


        def before_delete
            #cancello la cartella degli allegati
            dir_allegati = File.join(Spider.paths[:data],'/uploaded_files/moduli', self.hashdir_allegati)
            FileUtils.remove_dir(dir_allegati) if File.directory?(dir_allegati)
            #cancello fisicamente i pagamenti se cancello il modulo
            if defined?(Pagamenti) != nil && self.respond_to?(:pagamenti_collegati) && !self.pagamenti_collegati.blank?
                self.pagamenti_collegati.each{ |pagamento_collegato|
                    #cancello fisicamente un pagamento se pendente, non_eseguito. Cancello il dovuto. Cancello la transazione collegata se non_eseguito
                    if pagamento_collegato.stato == 'pendente' || pagamento_collegato.stato == 'non_eseguito'
                        #cancello dovuto
                        dovuto_da_cancellare = pagamento_collegato.dovuto
                        dovuto_da_cancellare.delete unless dovuto_da_cancellare.blank?
                        #cancello transazioni se presente
                        unless pagamento_collegato.transazioni.blank?
                            pagamento_collegato.transazioni.each{ |trans_collegata|
                                trans_collegata.delete
                            }
                        end
                        #cancello pagamento se cancellabile
                        pagamento_collegato.delete
                    end
                }
            end
        end 

        def allegati_salvati(url_type=nil)
            folder = File.join(Spider.paths[:data],'/uploaded_files/moduli', self.hashdir_allegati)
            return nil unless File.directory? folder
            #nomi_file = Dir.entries(folder).select{|f| !File.directory? f }
            nomi_file = Dir.glob(folder+"/*.*")
            case url_type
            when "assoluto"
                nomi_file
            #when "relativo"
            else
                nomi_relativi_url = []
                nomi_file.each{ |nome_allegato| nomi_relativi_url << File.basename(nome_allegato) }
                nomi_relativi_url
            end
        end


    end

end

