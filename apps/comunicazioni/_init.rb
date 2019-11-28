# -*- encoding : utf-8 -*-
module Comunicazioni
    include Spider::App
    @controller = :ComunicazioniController
end

require 'apps/comunicazioni/comunicazioni'

canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
if canali_pubblicazione.include?('push_notification') || Spider.conf.get('comunicazioni.db_push_connection')
	require 'rpush'
	#file di conf per le notifiche push
	require 'apps/comunicazioni/config/rpush.rb'
	#migration da usare con active record per notifiche push
	require 'apps/comunicazioni/db/migrate/20150410140050_add_rpush.rb'
	require 'apps/comunicazioni/db/migrate/20150410140051_rpush_2_0_0_updates.rb'
	require 'apps/comunicazioni/db/migrate/20150410140052_rpush_2_1_0_updates.rb'
end

require 'apps/comunicazioni/lib/canale_comunicazione'
require 'apps/comunicazioni/models/comunicazione'
require 'apps/comunicazioni/models/segnalazione'

require 'apps/comunicazioni/controllers/comunicazioni_controller'

#aggiunta per avere l'amministratore delle comunicazioni
Spider::Admin.register_app(Comunicazioni, Comunicazioni::GestioneComunicazioniController, {
    :icon => 'app_icon.png', :priority => 3, :name => 'Comunicazioni', :users => [Portal::Amministratore], 
    :check => Proc.new { |user| user.is_a?(Spider::Auth::SuperUser) || (user.respond_to?(:servizi) && user.servizi.include?('comunicazioni')) }
})


#faccio il require dei canali di pubblicazione in base ai dati inseriti nel file di config
(Spider.conf.get('comunicazioni.canali_comunicazione') || []).each do |canale|
    require File.join('apps/comunicazioni/canali_comunicazione/', canale)
end

#se sono abilitate le notifiche push non come canale ma come invio agganciato al portale faccio il require del file
require 'apps/comunicazioni/canali_comunicazione/push_notification' unless Spider.conf.get('comunicazioni.db_push_connection').blank?

#require dei vari gestori di segnalazioni
require 'apps/comunicazioni/lib/gestore_segnalazioni'
require 'apps/comunicazioni/gestori_segnalazioni/gestore_ambiente_servizi'
