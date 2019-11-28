# -*- encoding : utf-8 -*-
cambia_id_stato = Spider::Migrations.replace(Portal::Utente, :stato, 'email' => 'contatti')

Spider::Setup.task do

	sync_schema(Portal::Utente)
    
    up do
        Portal::Utente.element(:chiave_conferma, String)
        Portal::Utente.mapper.reset_schema
        cambia_id_stato.run
        utenti = Portal::Utente.all
        utenti.fetch_window = 100
        utenti.each do |u|
            if u.stato == 'contatti' && chiave = u.chiave_conferma
                mc = Portal::ModificaContatto.load(:tipo => 'email', :utente => u, :chiave_conferma => chiave)
                unless mc
                    Portal::ModificaContatto.create(
                        :utente => u,
                        :tipo => 'email',
                        :dopo => u.email,
                        :chiave_conferma => chiave
                    )
                end
            else
                u.email_confermata = true 
                u.save
            end
        end

    end

    down do
        cambia_id_stato.undo
    end

    cleanup do
        Spider::Migrations.drop_element!(Portal::Utente, :chiave_conferma).run
    end

end
