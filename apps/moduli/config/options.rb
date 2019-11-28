# -*- encoding : utf-8 -*-
module Spider

config_option 'moduli.mail_invio_moduli', 'Indirizzo mail a cui inviare i moduli con gli allegati', :type => String, :default => Proc.new{ Spider.conf.get('portal.email_amministratore') }

config_option 'moduli.aggiungi_dati_campo_dati', "Impostazione per indicare se si esportano in csv anche i campi interni del modulo nel campo Dati della tabella", :type => Spider::Bool, 
	:default => false

config_option 'moduli.label_per_elenco_in_csv', "Indica la label nel csv da mostrare se è presente un elenco lungo", :type => String, :default => "Scelta"

config_option 'moduli.url_ws_caricamento_pratiche', "Url completo tranne parametro per il web service getJSON per gli Allegati, Eventi e Interventi", :type => String, :default => "#{Spider.conf.get('site.domain')}/openweb/caricamento_pratiche/services/getJSON.php" 

config_option 'moduli.includi_piede_informative', 'Imposta se avere o no il piede comune su tutti i moduli con le informative', :type => Spider::Bool, :default => false

config_option 'moduli.testo_informativa_piede', "Testo da visualizzare nell'informativa nel piede, se attivo", :type => String

config_option 'moduli.abilita_scelta_font', "Imposta se mostrare scelta font e dimensione del testo su editor (Default: false)", :type => Spider::Bool, :default => false


config_option 'moduli.tipologia_bando', "Imposta tipo di bando (dirigenti, funzionari)", :type => String, :default => "dirigenti"

config_option 'moduli.protocollo_interoperabile', 'Attiva la protocollazione interoperabile (Default: nil, Opt: iride_web, civiliaopen, etc)', :type => String, :default => nil
config_option 'moduli.protocollo', 'Parametri di connessione al webservice', :type => Hash, :default => nil #{ 'service' => '', 'endpoint' => '', 'auth' => '' }
config_option 'moduli.protocollo_mittente', 'Indirizzo di posta da usare forzatamente come mittente', :type => String, :default => nil
end
