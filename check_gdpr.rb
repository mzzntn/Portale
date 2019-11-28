# -*- encoding : utf-8 -*-
#!/bin/env /usr/local/rvm/rubies/ruby-1.8.7-p374/bin/ruby 
#/opt/ruby-enterprise-1.8.7-2011.03/bin ruby 
require 'rubygems'
require 'spiderfw/init'
#require 'debugger'

#require "mysql"
Spider.conf.set "log.level", :ERROR

#variabili
ore = "9"
destinatario = Spider.conf.get('portal.email_amministratore')
#destinatario = "andrea@soluzionipa.it"
mittente = Spider.conf.get('portal.email_from')
#mittente = "alert@soluzionipa.it"
gdpr_attivo = Spider.conf.get('portal.abilita_gdpr_utente')

server = `hostname`.chomp.split('.')[0]

include Spider::Messenger::MessengerHelper rescue NameError

begin
    if gdpr_attivo
        con = Spider::Model::BaseModel.get_storage
        dati = YAML.load_file('config/config.yml')
        database = dati['storages']['default']['url'].split('/').last
        rs = con.connection.query("SELECT nome, cognome, email, data_ora_cancellazione_gdpr, id FROM portal__utente WHERE data_ora_cancellazione_gdpr <= DATE_ADD(NOW(), INTERVAL -#{ore} HOUR) and richiesta_cancellazione_gdpr = '1'")

    
        rs.each do |row|
        	nome = row[0]
            cognome = row[1]
            email = row[2]
            data_ora_cancellazione_gdpr = row[3]
            id_utente = row[4]

            testo = "Cancellazione dati personali eseguita per l'utente (#{id_utente}) #{nome} #{cognome} email: #{email} come da sua richiesta del #{data_ora_cancellazione_gdpr}"

            e1 = con.connection.query("delete from portal__utentelogin where utente_portale_id = #{id_utente}")
            e2 = con.connection.query("delete from portal__utente__attributiaggiuntivi where utente_id = #{id_utente}")
            e3 = con.connection.query("delete from portal__utente__gruppi where utente_id = #{id_utente}")
            e4 = con.connection.query("delete from portal__utente__serviziprivati where utente_id = #{id_utente}")
            e5 = con.connection.query("delete from portal__utente__utenteportalepermissionsjunction where utente_id = #{id_utente}")
            e0 = con.connection.query("delete from portal__utente where id = #{id_utente}")

            mail = Mail.new(testo)
            mail[:to] = destinatario
            mail[:from] = mittente
            mail[:oggetto] = "#{ Spider.conf.get('ente.nome') } - Cancellazione dati personali" 

            mail_headers = {'Subject' => mail[:oggetto]} 
            #mando la mail
            mailmandata = Spider::Messenger.email(mail[:from], mail[:to], mail_headers, testo)

        end
    end

end