# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Email < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError

        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli
        canale_comunicazione( 
            :id => "email",
            :nome => "E-mail",
            :immagine => "email.png"
        )

        def self.canale_attivo(request_da_chiamante)
            true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            #creo i vari campi della mail
            oggetto = comunicazione.titolo
            testo = comunicazione.testo
            testo_breve = comunicazione.testo_breve

            attachments = nil
            #creo la scene per passare le informazioni al template della mail
            scene = Spider::Scene.new
            #cancello la parte html che non serve
            #testo_estratto = Nokogiri::HTML(testo).css("body").inner_html
            
            if !comunicazione.immagine.blank?
                link_immagine = Comunicazioni::ComunicazioniController.http_s_url('download_immagine?id_com='+comunicazione.id.to_s+'&t_img=mini')
                scene.link_immagine = link_immagine
            end

            #aggiungo il dominio agli url delle immagini
            doc = Hpricot(testo)
            doc.search('a').each do |a| 
                a['href'] = "#{Spider.site.to_s}#{a['href']}" unless a['href'] =~ /http|mailto/
            end
            doc.search('img').each do |img|
                img['src'] = "#{Spider.site.to_s}#{img['src']}" unless img['src'] =~ /http/
            end
            doc.search('*[@background]') do |tag|
                tag['background'] = "#{Spider.site}#{tag['background']}" unless tag['background'] =~ /http/
            end
            testo = doc.to_s
            scene.testo_comunicazione = testo
            
            #aggiungo il dominio ai link del testo breve se ci sono
            doc_breve = Hpricot(testo_breve)
            doc_breve.search('a').each do |a| 
                a['href'] = "#{Spider.site.to_s}#{a['href']}" unless a['href'] =~ /http|mailto/
            end
            doc_breve.search('img').each do |img|
                img['src'] = "#{Spider.site.to_s}#{img['src']}" unless img['src'] =~ /http/
            end
            doc_breve.search('*[@background]') do |tag|
                tag['background'] = "#{Spider.site}#{tag['background']}" unless tag['background'] =~ /http/
            end
            testo_breve = doc_breve.to_s
            scene.testo_breve = testo_breve

            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')}:servizio comunicazioni - #{oggetto}"}
            #controllo se la comunicazione è pubblica o privata.
            if comunicazione.pubblica == true
                #controllo se ho utilizzato i gruppi
                if !comunicazione.gruppi.blank?
                    comunicazione.gruppi.each{ |gruppo|
                        gruppo.utenti.each{ |utente_gruppo|
                            #ricavo l'utente portale
                            utente = utente_gruppo.utente
                            if ( !utente.email.blank? && ( utente.stato == 'confermato' || utente.stato == 'attivo') )
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
                                #mando la mail se ho il forza invio a true
                                Spider::Messenger::MessengerHelper.send_email(Comunicazioni, 'comunicazione_pubblica', scene, Spider.conf.get('portal.email_from'), utente.email , headers ,attachments) if invio
                            end
                         }

                    }
                else
                    #mando la comunicazione a tutti
                    #filtro tutti gli utenti con stato 'confermato' o 'attivo' e mando la mail
                    utenti = Portal::Utente.where{ |utente| (( utente.email .not nil) & (utente.email .not "")) & ((utente.stato == 'confermato') | (utente.stato == 'attivo'))  }

                    #controllo che sia abilitato l'invio delle comunicazioni a livello di utente
                    if session_user.blank? || session_user["forza_invio"].blank?
                        utenti.query.condition = utenti.query.condition.and{ |u| (u.disabilita_comunicazioni == nil) | (u.disabilita_comunicazioni == false) } unless Portal::Utente.elements[:disabilita_comunicazioni].blank?
                    end

                    utenti.each{ |utente|
                        Spider::Messenger::MessengerHelper.send_email(Comunicazioni, 'comunicazione_pubblica', scene, Spider.conf.get('portal.email_from'), utente.email , headers ,attachments)
                    }
                end
                
            else
                #mando la mail agli utenti associati alla comunicazione
                utenti = comunicazione.utenti
                utenti.each{ |utente|
                    Spider::Messenger::MessengerHelper.send_email(Comunicazioni, 'comunicazione_privata', scene, Spider.conf.get('portal.email_from'), utente.email , headers ,attachments) 
                }
            end
            comunicazione
        end


    end
end;end
