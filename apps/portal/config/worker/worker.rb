# -*- encoding : utf-8 -*-
include Spider::Messenger::MessengerHelper

def esegui_worker(ora, modulo='')

    Spider::Worker.cron(ora) do
        app_list = Portal::Notifiche::Applicazione.where{ codice == modulo } unless modulo.blank?
    	app_list ||= Portal::Notifiche::Applicazione.all
        active_apps = []

        #Verifico quali applicazioni sono attive della lista delle applicazioni con notifiche
        app_list.each do |row|
            stati = []
            if Spider.apps_by_short_name.include?(row.codice) && !Spider.conf.get('notifiche.moduli_da_disattivare').include?(row.codice)
                case row.codice
                when 'pagamenti'
                    Portal::Notifiche::Pagamenti.esegui_invio(row.codice)
                else                    
                    Spider.logger.debug("Eseguo sottomodulo notifiche #{row.codice}")
                    utenti = Portal::Utente.where{ |n| (n.stato .not 'disabilitato') & (n.notifiche.applicazione == row.codice) & ((n.notifiche.notifica_email == 1) | (n.notifiche.notifica_sms == 1)) }
                    utenti.each do |utente|
                        utente.notifiche.each do |notifica|
                            stati += notifica.invia(notifica) if (notifica.notifica_email || notifica.notifica_sms) && !notifica.applicazione.blank? && notifica.applicazione.codice == row.codice
                        end
                    end
                    if row.codice == 'muse' && stati #Pulisco le notifiche più vecchie
                        ::MuSe::Stato.mapper.bulk_update({:livello_notifica => nil}, Spider::Model::Condition.new{ |s| (s.id .not stati) })
                    end
                end
            end
        end
    end
end

starter = Spider.conf.get('notifiche.cron')
if starter.is_a?(Hash)
    starter.each do |modulo, ora|
        esegui_worker(ora, modulo)
    end
elsif starter
    esegui_worker(starter)
end
