# -*- encoding : utf-8 -*-

module Comunicazioni; module CanaleComunicazione

    class PushNotification < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError
        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli se presente nell'array di canali configurati
        canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
        if canali_pubblicazione.include?('push_notification')
            canale_comunicazione( 
                :id => "push_notification",
                :nome => "Push Notification",
                :immagine => "push_notification.png"
            )
        end


        #collegamento per db con active_record
        conn_params = Spider.conf.get('comunicazioni.db_push_connection')
        ActiveRecord::Base.establish_connection(
            :adapter  => conn_params['adapter'],
            :database => conn_params['database'],
            :username => conn_params['username'],
            :password => conn_params['password'],
            :host     => conn_params['host']
        )

        def self.canale_attivo(request_da_chiamante=nil)
           true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            hash_array_token = { 
                            :token_ios => [],
                            :token_android => []
            }
            if comunicazione.pubblica == true
                #controllo se ho utilizzato i gruppi
                if !comunicazione.gruppi.blank?
                    comunicazione.gruppi.each{ |gruppo|
                        gruppo.utenti.each{ |utente_gruppo|
                            #ricavo l'utente portale
                            utente = utente_gruppo.utente
                            if ( utente.stato == 'confermato' || utente.stato == 'attivo')
                                invio = false
                                #controllo che sia abilitato l'invio delle comunicazioni a livello di utente
                                if !session_user.blank? && !session_user["forza_invio"].blank?
                                    #forza_invio a true, invio
                                    invio = true
                                else
                                    #devo filtrare ulteriormente gli utenti per eliminare quelli che si sono disabilitati
                                    #se l'utente disabilitato non invio niente
                                    if !Portal::Utente.elements[:disabilita_comunicazioni].blank? && (utente.disabilita_comunicazioni == nil || utente.disabilita_comunicazioni == false)
                                        #comunicazioni abilitate 
                                        invio = true
                                    elsif !Portal::Utente.elements[:disabilita_comunicazioni].blank? && (utente.disabilita_comunicazioni == true) #non invio
                                        invio = false
                                    else
                                        #non ho Portal::Utente.elements[:disabilita_comunicazioni], portal non aggiornato -> invio
                                        invio = true
                                    end
                                end
                                #aggiungo ad array il token
                                self.aggiungi_utente_ad_array(utente.id, hash_array_token)
                            end
                         }

                    }
                else
                    #mando la comunicazione a tutti gli utenti abilitati
                    #filtro tutti gli utenti con stato 'confermato' o 'attivo' e mando la notifica push
                    utenti = Portal::Utente.where{ |utente| ((utente.stato == 'confermato') | (utente.stato == 'attivo'))  }

                    #controllo che sia abilitato l'invio delle comunicazioni a livello di utente
                    if session_user.blank? || session_user["forza_invio"].blank?
                        utenti.query.condition = utenti.query.condition.and{ |u| (u.disabilita_comunicazioni == nil) | (u.disabilita_comunicazioni == false) } unless Portal::Utente.elements[:disabilita_comunicazioni].blank?
                    end

                    utenti.each{ |utente|
                        self.aggiungi_utente_ad_array(utente.id, hash_array_token)
                    }
                end
                
            else
                #mando la notifica agli utenti associati alla comunicazione
                utenti = comunicazione.utenti
                utenti.each{ |utente_comunicazioni|
                    utente = utente_comunicazioni.utente
                    if utente.stato == 'confermato' || utente.stato == 'attivo'
                        self.aggiungi_utente_ad_array(utente.id, hash_array_token)
                    end
                }
            end
            
            begin

                #per la app devo passare anche l'url del servizio
                servizio = Portal::Servizio.load(:id => 'comunicazioni')
                unless servizio.blank?
                    url_servizio = servizio.url
                end
            
                #mando le notifiche ios
                unless hash_array_token[:token_ios].blank?
                    hash_array_token[:token_ios].each{ |token_ios|
                        notifica_ios = Rpush::Apns::Notification.new
                        notifica_ios.app = Rpush::Apns::App.find_by_name(Spider.conf.get('comunicazioni.app_name_ios'))
                        notifica_ios.device_token = token_ios # 64-character hex string
                        notifica_ios.alert = "test comunicazioni"
                        notifica_ios.data = { foo: :bar }
                        notifica_ios.save!
                    }
                    
                end
                #mando le notifiche android, non uso un array di id per avere un record per ogni id in tabella
                unless hash_array_token[:token_android].blank?
                    hash_array_token[:token_android].each{ |token_android|
                        notifica_android = Rpush::Gcm::Notification.new
                        notifica_android.app = Rpush::Gcm::App.find_by_name(Spider.conf.get('comunicazioni.app_name_android'))
                        notifica_android.registration_ids = token_android
                        notifica_android.data = { 'titolo' => 'Nuova Comunicazione - '+Spider.conf.get('comunicazioni.app_name_android'),
                                                  'id' => comunicazione.id,
                                                  'testo' => comunicazione.titolo+" "+comunicazione.testo_breve,
                                                  'applicazione' => 'comunicazioni',
                                                  'url' => url_servizio,
                                                  'accesso' => (comunicazione.pubblica == true ? 'pubblica' : 'privata')
                                                 }.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) } 
                        notifica_android.save!
                    }
                    
                end
            rescue Exception => exc
                Spider.logger.error "Errore: #{exc.message} \n\n #{exc.backtrace}"
                raise Exception.new('errore invio notifica')
            end
            comunicazione
        end

        #aggiungo agli array nell'hash passato i token degli utenti
        def self.aggiungi_utente_ad_array(id_utente, hash_array_token)
            
            token_utente = Portal::MobileDeviceRegistrati.load(:utente_portale => id_utente, :app_name => 'comunicazioni')
            unless token_utente.blank?
                case token_utente.device_type
                when 'iphone'
                    hash_array_token[:token_ios] << token_utente.registrationId
                when 'android'
                    hash_array_token[:token_android] << token_utente.registrationId
                else
                    #niente
                end
            end
            
        end

       
    end
end;end
