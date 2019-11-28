# -*- encoding : utf-8 -*-
module Portal

	class PortalScript

		def self.converti_province_sigla
			begin
				utenti = Portal::Utente.all
				require 'apps/portal/models/provincia'
				
				#carico le province in hash
				province_tabellate = Portal::Provincia.all
				unless province_tabellate.blank?
					hash_province = {}
					province_tabellate.each{|prov|
						hash_province[prov.nome.upcase] = prov.sigla 
					}
					
					utenti.each{|utente|
						if !utente.provincia_residenza.blank? && utente.provincia_residenza.length > 2 && !hash_province[utente.provincia_residenza.upcase].blank?
							utente.provincia_residenza = hash_province[utente.provincia_residenza.upcase]
						end
						if !utente.provincia_nascita.blank? && utente.provincia_nascita.length > 2 && !hash_province[utente.provincia_nascita.upcase].blank?
							utente.provincia_nascita = hash_province[utente.provincia_nascita.upcase]
						end
						utente.save
					}
					puts "Importazione effettuata, controllare province estere e inserire EE. ('SELECT * FROM spider.portal__utente WHERE length(portal__utente.provincia_residenza) > 2 or length(portal__utente.provincia_nascita) > 2 ')"
				else
					puts "Devi importare le province con la configurazione portal.province_tabellate a true"
				end


			rescue Exception => exc
				Spider.logger.error "Errore Conversione Province: #{exc.message}"
				return "error"
			end
		end
	end
end