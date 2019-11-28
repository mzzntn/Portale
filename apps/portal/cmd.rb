# -*- encoding : utf-8 -*-
require 'cmdparse'

module Portal

    class Cmd < ::CmdParse::Command

        def initialize
            super('portal', true)
            
            scarica = CmdParse::Command.new('scarica_utenti', false )
            scarica.short_desc = "Scarica utenti"
            scarica.set_execution_block do |args|
                url = Spider.conf.get('portal.scarica_utenti_da')
                if url.blank?
                    puts "Devi prima impostare il parametro di configurazione portal.scarica_utenti_da"
                end
                file_data = File.join(Spider.paths[:var], 'portal_scaricamento_utenti')
                data = nil
                if File.exist?(file_data)
                    data = DateTime.parse(IO.read(file_data))
                end
                Portal.scarica_utenti(url, data)
                File.open(file_data, w) do |f|
                    f << data.to_s
                end
            end
            self.add_command(scarica)



            aggiungi_servizio = CmdParse::Command.new('aggiungi_servizio', false )
            aggiungi_servizio.short_desc = "Aggiunge un servizio privato ad un utente che ha un certo stato/i"
            aggiungi_servizio.options = CmdParse::OptionParserWrapper.new do |opt|
                opt.on("--servizio [STRING]", "Id del servizio da aggiungere agli utenti"){ |srv|
                        @servizio = srv.strip unless srv.blank? 
                }
                opt.on("--stati [STRING]", "Stati dell'utente a cui può essere aggiunto il servizio"){ |stati|
                        @stati = stati.split(",") unless stati.blank?
                }
            end
            aggiungi_servizio.set_execution_block do |args|
                unless @servizio.blank? || @stati.blank? 
                    Portal::PortalScript.aggiungi_servizio(@servizio, @stati)
                else
                    puts "Devi inserire l'id del servizio e gli stati utente"
                end
                
            end
            self.add_command(aggiungi_servizio)


            converti_province_sigla = CmdParse::Command.new('converti_province_sigla', false )
            converti_province_sigla.short_desc = "Converte le province nella tabella portal utente da nome completo a sigla"
            converti_province_sigla.set_execution_block do |args|
                Portal::PortalScript.converti_province_sigla
            end
            self.add_command(converti_province_sigla)



         
        end

    end

 


end
