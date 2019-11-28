# -*- encoding : utf-8 -*-
require 'spiderfw/init'

modulo = []
ARGV.each do|a|
    modulo << a
end

app_list = Portal::Notifiche::Applicazione.where{ codice == modulo } unless modulo.blank?
if !modulo.blank? && app_list.blank?
    puts "USAGE: ruby attiva_notifiche.rb modulo1 modulo2 ... modulo N"
    exit
end
app_list ||= Portal::Notifiche::Applicazione.all

utenti = Portal::Utente.all
utenti.each do |utente|
	app_list.each do |row|
		if Spider.apps_by_short_name.include?(row.codice) && !Spider.conf.get('notifiche.moduli_da_disattivare').include?(row.codice)
	   		notifica = Portal::Notifiche::Notifica.new 
            notifica.utente = utente
            notifica.applicazione = row.codice
            notifica.notifica_email = true
            notifica.notifica_sms = true
            notifica.notifica_push = false #TODO non ancora implementata
            utente.notifiche << notifica        
        end
	end
	utente.save
end
