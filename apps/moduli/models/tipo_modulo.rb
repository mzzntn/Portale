# -*- encoding : utf-8 -*-
require 'fileutils'

module Moduli
    class TipoModulo < Spider::Model::Managed
        element :nome, String, :label => "Nome modulo"
        element :tipo, String, :label => "Tipo template" #nome del template
        element :disponibile_dal, DateTime
        element :disponibile_al, DateTime
        # element :ore_dal, Time, :label => 'Inizio Ore'
        # element :ore_al, Time, :label => 'Fine Ore'
        element :descrizione, Text
        many :moduli, Moduli::ModuloSalvato, :add_reverse => :tipo_modulo#, :delete_cascade => true lentissimo e meglio se rimangono i moduli creati
        element :tipo_compilazione, Spider::OrderedHash[
            'uno', 'Una volta',
            'molti', 'Più volte'
        ], :label => "Compilabile"
        element :stato_visualizzazione, Spider::OrderedHash[
            'test', 'Fase di test',
            'visibile', 'Pubblico'
        ], :label => "Stato"

        element :mail_destinatari, String
        element :mail_a_compilatore, Spider::Bool
        element :solo_pratiche, Spider::Bool
        element :per_iscrizioni_scolastiche, Spider::Bool, :label => "Da Iscrizioni"
        element :contenuto_modulo, Text, :length => 100000000 #contenuto html del file proveniente dall'editor
        element :nome_file, String      # nome fisico del file salvato su filesystem
        element :campi_obbligatori, Text
        element :allegati_associati, Text
        element :eventi_associati, Text
        element :interventi_associati, Text

        element :settore, Portal::Hippo::Settore #non collego con add_multiple_reverse
        element :procedimento, Portal::Hippo::Procedimento #non collego con add_multiple_reverse
        element :responsabile, Portal::Hippo::Responsabile #non collego con add_multiple_reverse
        element :tipo_firma, Spider::OrderedHash[
            'solo_p7m', 'Solo p7m',
            'p7m_pdf', 'p7m o pdf'
        ]
        #Queste informazioni servono per implementare il file xml per il protocollo interoperabile con IRIDE di MAGGIOLI
        if ['iride_web'].include?(Spider.conf.get('moduli.protocollo_interoperabile'))
            element :classifica, String
            element :tipo_documento, String
            element :in_carico_a, String
        end
        #fine informazioni

        many :importi, Moduli::Importo, :add_reverse => :tipo_modulo

        #posso associare una categoria giuridica
        element :categoria_giuridica, Moduli::CategoriaGiuridica
        #codice del servizio su richiesta di muse (campo codice della tabella prestazioni) o valore 'tutti' per funzionamento di default
        element :servizio, String

        # def initialize(nome)
        #     @nome = nome
        #     @path = File.join(Spider.paths[:root], 'moduli', nome)
        #     @doc = Hpricot(File.read(File.join(@path, "#{nome}.shtml")))
        # end

        # def titolo
        #     @doc.root.get_attribute('titolo')
        # end

        # def codice
        #     @doc.root.get_attribute('codice')
        # end


        #Inserisco i moduli di base da visualizzare con l'editor
        def self.after_sync
            #creo la cartella moduli se non esiste
            path_file = File.join(Spider.paths[:root],'moduli')
            Dir.mkdir(path_file) unless File.exists?(path_file)
            #creo la cartella con il nome del comune
            nome_ente = Spider.conf.get('portal.nome').gsub(/[^a-z^A-Z]/,"_")
            path_file = File.join(path_file, nome_ente)
            unless File.exists?(path_file)
                Dir.mkdir(path_file)
                #copio i moduli di base nella cartella creata
                path_origine = File.join(Spider.paths[:apps]+"/moduli/moduli_base/moduli_editor_base")
                path_destinazione = path_file

                if File.exist?(path_origine)
                    FileUtils.cp_r(path_origine+'/.', path_destinazione)
                end
            end
            #carico in db i dati
            if self.count == 0
                path = File.join(Spider.paths[:apps],"/moduli/setup/seed_tipi_modulo.yml")
                Spider::Model.load_fixtures(path)
            end

        end

        def before_delete
            unless self.nome_file.blank?
                #rinomino la cartella del template dei moduli
                nome_ente = Spider.conf.get('portal.nome').gsub(/[^a-z^A-Z]/,"_")
                path_dir = File.join(Spider.paths[:root],'moduli', nome_ente, self.nome_file)
                if !self.nome_file.blank? && File.exists?(File.join(path_dir, self.nome_file.downcase+".shtml"))
                    FileUtils.mv File.join(path_dir, self.nome_file.downcase+".shtml"), File.join(path_dir, "cancellato_"+self.nome_file.downcase+".shtml")
                    path_dir_cancellata = File.join(Spider.paths[:root],'moduli', nome_ente, "cancellato_"+self.nome_file)
                    FileUtils.mv path_dir, path_dir_cancellata
                end
            end
        end 

        def self.presente_modulo_per_iscrizioni
            qs_modulo_per_iscrizioni = self.where{ |tipo_modulo| tipo_modulo.per_iscrizioni_scolastiche == true }
            qs_modulo_per_iscrizioni.length == 1
        end



    end

end
