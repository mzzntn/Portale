# -*- encoding : utf-8 -*-
module Portal
    
    class Anagrafica < Spider::Model::Managed     
        
        label 'Anagrafica Utente', 'Anagrafiche Utenti'
        
        element :nome, String, :check => /^[-.A-Za-zÀÁÈÉËÍÌÓÒÚÙáàèéíìóòúù'\s]+$/
        element :cognome, String#, :check => /^[-.A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/ non metto controllo di formato, potrei avere una ragione sociale
        element :nome_cognome, String, :label => "Nome e Cognome", :hidden => true
        choice :stato, Spider::OrderedHash[
            'attivo', 'Attivo',
            'archiviato', 'Archiviato',
            'disabilitato','Disabilitato'
        ], :default => 'attivo'
        choice :tipo_anagrafica, Spider::OrderedHash[
            'F', 'Fisica',
            'G', 'Giuridica'
        ], :default => 'F', :check => /^[F-G]{1}/
        #controlla sia il formato del codice fiscale e sia la partita iva come sequenza di cifre
        element :codice_fiscale, String, :label => 'Codice fiscale' , :check => { "Formato Codice Fiscale non valido" => /^(A$|([a-zA-Z]{6}[\da-zA-Z]{2}[abcdehlmprstABCDEHLMPRST][\da-zA-Z]{2}[a-zA-Z][\da-zA-Z]{3}[a-zA-Z])|[0-9]{11})$/ } 
        
        element :via_residenza, String, :label => 'Via'
        element :civico_residenza, String, :label => 'Civico'
        element :comune_residenza, String, :label => 'Comune', :check => /^[-.\/A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/
        element :cap_residenza, String, :label => 'C.A.P.', :check => /^[0-9]+$/
        element :prov_residenza, String, :label => 'Prov.'
        element :nazione_residenza, String, :label => 'Nazione'
        
        element :email, String, :label => 'E-mail', :check => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/
        #relazione che serve per registrare lato genitore i figli per cui si paga, lato bambino i genitori
        #n a n così se una mamma deve pagare un dovuto inizialmente a nome del marito si può aggiungere al bambino anche l'anagrafica della mamma
        #poi sia la mamma che il papà vedranno il bambino, nei pagamenti viene indicato se ha pagato la mamma o il papà. 
        #viene creata una tabella portal__anagrafica__familiari dove anagrafica_id ha l'id del genitore, portal__anagrafica__id ha l'id del figlio
        many :familiari, Anagrafica, :add_multiple_reverse => :genitori do 
            element :stato_relazione, String
        end

        #dati delle preferenze sui psp che ha usato l'utente
        element :id_psp, String, :label => 'Identificativo PSP preferito'
        element :tipo_versamento, Spider::OrderedHash[
            'AD', 'Addebito diretto',
            'BBT', 'Bonifico Bancario di Tesoreria',
            'BP', 'Bollettino Postale',
            'CP', 'Carta di pagamento',
            'OBEP', 'On-line banking e-payment',
            'PO', 'Pagamento attivato presso PSP'
        ]

        def nome_cognome
            return nil if self.nome.blank? && self.cognome.blank?
            nome = self.nome.blank? ? "" : "#{self.nome} "
            cognome = self.cognome.blank? ? "" : self.cognome
            "#{nome}#{cognome}"
        end

        def nome_cognome=(val)
            if self.tipo_anagrafica.id == 'F'
                self.nome, self.cognome = val.strip.split(' ') if self.nome.blank? && self.cognome.blank?
            else #se una persona giuridica ci può essere qualsiasi carattere
                self.cognome = val.strip
            end
            return val
        end

        def self.get_anagrafica(dati_anagrafici)
            return nil if dati_anagrafici.blank?
            #se dati_anagrafici è un istanza di anagrafica ritorno l'istanza
            if dati_anagrafici.is_a?(Portal::Anagrafica)
                istanza = Anagrafica.load(:id => dati_anagrafici.id)
                return istanza unless istanza.blank?
            end
            #se ho Pagamenti nella versione con PagoPA
            if !defined?(CtSoggettoPagatore).blank?
                #Gestione anagrafica: se arriva un oggetto con stesso cf controllo se i dati sono cambiati
                nuova_anagrafica = Anagrafica.new
                if dati_anagrafici.is_a?(CtSoggettoPagatore)
                    nuova_anagrafica.codice_fiscale = dati_anagrafici.identificativoUnivocoPagatore['codiceIdentificativoUnivoco']
                    nuova_anagrafica.nome_cognome = dati_anagrafici.anagraficaPagatore
                    nuova_anagrafica.via_residenza = dati_anagrafici.indirizzoPagatore
                    nuova_anagrafica.civico_residenza = dati_anagrafici.civicoPagatore
                    nuova_anagrafica.cap_residenza = dati_anagrafici.capPagatore
                    nuova_anagrafica.comune_residenza = dati_anagrafici.localitaPagatore
                    nuova_anagrafica.prov_residenza = dati_anagrafici.provinciaPagatore
                    nuova_anagrafica.nazione_residenza = dati_anagrafici.nazionePagatore
                    nuova_anagrafica.email = dati_anagrafici.e_mailPagatore
                    #vedo se c'è già l'anagrafica salvata
                    anag_presente = Anagrafica.load(:codice_fiscale => dati_anagrafici.identificativoUnivocoPagatore['codiceIdentificativoUnivoco'], :stato => 'attivo')
                    unless anag_presente.blank?
                        #Se ricevo un CF == ANONIMO allora non aggiorno l'anagrafica
                        if  dati_anagrafici.identificativoUnivocoPagatore['codiceIdentificativoUnivoco'].downcase == 'anonimo'
                            return anag_presente
                        end
                        #controllo se ci sono stati cambiamenti
                        params = {}
                        params['nome_cognome'] = dati_anagrafici.anagraficaPagatore
                        params['codice_fiscale'] = dati_anagrafici.identificativoUnivocoPagatore['codiceIdentificativoUnivoco']
                        params['via_residenza'] = dati_anagrafici.indirizzoPagatore
                        params['civico_residenza'] = dati_anagrafici.civicoPagatore
                        params['comune_residenza'] = dati_anagrafici.localitaPagatore
                        params['cap_residenza'] = dati_anagrafici.capPagatore
                        params['prov_residenza'] = dati_anagrafici.provinciaPagatore
                        params['nazione_residenza'] = dati_anagrafici.nazionePagatore
                        params['email'] = dati_anagrafici.e_mailPagatore
                        anag_presente = anag_presente.versiona(params)
                        return anag_presente
                    else #salvo la nuova anagrafica e la ritorno
                        nuova_anagrafica.save
                        return nuova_anagrafica
                    end

                end
                if dati_anagrafici.is_a?(CtSoggettoVersante)
                    nuova_anagrafica.codice_fiscale = dati_anagrafici.identificativoUnivocoVersante['codiceIdentificativoUnivoco']
                    nuova_anagrafica.nome_cognome = dati_anagrafici.anagraficaVersante
                    nuova_anagrafica.via_residenza = dati_anagrafici.indirizzoVersante
                    nuova_anagrafica.civico_residenza = dati_anagrafici.civicoVersante
                    nuova_anagrafica.cap_residenza = dati_anagrafici.capVersante
                    nuova_anagrafica.comune_residenza = dati_anagrafici.localitaVersante
                    nuova_anagrafica.prov_residenza = dati_anagrafici.provinciaVersante
                    nuova_anagrafica.nazione_residenza = dati_anagrafici.nazioneVersante
                    nuova_anagrafica.email = dati_anagrafici.e_mailVersante
                    #vedo se c'è già l'anagrafica salvata
                    anag_presente = Anagrafica.load(:codice_fiscale => dati_anagrafici.identificativoUnivocoVersante['codiceIdentificativoUnivoco'], :stato => 'attivo')
                    unless anag_presente.blank?
                        #Se ricevo un CF == ANONIMO allora non aggiorno l'anagrafica
                        if  dati_anagrafici.identificativoUnivocoVersante['codiceIdentificativoUnivoco'].downcase == 'anonimo'
                            return anag_presente
                        end
                        #controllo se ci sono stati cambiamenti
                        params = {}
                        params['nome_cognome'] = dati_anagrafici.anagraficaVersante
                        params['codice_fiscale'] = dati_anagrafici.identificativoUnivocoVersante['codiceIdentificativoUnivoco']
                        params['via_residenza'] = dati_anagrafici.indirizzoVersante
                        params['civico_residenza'] = dati_anagrafici.civicoVersante
                        params['comune_residenza'] = dati_anagrafici.localitaVersante
                        params['cap_residenza'] = dati_anagrafici.capVersante
                        params['prov_residenza'] = dati_anagrafici.provinciaVersante
                        params['nazione_residenza'] = dati_anagrafici.nazioneVersante
                        params['email'] = dati_anagrafici.e_mailVersante
                        anag_presente = anag_presente.versiona(params)
                        return anag_presente
                    else #salvo la nuova anagrafica e la ritorno
                        nuova_anagrafica.save
                        return nuova_anagrafica
                    end
                end
                
            end
        end


        #se passo dei parametri diversi dai dati presenti nell'istanza ritorno una nuova anagrafica e archivio la precedente
        def versiona(parametri)
            modificato = false
            self.class.elements.each_pair{ |campo, elemento|
                next if elemento.definer_model != Portal::Anagrafica || elemento.model? || elemento.hidden?
                nuovo_valore = (parametri[campo.to_s] == "" ? nil : parametri[campo.to_s] )
                if !nuovo_valore.blank? && nuovo_valore != self[campo]
                    #se c'è un parametro diverso
                    modificato = true
                    break
                end
            }
            if modificato
                self.stato = 'archiviato'
                self.save
                parametri_senza_familiari = parametri.delete('anagrafica_familiare')
                nuova_anagrafica = self.class.new()
                self.class.elements.each_pair{ |campo, elemento|
                    next if elemento.definer_model != Portal::Anagrafica || elemento.hidden? == true || elemento.model?
                    nuovo_valore = (parametri[campo.to_s] == "" ? nil : parametri[campo.to_s] )
                    nuova_anagrafica.send((campo.to_s+"=").to_sym, nuovo_valore)    
                }
                nuova_anagrafica.nome_cognome = parametri['nome_cognome'] unless parametri['nome_cognome'].blank?
                nuova_anagrafica.nome_cognome ||= "#{parametri['nome']} #{parametri['cognome']}" unless parametri['nome'].blank? && parametri['cognome'].blank?
                nuova_anagrafica.nome_cognome ||= parametri['cognome'] unless parametri['cognome'].blank?
                nuova_anagrafica.tipo_anagrafica = 'G' if (parametri['codice_fiscale'] =~ /[0-9]{11}/) != nil #caso PIVA
                nuova_anagrafica.familiari << self.familiari
                nuova_anagrafica.save
                return nuova_anagrafica
            else
                return self
            end
        end









    end
end