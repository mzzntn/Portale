# -*- encoding : utf-8 -*-
require 'spiderfw/init'



def calcola_punteggio_servizi(cat_modulo,hash_servizi)
    totale_punteggio_servizi = BigDecimal.new(0.00,5)  
	errore_date = false
	hash_servizi.each_value{ |servizio|
	    valore_servizio = Moduli.calcola_punteggio_esperienza_lavorativa(cat_modulo,servizio['cat_giuridica'],servizio['giorni_di_servizio'],servizio['tipo_amministrazione'],servizio['rid_lavorativa'])
	    totale_punteggio_servizi += valore_servizio
	    if servizio['inizio_servizio'].split("/").last.length < 4
	    	Spider.logger.error "\n data errata inizio servizio id inc #{servizio['id_inc']}"
			errore_date = true
	    end
	    if servizio['fine_servizio'].split("/").last.length < 4
	    	Spider.logger.error "\n data errata fine servizio id inc #{servizio['id_inc']}"
	    	errore_date = true
	    end
	    data_inizio = Date::strptime(servizio['inizio_servizio'], "%d/%m/%Y")
	    data_fine = Date::strptime(servizio['fine_servizio'], "%d/%m/%Y")
	    if data_fine < data_inizio
			Spider.logger.error "\n data fine prima di data inizio: inizio #{servizio['inizio_servizio']}, fine #{servizio['fine_servizio']}"
	    	errore_date = true
	    end
	}
   
    totale_punteggio_servizi = totale_punteggio_servizi.to_f.round(6) #to_f per non avere EO alla fine...
    totale_punteggio_servizi = 20 if totale_punteggio_servizi > 20 #max 20 punti
    [totale_punteggio_servizi,errore_date]
end



           
moduli_qs =  Moduli::ModuloSalvato.where{ |mod| (mod.servizi_svolti .not nil) & (mod.tipo_modulo.categoria_giuridica.nome == 'C')}
#moduli_qs =  Moduli::ModuloSalvato.where(:id => 1949)
moduli_qs.each{|modulo|
	unless modulo.blank?
	    cat_modulo = modulo.tipo_modulo.categoria_giuridica.nome
	    next if modulo.tipo_modulo.categoria_giuridica.blank?
	    hash_servizi = JSON.parse(modulo.servizi_svolti)
	    #calcolo il totale dei servizi svolti
	    array_totale_hash = calcola_punteggio_servizi(cat_modulo,hash_servizi)
	    totale_corretto = array_totale_hash[0]
	   	errore = array_totale_hash[1]
	    Spider.logger.error "\n Errore su id #{modulo.id.to_s}" if errore
	    modulo.punteggio_servizi = totale_corretto
	    #salvo anche il punteggio totale
	    modulo.punteggio_totale = BigDecimal.new(modulo.punteggio_titoli) + BigDecimal.new(modulo.punteggio_titoli_vari) + totale_corretto
	    modulo.save
	end
}

