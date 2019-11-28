# -*- encoding : utf-8 -*-

module Portal
    include Spider::App
    @controller = :PortalController
    @short_prefix = Spider.conf.get('portal.prefisso_tabelle') if Spider.conf.get('portal.prefisso_tabelle')    
end
Spider::Template.register_namespace('portal', Portal)
Spider.load_app('core')

require 'apps/core/auth/lib/rbac'
RBAC.define_context :utente_portale, Spider::OrderedHash[
], :element_label => 'Permessi'

#includo i models per notifiche
Portal.req 'models/notifiche/applicazione'
Portal.req 'models/notifiche/notifica'

#definisco le apps per le notifiche
Portal.req 'notifiche_app/demografici'
Portal.req 'notifiche_app/muse'
Portal.req 'notifiche_app/pagamenti'

#includo librerie per le notifiche
Portal.req 'lib/notifiche'

Spider.register_resource_type(:sms, :extensions => ['erb'], :path => 'templates/sms')

Portal.req 'lib/servizio_portale'
Portal.req 'portal', 'models/mixins/autenticazione', 'lib/utente_virtuale'
unless Spider.conf.get('portal.no_db')
    #se devo usare le province e comuni tabellati con valori di Civilia DEDA carico le tabelle e i modelli
    if Spider.conf.get('portal.comuni_province_tabellate')
        Portal.req 'models/comune', 'models/provincia'
    end

    if Spider.conf.get('portal.province_tabellate')
        Portal.req 'models/provincia'
    end
    
    Portal.req 'models/servizio', 'models/attributo_utente', 'models/utente', 'models/ditta', 'models/utente_login', 'models/utente_esterno'
    Portal.const_set(:SuperUser, Spider::Auth::SuperUser)
    Portal::AttributoUtente.data Spider.conf.get('portal.attributi_aggiuntivi')
else
    Portal.req 'lib/utente_dummy'
end
Portal.req 'lib/auth_provider'
    
Spider.conf.get('portal.autenticazioni_esterne').each do |auth|
    res = Spider.find_resource(:portal_auth_providers, auth, nil, Portal)
    require res.path
end

#includo l'api_controller per la sincro openweb-civilia
Portal.req 'controllers/api_controller'
#Portal.req 'controllers/new_api'
Portal.req 'controllers/portal_controller'

if Spider.conf.get('portal.conferma_cellulare') && Spider.conf.get('messenger.sms.backend').blank?
	raise "Per la conferma cellulare, impostare messenger.sms.backend"
end

Portal.req 'controllers/portal_admin_controller'

Spider::Admin.register_app(Portal, Portal::AdminController, {
    :icon => 'app_icon.png', :priority => 10
})

if locale = Spider.conf.get('portal.locale_fisso')
    Spider.conf.set('locale', locale)
end

#includo la tabella per i registrationId dei devici mobili (Android, Iphone)
Portal.req 'models/mobile_device_registrati'

#uso delle tabelle hippo per settori
if Spider.conf.get('portal.attiva_settori_hippo')
    require 'apps/portal/models/hippo/settore'
    require 'apps/portal/models/hippo/procedimento'
    require 'apps/portal/models/hippo/responsabile'
    #da importare per far funzionare i modelli hippo
    require 'apps/portal/models/hippo/modelli_beneficio'
    require 'apps/portal/models/hippo/mapper_hippo'        
else
    require 'apps/portal/models/settore'
    require 'apps/portal/models/procedimento'
    require 'apps/portal/models/responsabile'
end

#includo medello per amministratori dei servizio
Portal.req 'models/amministratore'

#includo lo script per il calcolo del codice fiscale per la registrazione
Portal.req 'lib/calcola_cf'

#includo la classe per le anagrafiche pagopa
Portal.req 'models/anagrafica'

#includo la classe con gli scripts del portal
Portal.req 'scripts/attiva_servizi_utenti'
Portal.req 'scripts/converti_province_sigla'

#includo la classe per le anagrafiche pagopa
Portal.req 'models/gdpr'

#includo la traccia per salvare gli accessi cas e i servizi esterni
Portal.req 'models/traccia'


