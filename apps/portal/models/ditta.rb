# -*- encoding : utf-8 -*-
module Portal
    
    class Ditta < Spider::Model::Managed
        label 'Azienda Utente', 'Aziende Utenti'
        element :ragione_sociale, String
        element :partita_iva, String#, :check => /^[A-Za-z0-9]{11}$/
        element :codice_fiscale_azienda, String
        element :referente, Portal::Utente, :add_reverse => :ditta, :reverse_delete_cascade => true
        integrate :referente
        element :indirizzo_azienda, String, :label => 'Indirizzo Azienda'
        #separazione indirizzo in campi multipli 12/2014
        element :civico_azienda, String, :label => 'Civico'
        element :cap_azienda, String, :label => 'C.A.P. Comune Azienda', :check => /^[0-9]+$/
        if Spider.conf.get('portal.comuni_province_tabellate') == true
            # elementi tabellati
            choice :comune_azienda_tab, Portal::Comune, :label => 'Comune Azienda', :condition => {:data_abrogazione => nil}, :order => :nome
            choice :provincia_azienda_tab, Portal::Provincia, :label => 'Provincia Azienda'
        elsif Spider.conf.get('portal.province_tabellate') == true
            element :comune_azienda, String, :label => 'Comune Azienda', :check => /^[A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/
            choice :provincia_azienda_tab, Portal::Provincia, :label => 'Provincia Azienda'
        else
            element :comune_azienda, String, :label => 'Comune Azienda', :check => /^[A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/
            element :provincia_azienda, String, :label => 'Provincia Azienda', :check => /^[A-Za-zÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù'\s]+$/
        end

        element :telefono_azienda, String, :check => /^[0-9]+$/
        element :fax_azienda, String, :check => /^[0-9]+$/
        element :email_azienda, String, :label => 'E-mail Aziendale', :check => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/
        element :pec_azienda, String, :label => 'Pec Aziendale', :check => /^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/

    end
    
    
end
