# -*- encoding : utf-8 -*-
require 'spiderfw/init'

begin

        mails = Spider::Messenger::Email.where{ |m_e| (m_e.status == [:backend,:failed])}
        puts "Totale righe tabella: #{mails.length}"
        n_righe_pulite = 0
        mails.each{ |mail|
                pulita = false
                stringa_body = mail.body
                while stringa_body.include?("Content-Disposition: attachment") do
                        pulita = true
                        #stringa_body = stringa_body.sub(/Content-Transfer-Encoding: base64.*?(filename=.*?)[\r\n].*?==_mimepart_/m,"\\1\r\n")
                        stringa_body = stringa_body.sub(/Content-Transfer-Encoding: (?!quoted-printable).*?(filename=.*?\n)(.*?==_mimepart_|.*)/mi,"\\1\r\n")
                        mail.body = stringa_body
                end
                n_righe_pulite += 1 if pulita
                mail.status = 'archiviata'
                mail.save
        }
        puts "\n\n Totale righe PULITE: #{n_righe_pulite}"

    con = Spider::Model::BaseModel.get_storage
    dati = YAML.load_file('config/config.yml')
    database = dati['storages']['default']['url'].split('/').last
    d1 = con.connection.query("delete from spider__messenger__message where exists (select message_id from spider__messenger__email where spider__messenger__message.id = spider__messenger__email.message_id and spider__messenger__email.to_field='down@soluzionipa.it')")
    d2 = con.connection.query("delete from spider__messenger__email where to_field='down@soluzionipa.it'")
    e = con.connection.query("OPTIMIZE TABLE spider__messenger__email")
    puts database
rescue => exc
    messaggio = "Errore Pulisci Mail ( #{exc.message} )"
	messaggio_log = messaggio
	exc.backtrace.each{ |riga_errore|
			messaggio_log += "\n\r#{riga_errore}"
	}
	puts messaggio_log
end