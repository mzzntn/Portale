# -*- encoding : utf-8 -*-
if defined?(Pagamenti) != nil
	require 'apps/pagamenti/models/tipo_dovuto'
end

module Moduli

    class Importo < Spider::Model::Managed
        #carico tutti i tipi dovuto di tipo 1
    	if defined?(Pagamenti) != nil
            choice :tipo_dovuto, ::Pagamenti::TipoDovuto, :required => :true, :condition => { :modalita_pagamento => 'interattivo' }
        end
        element :codice, String , :label => 'Codice (1-10 caratteri)', :required => :true, :check => { "Numero di caratteri del Codice da 1 a 10" => Proc.new{ |val| ( (val!="" && val!=nil && val.length > 10) || (val == "" || val == nil) ) ? false : true } }
    	element :descrizione, String
    	#Se importo:
        # 0: gratuito
        # != 0: importo del modulo
        element :importo, Moduli::Decimal, :label => 'Importo (€)', :currency => :euro, :check => { "Inserire un importo maggiore di zero o zero se gratuito" => Proc.new{ |val|  ( (val < 0 ) ? false : true) } }
        choice :tipo_obbligatorieta, { 'obbligatorio' => 'Obbligatorio', 
                                       'solo_uno' => 'Solo Uno Opzionale', 
                                       'almeno_uno' => 'Almeno Uno Obbligatorio',
                                       'facoltativo' => 'Opzionale' } , :required => :true, :label => 'Tipo di Obbligatorietà'
        element :bollo, Spider::Bool, :default => :false
        #se importo_utente a true è un importo libero che deve indicare il cittadino. Non serve indicare l'importo
        element :importo_utente, Spider::Bool, :label => 'Importo libero', :default => :false

    end





end