# -*- encoding : utf-8 -*-
require 'uuidtools'
require 'apps/portal/models/modifica_contatto'
require 'apps/portal/models/gruppo'
module Portal
    
    class Utente < Spider::Model::Managed
        include Spider::Model::StateMachine
        include Spider::Auth::Authenticable
        include Spider::Messenger::MessengerHelper rescue NameError        
        
        label 'Utente Portale', 'Utenti Portale'
        
        element :nome, String, :check => { "Formato Nome non valido" => /^[-.A-Za-zÀÁÈÉËÍÌÓÒÚÙáàèéíìóòúù'\s]+$/ }
        element :cognome, String, :check => { "Formato Cognome non valido" => /^[-.A-Za-zÀÁÈÉËÍÌÓÒÚÙáàèéíìóòúù'\s]+$/ }
        state :stato, Spider::OrderedHash[
            'contatti', 'Attesa conferma contatti',
            'seconda_conferma', 'Richiesta conferma contatti',
            'attesa', 'Attesa attivazione',
            'attivo', 'Attivo',
            'confermato', 'Confermato',
            'disabilitato','Disabilitato'
        ], :conferma => true
        element :codice_fiscale, String, :check => { "Formato Codice Fiscale non valido" => /^EE_(\d){13}$|^A$|([a-zA-Z]{6}[\da-zA-Z]{2}[abcdehlmprstABCDEHLMPRST][\da-zA-Z]{2}[a-zA-Z][\da-zA-Z]{3}[a-zA-Z])/, "Codice Fiscale non corretto" => Proc.new{ |val| (val!="" && val!=nil) ? Portal::CodiceFiscale.check_codice_fiscale(val) : true } }, 
        :label => 'Codice fiscale', :conferma => true
        choice :sesso, {'M' => 'Maschio', 'F' => 'Femmina'}, :conferma => true
        element :comune_nascita, String, :label => 'Comune di nascita', :check => { "Formato Comune di nascita non valido" => /^[-.A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/ }, :conferma => true
        if Spider.conf.get('portal.province_tabellate') == true
            choice :provincia_nascita_tab, Portal::Provincia, :label => 'Provincia di nascita', :conferma => true
        else
            element :provincia_nascita, String, :label => 'Provincia di nascita (Sigla)', :check => { "Formato Provincia di nascita (Sigla) non valido" => /^[a-zA-Z]{2}$/ } , :conferma => true
        end

        element :stato_nascita, String, :label => 'Stato di nascita (Sigla)', :check => { "Formato Stato di nascita (Sigla) non valido" => /^[-.\/A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]{2}$/ }
        element :data_nascita, Date, :label => 'Data di nascita', :conferma => true, :check => Proc.new{ |data| data.blank? ? true : data < Date.today }
	    element :cap_residenza, String, :label => 'C.A.P. Comune di residenza', :check => { "Formato C.A.P. Comune di residenza non valido" => /^[0-9]+$/ } , :conferma => true
        element :comune_residenza, String, :label => 'Comune di residenza', :check => { "Formato Comune di residenza non valido" => /^[-.\/A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/ } , :conferma => true
        if Spider.conf.get('portal.province_tabellate') == true
            choice :provincia_residenza_tab, Portal::Provincia, :label => 'Provincia di residenza', :conferma => true
        else
            element :provincia_residenza, String, :label => 'Provincia di residenza (Sigla)', :check => { "Formato Provincia di residenza (Sigla) non valido" => /^[a-zA-Z]{2}$/ }, :conferma => true
        end
        element :indirizzo_residenza, String, :label => 'Indirizzo di residenza', :conferma => true
        #separazione indirizzo in campi multipli 12/2014
        element :civico_residenza, String, :label => 'Civico'#, :conferma => true
        element :stato_residenza, String, :label => 'Stato di Residenza (Sigla)', :check => { "Formato Stato di Residenza (Sigla) non valido" => /^[-.\/A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]{2}$/ }
        choice :tipo_documento, {'CI' => "Carta d'Identità", 'Patente' => 'Patente', 'Passaporto' => 'Passaporto'}, :label => 'Tipo documento'#, :conferma => true
        element :numero_documento, String, :label => 'Numero documento'#, :conferma => true
        element :data_documento, Date, :label => 'Data documento', :check => Proc.new{ |data| data.blank? ? true : data <= Date.today } #, :conferma => true
        element :documento_rilasciato, String, :label => 'Documento rilasciato da' #, :conferma => true
        element :email, String, :label => 'E-mail', :check => { "Formato E-mail non valido" => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/, "E-mail obbligatoria" => Proc.new{ |val| (val != "" && val != nil) ? true : false } }, :conferma => true
        #vecchio controllo mail, cambiato il 13/09/2017 dopo problema federa :check => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/
        element :email_confermata, Spider::Bool
        element :pec, String, :label => 'Pec', :check => { "Formato Pec non valido" => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/ } 
        element :telefono, String, :check => { "Formato Telefono non valido" => /^[0-9]+$/ }
        element :fax, String, :check => { "Formato Fax non valido" => /^[0-9]+$/ }
        element :cellulare, String, :check => { "Formato Cellulare non valido" => /^[0-9]+$/ }
        element :cellulare_confermato, Spider::Bool
        many :attributi_aggiuntivi, Portal::AttributoUtente do
            element :valore, String
        end
        many :modifiche_contatti, Portal::ModificaContatto, :add_reverse => :utente, :delete_cascade => true
        multiple_choice :gruppi, Portal::Gruppo, :add_multiple_reverse => {:name => :utenti, :association => :multiple_choice, :order => :cognome}
        element :cancellato, Spider::Bool, :hidden => true
        element :data_cambio_password, Date, :hidden => true
        element :strong_auth_key, String, :hidden => true
        element :note, Text

        many :notifiche, Portal::Notifiche::Notifica, :add_reverse => :utente, :delete_cascade => true

        element :accettazione_clausole, Spider::Bool, :label => "Accetto le clausole"
        #campi per professionisti
        #element :albo, String, :hidden => true
        choice :albo, { 'ARC' => "Architetti",
                        'PER' => "Periti Industriali",
                        'ING' => "Ingegneri ",
                        'GEO' => "Geometri",
                        'PEA' => "Periti agrari"}, :hidden => true
        element :n_albo, String, :label => "N° Iscrizione Albo", :hidden => true
        element :p_iva, String, :label => "P. IVA", :hidden => true
        element :p_albo, String, :label => "Prov Albo (Sigla)", :check => /^[a-zA-Z]{2}$/
        #campo per disabilitare le comunicazioni
        element :disabilita_comunicazioni, Spider::Bool, :default => false, :hidden => true
        #campo per evitare di mandare una variazione a civilia quando si confermano i dati di un utente
        #se data_conferma ha stesso giorno e ora di obj_modified non faccio scaricare la variazione
        element :data_conferma, DateTime, :label => "Conferma Civilia", :hidden => true

        element :accettazione_gdpr, Spider::Bool, :default => false
        element :data_ora_accettazione_gdpr, DateTime, :label => "Accettato GDPR alle", :hidden => true

        element :richiesta_cancellazione_gdpr, Spider::Bool, :default => false
        element :data_ora_cancellazione_gdpr, DateTime, :label => "Richiesta Cancellazione GDPR alle", :hidden => true        

        #Se ho l'app utenze_utility aggiungo questi campi. Con questo controllo uso var di classe, funziona anche con setup da db
        if Spider.configuration['apps'].include?('utenze_utility')
            element :codice_anagrafico, String, :length => 20
            element :codice_ente, String, :length => 6
            element :codice_cliente, String, :length => 32
            element :denominazione, String, :length => 80
            element :data_attivazione_utenza, Date
            element :data_chiusura_utenza, Date
            element :intestatario_recapito, String, :length => 80
            element :indirizzo_recapito_fattura, String, :length => 80
            element :localita_recapito, String, :label => "Località Recapito", :length => 40
        end

        multiple_choice :servizi_privati, Portal::Servizio do
            include Spider::Model::StateMachine
            state :stato, {
                'richiesto' => 'Richiesta attivazione',
                'configurazione' => 'Necessaria configurazione',
                'attivo' => 'Attivo',
                'disattivato' => "Disattivato dall'utente"
            }

            state_event :stato do |ev|
                ev.transition :from => ['richiesto', 'attivo', 'disattivato'], :to => 'configurazione' 
                ev.action do |obj, from, to|
                    obj.utente.email_necessaria_configurazione(obj)
                end
            end



            state_event :stato do |ev|
                ev.transition :from => ['richiesto', 'configurazione'], :to => 'attivo' 
                ev.action do |obj, from, to|
                    obj.utente.email_attivazione_servizio(obj)
                end
            end
            
            def attivo?
                return false unless self.utente.attivo? 
                return false if !self.accesso.blank? && self.accesso.id == 'confermati' && !self.utente.confermato?
                return self.stato.id == 'attivo' 
            end
            
            def configurazione?
                return false unless self.utente.attivo?
                return false if self.accesso.id == 'confermati' && !self.utente.confermato?
                return self.stato.id == 'configurazione'
            end
        end

        
        include Spider::Auth::RBACProvider
        rbac_provider_for :utente_portale

        def before_save
            if self.id.blank? && self.servizi_privati.blank? && s = Spider.conf.get('portal.servizi_privati_default')
                s.each do |id_servizio|
                    self.servizi_privati << {
                        :servizio => id_servizio,
                        :stato => :attivo
                    }
                end
            end
            super
        end
        
        def codice_fiscale=(val)
            return super if val.blank?
            super(val.gsub(/\s+/, '').upcase)
        end

        #commentato perchè è stato messo il :check per sole cifre
        # def cellulare=(val)
        #     return super if val.blank?
        #     super(val.gsub(/[^\d]/, ''))
        # end
        
        def to_s
            "#{self.nome} #{self.cognome}"
        end
        
        def attivo?
            self.stato == 'attivo' || self.stato == 'confermato'
        end
        
        def confermato?
            self.stato == 'confermato'
        end
        
        def disabilitato?
            self.stato == 'disabilitato'
        end

        def servizio_privato?(id)
            self.servizi_privati.each do |s|
                return s if s.servizio.id == id && s.attivo?
            end
            return false
        end
        
        def servizio_privato(id)
            self.servizi_privati.each{ |s| return s if s.servizio.id == id.to_s }
            return nil
        end
        
        def utente_autenticazione_portale
            elemento = self.class.elements_array.select{ |el| el.attributes[:autenticazione_portale] && self.get(el) }.first
            self.get(elemento)
        end
        
        #passaggio da attesa conferma contatti o da seconda mail di conferma a attivo o confermato
        state_event :stato do |ev|
            ev.transition :from => ['attesa'], :to => ['attivo', 'confermato']
            ev.action do |obj, from, to|
                Portal::PortalController.email_attivazione_utente(obj)
            end
        end
        
        #mando la seconda mail di conferma di registazione
        state_event :stato do |ev|
            ev.transition :from => ['contatti'], :to => ['seconda_conferma']
            ev.action do |obj, from, to|
                #cancello le modifiche pendenti precedenti
                precedenti_modifiche = ModificaContatto.where(:utente => obj)
                precedenti_modifiche.each{ |modifica_precedente|
                    modifica_precedente.delete
                }
                if Spider.conf.get('portal.conferma_email')
                    modifica = ModificaContatto.create(:utente => obj, :tipo => 'email', :dopo => obj.email)
                    obj.invia_controllo_email(modifica, :registrazione => true)
                end
                if Spider.conf.get('portal.conferma_cellulare') && !obj.cellulare.blank? && !Spider::Messenger.backends[:sms].blank?
                    modifica = ModificaContatto.create(:utente => obj, :tipo => 'cellulare', :dopo => obj.cellulare)
                    obj.invia_sms_controllo_cellulare(modifica)
                end
            end
        end

        state_event :stato do |ev|
            ev.transition :from => 'attivo', :to => 'confermato'
            ev.action do |obj, from, to|
                obj.email_conferma_utente
            end
        end
        
        def link_amministrazione
            utente_auth = self.utente_autenticazione_portale
            link = Spider::Components::Switcher.label_to_link(utente_auth.class.label_plural)
            "#{Portal.http_s_url}/amministrazione/#{link}/#{utente_auth.id}"
        end
        
        def invia_email_cancellazione
            scene = Spider::Scene.new
            scene.data_registrazione = self.obj_created.lformat(:short)
            scene.nome = self.nome; scene.cognome = self.cognome
            scene.nome_portale = Spider.conf.get('portal.nome')
            headers = {'Subject' => "Registrazione al portale #{Spider.conf.get('portal.nome')} annullata"}
            send_email('cancellazione_no_conferma_email', scene, Spider.conf.get('portal.email_from'), self.email, headers)
        end

        def registrato!
            contatti = false
            utente = self
            utente.stato = 'contatti'
            if Spider.conf.get('portal.conferma_email')
                modifica = ModificaContatto.create(:utente => self, :tipo => 'email', :dopo => utente.email)
                utente.invia_controllo_email(modifica, :registrazione => true)
                contatti = true
            end
            if Spider.conf.get('portal.conferma_cellulare') && !utente.cellulare.blank? && !Spider::Messenger.backends[:sms].blank?
                modifica = ModificaContatto.create(:utente => self, :tipo => 'cellulare', :dopo => utente.cellulare)
                utente.invia_sms_controllo_cellulare(modifica)
                contatti = true
            end
            if contatti
                utente.stato = 'contatti'
            elsif Spider.conf.get('portal.attivazione_utenti_automatica')
                utente.stato = 'attesa'
            else
                utente.stato = 'attivo'
            end
        end
        
        def invia_controllo_email(modifica=nil, opzioni={})
            opzioni = ({
                :registrazione => false,
                :ripetuta => false
            }).merge(opzioni)
            nuova = ""
            unless modifica.blank?
                modifica.conferme_mandate ||= 0
                modifica.conferme_mandate += 1
                chiave = Spider::SecureRandom.hex(20)
                modifica.chiave_conferma = chiave
                modifica.save
                nuova = modifica.dopo
            end
            scene = Spider::Scene.new
            scene.nome = self.nome; scene.cognome = self.cognome
            scene.nome_portale = Spider.conf.get('portal.nome')
            scene.ripetuta = opzioni[:ripetuta]
            scene.link_conferma = "#{Portal.http_s_url('conferma_email')}?controllo=#{chiave}".gsub(/\n/, "")
            if opzioni[:registrazione]
                headers = {'Subject' => "Registrazione al portale #{Spider.conf.get('portal.nome')} - Conferma e-mail"}
                send_email('conferma_email_registrazione', scene, Spider.conf.get('portal.email_from'), self.email, headers)
            else
                headers = {'Subject' => "#{Spider.conf.get('portal.nome')} - Conferma nuova e-mail"}
                send_email('conferma_cambio_email', scene, Spider.conf.get('portal.email_from'), nuova, headers)
            end
        end
        
        def invia_sms_controllo_cellulare(modifica)
            modifica.conferme_mandate ||= 0
            modifica.conferme_mandate += 1
            chiave = Spider::SecureRandom.hex(4)
            modifica.chiave_conferma = chiave
            modifica.save
            send_sms(modifica.dopo, "#{Spider.conf.get('portal.nome')} - Chiave di controllo: #{chiave}")
        end
        
        def invia_sms_autenticazione_forte
            chiave = Spider::SecureRandom.hex(2)
            self.strong_auth_key = chiave
            self.save
            sms_inviato = send_sms(self.cellulare, "#{Spider.conf.get('portal.nome')} - Chiave di autenticazione: #{chiave}")
            !sms_inviato.blank?
        end

        # FIXME: dovrebbe essere fatto da worker entro un giorno, ma worker.in non funziona; spostare in uno
        # script di worker
        def self.controllo_registrazione(id_utente)
#             utente = Portal::Utente.new(id_utente)
#             return unless utente
#             if utente.stato && utente.stato.id == 'contatti'
#                 utente.invia_controllo_email(true)
#                 Spider::Worker.in('2d', "
# u = Portal.utente.new(#{id_utente})
# if u.chiave_conferma && u.chiave_conferma == '#{utente.chiave_conferma}'
#     u.invia_email_cancellazione
#     u.delete
# end
#                 ")
#             end
            
        end
        
        def email_conferma_utente
            scene = Spider::Scene.new
            scene.utente = self
            headers = {'Subject' => "Conferma account #{Spider.conf.get('portal.nome')}"}
            send_email('conferma_account', scene, Spider.conf.get('portal.email_from'), self.email, headers)
        end

        def email_attivazione_servizio(servizio_utente)
            scene = Spider::Scene.new
            scene.utente = self
            scene.servizio = servizio_utente.servizio
            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Attivazione servizio portale"}
            send_email('attivazione_servizio', scene, Spider.conf.get('portal.email_from'), self.email, headers)
        end
        
        def email_necessaria_configurazione(servizio_utente)
            scene = Spider::Scene.new
            scene.link_configurazione = ""
            url_configurazione = Portal.servizi[servizio_utente.servizio.id].servizio_portale[:url_configurazione]
            unless url_configurazione.blank?
                scene.link_configurazione = File.join(Portal.http_s_url, '/servizi', url_configurazione)
            end
            scene.utente = self
            scene.servizio = servizio_utente.servizio
            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Configurazione servizio portale necessaria"}
            send_email('necessaria_configurazione', scene, Spider.conf.get('portal.email_from'), self.email, headers)
        end

        def modifica_contatto(tipo, nuovo, forza_controllo=false)
            raise "Tipo non conosciuto: #{tipo}" unless ['email', 'cellulare'].include?(tipo)
            #se non richiesta la conferma della mail o del cel modifico subito il campo in db
            unless forza_controllo
                unless (self.attivo? && Spider.conf.get("portal.conferma_cambio_#{tipo}")) || \
                    (!self.attivo? && Spider.conf.get("portal.conferma_#{tipo}"))
                    self.set(tipo, nuovo)
                    return #non ritorno una modifica_contatto
                end
            end
            #carico la modifica da fare per il contatto
            modifica = modifica_contatto_pendente(tipo)
            if modifica
                if modifica.dopo == nuovo
                    return
                else
                    modifica.delete
                    modifica = nil
                end
            end
            #se la mail/cell sono uguali a quello nuovo non fa niente
            if self.get(tipo) == nuovo
                return
            end
            prima = self.attivo? ? self.get(tipo) : nil
            nuovo = nuovo.gsub(/\D/, '') if tipo == 'cellulare'
            modifica ||= ModificaContatto.create(
                :utente => self,
                :tipo => tipo,
                :prima => prima,
                :dopo => nuovo
            )
            if tipo == 'email'
                if Spider.conf.get('portal.conferma_cambio_email')
                    self.email_confermata = false
                    #cambio lo stato per metterlo in attesa conf contatti
                    self.stato = 'attivo'
                    self.email_confermata = false
                    invia_controllo_email(modifica)
                else
                    self.set(tipo, nuovo) unless self.attivo?
                end
            elsif tipo == 'cellulare'
                if Spider.conf.get('portal.conferma_cambio_cellulare')
                    self.cellulare_confermato = false
                    self.stato = 'attivo'
                    self.cellulare_confermato = false
                    invia_sms_controllo_cellulare(modifica)
                else
                    self.set(tipo, nuovo) unless self.attivo?
                end
            end
            return modifica
        end
        
        def modifica_email(email)
            modifica_contatto('email', email)
        end
        
        def modifica_cellulare(cell)
            if Spider::Messenger.backends[:sms]
                modifica_contatto('cellulare', cell)
            else
                self.cellulare = (cell.nil? ? cell : cell.gsub(" ",""))
            end
        end

        def conferma_contatto(tipo, modifica=nil)
            modifica ||= modifica_contatto_pendente(tipo)
            #se sono in contatti o in seconda conferma e sto confermando per la prima volta un contatto
            if (self.stato == 'contatti' || self.stato == 'seconda_conferma') && modifica.prima.blank?
                attivazione_automatica = Spider.conf.get('portal.attivazione_utenti_automatica')
                self.stato = attivazione_automatica ? 'attivo' : 'attesa'
            end
            #effettuo la modifica al campo
            if self.get(tipo.to_sym) != modifica.dopo
                self.set(tipo.to_sym, modifica.dopo)
            end
            if modifica.prima
                modifica.chiave_conferma = nil
                modifica.save
            else # Non serve tenere modifica primo contatto
                modifica.delete
            end
            if tipo == 'cellulare'
                self.cellulare_confermato = true
            elsif tipo == 'email'
                self.email_confermata = true
            end
            self.save
        end
        
        def self.per_attributo(id, valore)
            self.load{ |u| (u.attributi_aggiuntivi.attributo_utente == id ) & (u.attributi_aggiuntivi.valore == valore) }
        end
        
        def attributo(id, val=nil)
            if val
                cancella_attributo(id)
                Portal::Utente::AttributiAggiuntivi.create(:utente => self, :attributo_utente => id, :valore => val)
            else
                a = self.attributi_aggiuntivi
                a.each do |at|
                    return at.valore if at.attributo_utente.id == id
                end
                return nil
            end
        end
        
        def cancella_attributo(id)
            a = self.attributi_aggiuntivi
            a.each do |at|
                at.delete if at.attributo_utente.id == id
            end
        end
        
        def modifica_contatto_pendente(tipo)
            @modifica_contatto_pendente ||= {}
            return @modifica_contatto_pendente[tipo] if @modifica_contatto_pendente[tipo]
            md = ModificaContatto.where{ |m| 
                (m.utente == self) & (m.tipo == tipo) & (m.chiave_conferma .not nil)
            }.order_by(:obj_created, :desc)
            md.limit(1)
            @modifica_contatto_pendente[tipo] = md[0]
        end

        def modifiche_contatti_pendenti
            ModificaContatto.where{ |m|
                (m.utente == self) & (m.chiave_conferma .not nil)
            }
        end
        
        def attiva_servizio_configurato(id_servizio)
            self.servizi_privati.each do |servizio|
                if servizio.servizio.id == id_servizio && servizio.stato.id == "configurazione"
                    servizio.stato = "attivo"
                    servizio.save
                end                
            end
        end
        
    end
    
    
end
