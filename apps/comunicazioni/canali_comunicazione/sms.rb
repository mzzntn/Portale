# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Sms < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare gli sms
        include Spider::Messenger::MessengerHelper rescue NameError

        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli
        canale_comunicazione( 
            :id => "sms",
            :nome => "Sms",
            :immagine => "sms.png"
        )

        def self.canale_attivo(request_da_chiamante)
            true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            testo = comunicazione.testo_breve
            #controllo se la comunicazione è pubblica o privata.

            if comunicazione.pubblica == true
                #controllo se ho utilizzato i gruppi
                if !comunicazione.gruppi.blank?
                    comunicazione.gruppi.each{ |gruppo|
                        gruppo.utenti.each{ |utente_gruppo|
                            #ricavo l'utente portale
                            utente = utente_gruppo.utente
                            cellulare = utente.cellulare
                            if !cellulare.blank? && ( utente.stato == 'confermato' || utente.stato == 'attivo' )
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
                                #mando l'sms se ho il forza invio a true
                                if invio
                                    cellulare = "+39"+cellulare if (!cellulare.include?("+") && (cellulare.length == 10 || cellulare.length == 9) )
                                    sms = Spider::Messenger.sms(cellulare, testo) if (cellulare.length == 13 || cellulare.length == 12)
                                    if sms.blank?
                                        Spider.logger.error "Numero #{cellulare} non valido dell'utente #{utente.id.to_s}"
                                    end
                                end
                            end
                         }

                    }
                else
                    #filtro tutti gli utenti con stato 'confermato' o 'attivo' e mando l'sms
                    utenti = Portal::Utente.where{ |utente| (( utente.cellulare .not nil) & (utente.cellulare .not "")) & ((utente.stato == 'confermato') | (utente.stato == 'attivo'))  }
                    
                    #controllo che sia abilitato l'invio delle comunicazioni a livello di utente
                    if session_user.blank? || session_user["forza_invio"].blank?
                        utenti.query.condition = utenti.query.condition.and{ |u| (u.disabilita_comunicazioni == nil) | (u.disabilita_comunicazioni == false) } unless Portal::Utente.elements[:disabilita_comunicazioni].blank?
                    end

                    utenti.each{ |utente| 
                        cellulare = utente.cellulare
                        unless cellulare.blank?
                            cellulare = "+39"+cellulare if (!cellulare.include?("+") && (cellulare.length == 10 || cellulare.length == 9) )
                            sms = Spider::Messenger.sms(cellulare, testo) if (cellulare.length == 13 || cellulare.length == 12)
                            if sms.blank?
                                Spider.logger.error "Numero #{cellulare} non valido dell'utente #{utente.id.to_s}"
                            end
                        end
                    }
                end
            else
                #mando l'sms agli utenti associati alla comunicazione
                utenti = comunicazione.utenti
                utenti.each{ |utente|
                    cellulare = utente.cellulare
                    unless cellulare.blank?
                        cellulare = "+39"+cellulare if (!cellulare.include?("+") && (cellulare.length == 10 || cellulare.length == 9) ) 
                        sms = Spider::Messenger.sms(cellulare, testo) if (cellulare.length == 13 || cellulare.length == 12)
                        if sms.blank?
                            Spider.logger.error "Numero #{cellulare} non valido dell'utente #{utente.id.to_s}"
                        end
                    end
                }
            end
            comunicazione
        end

    end
end;end
