# -*- encoding : utf-8 -*-
require "rss"

module Comunicazioni; module CanaleComunicazione

    class Rss < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        

        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli
        canale_comunicazione( 
            :id => "rss",
            :nome => "RSS",
            :immagine => "rss.png"
        )

        def self.canale_attivo(request_da_chiamante)
            true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            #richiamo la pubblicazione
            self.genera_feed(comunicazione)
        end



        def self.genera_feed(comunicazione=nil)
            oggi = DateTime.now
            #carico le comunicazioni presenti che sono state pubblicate sul portale e non scadute
            comunicazioni_rss = Comunicazioni::Comunicazione.where{ |com| (com.id .not comunicazione.id) & (com.stato == 'pubblicata') & (com.pubblica == true) & (com.canali_pubblicazione .like "%rss%") & (com.data_da <= oggi) & (com.data_a >= oggi) }
            uri = URI.parse(self.http_s_url)
            dominio = uri.scheme+"://"+uri.host+":"+uri.port.to_s


            rss = RSS::Maker.make("2.0") do |maker|
                maker.channel.author = Spider.conf.get('ente.nome')
                maker.channel.updated = Time.now.to_s
                maker.channel.link = Comunicazioni::ComunicazioniController.http_s_url('feed_rss')
                maker.channel.description = "Feed RSS di #{Spider.conf.get('ente.nome')}"
                maker.channel.title = "RSS #{Spider.conf.get('ente.nome')}"

                comunicazioni_rss.each{ |com_rss|
                    maker.items.new_item do |item|
                        item.link = "#{dominio}/portal/servizi/comunicazioni/#{com_rss.id}/pubblica"
                        item.title = com_rss.titolo
                        item.description = com_rss.testo_breve
                        item.date = com_rss.obj_modified.to_date.to_s
                    end
                }
                unless comunicazione.blank?
                    maker.items.new_item do |item|
                        item.link = "#{dominio}/portal/servizi/comunicazioni/#{comunicazione.id}/pubblica"
                        item.title = comunicazione.titolo
                        item.description = comunicazione.testo_breve
                        item.date = comunicazione.obj_modified.to_date.to_s
                    end
                end
            end
            save_dir = Spider.paths[:root]+"/public/rss_comunicazioni"
            FileUtils.mkdir_p(save_dir) unless File.directory?(save_dir)
            path_rss_feed = File.join(save_dir, 'feed.xml')
            File.open(path_rss_feed, "w") { |f| f.write(rss) }
        end




    end
end;end