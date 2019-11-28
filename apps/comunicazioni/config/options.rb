# -*- encoding : utf-8 -*-
Spider.config_option 'comunicazioni.numero_comunicazioni_index', "Numero di comunicazioni visualizzate nell'index delle comunicazioni", :type => Integer,
	:default => 10

Spider.config_option 'comunicazioni.canali_comunicazione' , "Canali di comunicazione configurabili (portale, sms, email)", :type => Array,
    :default => []

Spider.config_option 'comunicazioni.servizio_privato', 'Impostazione di default per indicare se il servizio è privato o se è pubblico', :type => Spider::Bool, :default => false

Spider.config_option 'comunicazioni.ws_area_riservata_abilitata', 'Impostazione per indicare se il servizio manda tramite ws dati per l\'area riservata delle mobile apps ', :type => Spider::Bool, :default => false
Spider.config_option 'comunicazioni.consenti_sms_a_tutti', 'Indica se anche gli amministratori del servizio comunicazioni possono usare il canale sms per inviare comunicazioni ', :type => Spider::Bool, :default => false

Spider.config_option 'comunicazioni.page_id_facebook', 'Contiene il page_id della pagina facebook dove pubblica il comune, da vedere nelle impostazioni della pagina in Facebook ', :type => String
Spider.config_option 'comunicazioni.app_id_facebook', 'Contiene l\'id dell\' applicazione facebook fatta per il comune, da vedere nelle impostazioni della pagina in Facebook ', :type => String
Spider.config_option 'comunicazioni.user_id_facebook', 'Contiene l\'id (o gli id separati da virgola) degli utenti abilitati a pubblicare sulla pagina e amministratori sull\'app Comunicazioi Euro Servizi di Facebook ', :type => String

Spider.config_option 'comunicazioni.api_key_twitter', 'Contiene la api_key che si vede nella app twitter fatta per il comune', :type => String
Spider.config_option 'comunicazioni.api_secret_twitter', 'Contiene la api_secret che si vede nella app twitter fatta per il comune', :type => String

Spider.config_option 'comunicazioni.lingue_traduzioni', "Array con nome della lingua per traduzioni", :type => Array, :default => []

Spider.config_option 'comunicazioni.pubbliche_per_gruppi', "Indica se le comunicazioni pubbliche possono essere inviate ai gruppi del portale.", :type => Spider::Bool, :default => false

Spider.config_option 'comunicazioni.pubblica_home_cms', 'Indica se usando il canale cms pubblica la home page', :type => Spider::Bool, :default => false

Spider.config_option 'comunicazioni.integra_in_prisma', 'Indica se viene usato comunicazioni integrato con prisma per usare il layout di prisma e non del portale', :type => Spider::Bool, :default => false

Spider.config_option 'comunicazioni.max_risoluzione_immagini', 'Indica la risoluzione massima in pixel (x e y) delle immagini delle news', :type => Array, :default => ['160','160']

Spider.config_option 'comunicazioni.db_push_connection', 'Hash con configurazioni per connesione al db da usare con active record: adapter, database, username, password, host', :type => Hash
Spider.config_option 'comunicazioni.app_name_ios', 'Nome della app Ios per notifica push', :type => String
Spider.config_option 'comunicazioni.app_name_android', 'Nome della app Android per notifica push', :type => String
Spider.config_option 'comunicazioni.app_auth_key', 'Auth key della applicazione Android per notifiche push', :type => String
Spider.config_option 'segnalazioni.max_risoluzione_immagini', 'Indica la risoluzione massima in pixel (x e y) delle immagini delle segnalazioni', :type => Array, :default => ['160','160']
Spider.config_option 'comunicazioni.abilita_segnalazioni', "Indica se sono abilitate le segnalazioni", :type => Spider::Bool, :default => false