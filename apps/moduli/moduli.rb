# -*- encoding : utf-8 -*-
require "bigdecimal"

module Moduli

	def self.verifica_presenza_configurazioni(configurazioni)
		applicazione = self.to_s.underscore
        configurazioni.each{ |configurazione| 
            raise "Inserire le configurazioni per applicazione #{applicazione}: #{applicazione}.#{configurazione}" if Spider.conf.get("#{applicazione}.#{configurazione}").blank?
        }
                
    end

    def self.labels(id)
		#cerco nel file di conf per le traduzioni delle voci dell'elenco con un valore
		unless @labels.blank?
			label_personalizzata = @labels['elenco'][id]
		else
			label_personalizzata = id
		end
		
	end

	def self.converti_importo(stringa_importo)
        return stringa_importo if stringa_importo.blank?
        #rimuovo € e scritta euro
        stringa_importo = stringa_importo.gsub('€','').strip
        stringa_importo = stringa_importo.upcase.gsub('EURO','').gsub('€','').strip

        #se include una virgola allora uso Numeric.lparse
        if stringa_importo.include?(',')
            importo_convertito = Numeric.lparse(stringa_importo).to_s
        else
            #arriva o un valore già sistemato oppure un formato euro senza decimali
            #controllo quante cifre ci sono dopo i punti
            array_cifre_separate = stringa_importo.split('.')
            cond_importo_convertito = false
            indice = 1
            while indice < array_cifre_separate.length
                #se ci sono gruppi di cifre < 3 allora è un importo già convertito
                cond_importo_convertito = true if array_cifre_separate[indice].length < 3
                break if cond_importo_convertito
                indice += 1
            end
            if cond_importo_convertito 
                #ho un importo del tipo 1.55 -> ok
                importo_convertito = stringa_importo
            else
                #arriva un importo 1.000.500 -> converto da euro a Numeric
                importo_convertito = Numeric.lparse(stringa_importo).to_s
            end
        end 
        importo_convertito
    end


   def self.tipo_amministrazione(id)
        case Spider.conf.get('moduli.tipologia_bando')
        when 'funzionari'
            hsh_servizi = { 'ammin_prov' => {'nome' => "Amministrazioni Provinciali", 'percentuale' => 100},
                'enti_locali' => {'nome' => "Altri Enti Locali (Comuni - Comunità Montane)", 'percentuale' => 75},
                'altri_enti' => {'nome' => "Altri Enti Pubblici", 'percentuale' => 50}
            }
        when 'dirigenti'
            hsh_servizi = { 'ammin_prov' => {'nome' => "Amministrazioni Provinciali", 'percentuale' => 100},
                'enti_locali' => {'nome' => "Altri Enti Locali (Comuni - Comunità Montane)", 'percentuale' => 50},
                'altri_enti' => {'nome' => "Altri Enti Pubblici", 'percentuale' => 25}
            }
        else
            #DIRIGENTI
            hsh_servizi = { 'ammin_prov' => {'nome' => "Amministrazioni Provinciali", 'percentuale' => 100},
                'enti_locali' => {'nome' => "Altri Enti Locali (Comuni - Comunità Montane)", 'percentuale' => 50},
                'altri_enti' => {'nome' => "Altri Enti Pubblici", 'percentuale' => 25}
            }
        end
        hsh_servizi[id.strip]
    end

    def self.hash_titoli(cat=nil)
        case Spider.conf.get('moduli.tipologia_bando')
        when 'funzionari'
            if !cat.blank? && (cat == 'D3' || cat == 'D1') 
                hsh_titoli = {
                    'laurea_vecchio' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 4},
                    'laurea_mag_unico' => {'nome' => "Laurea magistrale a ciclo unico (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_e_secondo_liv_nuovo' => {'nome' => "Laurea Primo e Secondo livello (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_liv_nuovo' => {'nome' => "Laurea primo livello (nuovo ordinamento)", 'punteggio' => 2},
                    'master' => {'nome' => "Master I e II livello", 'punteggio' => 1, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 1},
                    'titolo_necessario' => {'nome' => "Titolo di studio necessario alla partecipazione dichiarato nella domanda", 'punteggio' => 4}
                }
            elsif !cat.blank? && (cat == 'C') 
                hsh_titoli = {
                    'laurea_vecchio' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 4},
                    'laurea_mag_unico' => {'nome' => "Laurea magistrale a ciclo unico (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_e_secondo_liv_nuovo' => {'nome' => "Laurea Primo e Secondo livello (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_liv_nuovo' => {'nome' => "Laurea primo livello (nuovo ordinamento)", 'punteggio' => 2},
                    'master' => {'nome' => "Master I e II livello", 'punteggio' => 1, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 2},
                    'titolo_necessario' => {'nome' => "Titolo di studio necessario alla partecipazione dichiarato nella domanda", 'punteggio' => 1}
                }          
            else
                hsh_titoli = {
                    'laurea_vecchio' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 4},
                    'laurea_mag_unico' => {'nome' => "Laurea magistrale a ciclo unico (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_e_secondo_liv_nuovo' => {'nome' => "Laurea Primo e Secondo livello (nuovo ordinamento)", 'punteggio' => 4},
                    'laurea_primo_liv_nuovo' => {'nome' => "Laurea primo livello (nuovo ordinamento)", 'punteggio' => 2},
                    'master' => {'nome' => "Master I e II livello", 'punteggio' => 1, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 1},
                    'titolo_necessario' => {'nome' => "Titolo di studio necessario alla partecipazione dichiarato nella domanda", 'punteggio' => 2}
                }            
            end

        when 'dirigenti'
            if !cat.blank? && (cat == 'D3' || cat == 'D1')
                hsh_titoli = {
                    'laurea_primo_liv_nuovo' => {'nome' => "Master I livello", 'punteggio' => 0.5},
                    'laurea_secondo_liv_nuovo' => {'nome' => "Master II livello", 'punteggio' => 2},
                    'scuola' => {'nome' => "Scuola di specializzazione post-universitaria legalmente riconosciuta", 'punteggio' => 2, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 2.5}
                }
            else
                hsh_titoli = {
                    'laurea_primo_liv_nuovo' => {'nome' => "Master I livello", 'punteggio' => 0.5},
                    'laurea_secondo_liv_nuovo' => {'nome' => "Master II livello", 'punteggio' => 2},
                    'scuola' => {'nome' => "Scuola di specializzazione post-universitaria legalmente riconosciuta", 'punteggio' => 2, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 2.5}
                }
            end
        else
            #DIRIGENTI
            if !cat.blank? && (cat == 'D3' || cat == 'D1')
                hsh_titoli = {
                    'laurea_primo_liv_nuovo' => {'nome' => "Master I livello", 'punteggio' => 0.5},
                    'laurea_secondo_liv_nuovo' => {'nome' => "Master II livello", 'punteggio' => 2},
                    'scuola' => {'nome' => "Scuola di specializzazione post-universitaria legalmente riconosciuta", 'punteggio' => 2, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 2.5}
                }
            else
                hsh_titoli = {
                    'laurea_primo_liv_nuovo' => {'nome' => "Master I livello", 'punteggio' => 0.5},
                    'laurea_secondo_liv_nuovo' => {'nome' => "Master II livello", 'punteggio' => 2},
                    'scuola' => {'nome' => "Scuola di specializzazione post-universitaria legalmente riconosciuta", 'punteggio' => 2, 'max_punti' => 2},
                    'dottorato' => {'nome' => "Dottorato di ricerca", 'punteggio' => 2.5}
                }
            end
        end
        hsh_titoli
    end


    def self.hash_titoli_vari
        case Spider.conf.get('moduli.tipologia_bando')
        when 'funzionari'
            hsh_titoli = { 
                'laurea_vecchio' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 1},
                'laurea_mag_unico' => {'nome' => "Laurea magistrale a ciclo unico (nuovo ordinamento)", 'punteggio' => 1},
                'laurea_secondo_liv_nuovo' => {'nome' => "Laurea secondo livello (nuovo ordinamento)", 'punteggio' => 0.5},
                'laurea_primo_liv_nuovo' => {'nome' => "Laurea primo livello (nuovo ordinamento)", 'punteggio' => 0.5},
                'master_dottorato' => {'nome' => "Master I e II livello - Dottorato di ricerca", 'punteggio' => 0.5, 'max_punti' => 1}
            }
        when 'dirigenti'
            hsh_titoli = { 'pubblicazione' => {'nome' => "Pubblicazione", 'punteggio' => 0.25},
                'albo' => {'nome' => "Iscrizioni ad Albi Professionali congruenti con i titoli di studio richiesti per l'ammissione alla selezione", 'punteggio' => 1},
                'master' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 0.5}
            }
        else
            #DIRIGENTI
            hsh_titoli = { 'pubblicazione' => {'nome' => "Pubblicazione", 'punteggio' => 0.25},
                'albo' => {'nome' => "Iscrizioni ad Albi Professionali congruenti con i titoli di studio richiesti per l'ammissione alla selezione", 'punteggio' => 1},
                'master' => {'nome' => "Laurea (vecchio ordinamento)", 'punteggio' => 0.5}
            }
        end
        hsh_titoli
    end

    def self.tipo_titolo(id,cat=nil)
        unless cat.blank?
            hsh_titoli = hash_titoli(cat)
        else
            hsh_titoli = hash_titoli
        end
        hsh_titoli[id.strip]
    end

    def self.tipo_titolo_vario(id)
        hsh_titoli = hash_titoli_vari
        hsh_titoli[id.strip]
    end

    
    # #Punteggi giornalieri per cat:
    #CAT_UGUALE = BigDecimal.new("0.00365030115") #(BigDecimal.new(1.33,3) / 365) #uguale al posto in concorso 1.33 / 365
    #CAT_MENO_UNO = BigDecimal.new("0.00182515058") #(BigDecimal.new(0.67,3) / 365) #immediatamente inferiore al posto in concorso 0.67 / 365
    #CAT_MENO_N = BigDecimal.new("0.00091257529") #(BigDecimal.new(0.33,3) / 365) #ulteriormente inferiore al posto in concorso 0.33 / 365
    #ORDINE_CATEGORIE = { 'A' => 1, 'B1' => 2, 'B3' => 3, 'C' => 4, 'D1' => 5, 'D3' => 6 }
    #
    #def self.calcola_punteggio_esperienza_lavorativa(cat_giuridica_bando,cat_giuridica_servizio,giorni_servizio,tipo_amministrazione,rid_lavorativa)
    #    livello_cat_modulo = ORDINE_CATEGORIE[cat_giuridica_bando]
    #    livello_cat_servizio = ORDINE_CATEGORIE[cat_giuridica_servizio]
    #    if livello_cat_modulo == livello_cat_servizio || livello_cat_servizio > livello_cat_modulo
    #        coeff_cat = CAT_UGUALE 
    #    elsif livello_cat_modulo == (livello_cat_servizio + 1) #cat servizio immediatamente inferiore
    #        coeff_cat = CAT_MENO_UNO 
    #    else
    #        coeff_cat = CAT_MENO_N 
    #    end
    #    #calcolo n giorni per punti categoria 
    #    valore_servizio = giorni_servizio * coeff_cat
    #    #percentuale tipo amministrazione
    #    perc_tipo_amministrazione = Moduli.tipo_amministrazione(tipo_amministrazione)['percentuale']
    #    valore_servizio = (valore_servizio / 100) * perc_tipo_amministrazione
    #    #calcolo part time
    #    perc_riduzione_part_time = rid_lavorativa.to_f
    #    valore_servizio = (valore_servizio / 100) * (100.00 - perc_riduzione_part_time) if !perc_riduzione_part_time.blank? && perc_riduzione_part_time > 0 
    #    #arrotondo a 11 decimali
    #    valore_servizio = valore_servizio.round(11)
    #end
	
    case Spider.conf.get('moduli.tipologia_bando')
    when 'funzionari'
        CAT_UGUALE = BigDecimal.new("0.00365030115") #(BigDecimal.new(1.33,3) / 365) #uguale al posto in concorso 1.33 / 365
        CAT_MENO_UNO = BigDecimal.new("0.00182515058") #(BigDecimal.new(0.67,3) / 365) #immediatamente inferiore al posto in concorso 0.67 / 365
        CAT_MENO_N = BigDecimal.new("0.00091257529") #(BigDecimal.new(0.33,3) / 365) #ulteriormente inferiore al posto in concorso 0.33 / 365
        ORDINE_CATEGORIE = { 'A' => 1, 'B1' => 2, 'B3' => 3, 'C' => 4, 'D1' => 5, 'D3' => 6 }
    when 'dirigenti'
        #Punteggi giornalieri per cat:
        CAT_UGUALE = BigDecimal.new("0.00365030115") #(BigDecimal.new(1.33,3) / 365) #uguale al posto in concorso 1.33 / 365
        CAT_MENO_UNO = BigDecimal.new("0.00182515058") #(BigDecimal.new(0.67,3) / 365) #immediatamente inferiore al posto in concorso 0.67 / 365
        CAT_MENO_N = BigDecimal.new("0.00091257529") #(BigDecimal.new(0.33,3) / 365) #ulteriormente inferiore al posto in concorso 0.33 / 365
        ORDINE_CATEGORIE = { 'D' => 1, 'C' => 2, 'B' => 3 }
    else
        #DIRIGENTI
        #Punteggi giornalieri per cat:
        CAT_UGUALE = BigDecimal.new("0.00365030115") #(BigDecimal.new(1.33,3) / 365) #uguale al posto in concorso 1.33 / 365
        CAT_MENO_UNO = BigDecimal.new("0.00182515058") #(BigDecimal.new(0.67,3) / 365) #immediatamente inferiore al posto in concorso 0.67 / 365
        CAT_MENO_N = BigDecimal.new("0.00091257529") #(BigDecimal.new(0.33,3) / 365) #ulteriormente inferiore al posto in concorso 0.33 / 365
        ORDINE_CATEGORIE = { 'D' => 1, 'C' => 2, 'B' => 3 }
    end

    def self.calcola_punteggio_esperienza_lavorativa(cat_giuridica_bando,cat_giuridica_servizio,giorni_servizio,tipo_amministrazione,rid_lavorativa)
        livello_cat_modulo = ORDINE_CATEGORIE[cat_giuridica_bando]
        livello_cat_servizio = ORDINE_CATEGORIE[cat_giuridica_servizio]
        if livello_cat_modulo == livello_cat_servizio || livello_cat_servizio > livello_cat_modulo
            coeff_cat = CAT_UGUALE 
        elsif livello_cat_modulo == (livello_cat_servizio + 1) #cat servizio immediatamente inferiore
            coeff_cat = CAT_MENO_UNO 
        elsif livello_cat_modulo == (livello_cat_servizio + 2) #cat servizio ancora inferiore
            coeff_cat = CAT_MENO_N
        else
            coeff_cat = 0
        end
        #Calcolo richiesto con mail del 12/04
        #ovvero quando l'utente nelle esperienze lavorative seleziona la categoria giuridica 
        #Leva Obbligatoria o Servizio civile volontario nel calcolo del punteggio deve applicare 
        #sempre il coefficiente 0,00091257529 diviso 2 a prescindere da dove abbia prestato servizio 
        #(Amministrazioni Provinciali, Altri Enti Locali (Comuni- Comunità Montane, Altri Enti Pubblici)
        if Spider.conf.get('moduli.tipologia_bando') == 'funzionari' && cat_giuridica_servizio == 'B1'
            coeff_cat = (coeff_cat / 2)
        end
        #calcolo n giorni per punti categoria 
        valore_servizio = giorni_servizio * coeff_cat
        return valore_servizio if valore_servizio == 0
        #percentuale tipo amministrazione

        if Spider.conf.get('moduli.tipologia_bando') == 'funzionari' && cat_giuridica_servizio != 'B1'
            perc_tipo_amministrazione = Moduli.tipo_amministrazione(tipo_amministrazione)['percentuale']
            valore_servizio = (valore_servizio / 100) * perc_tipo_amministrazione
        end
        
        #calcolo part time
        perc_lavorativa = rid_lavorativa.to_f
        valore_servizio = (valore_servizio / 100) * perc_lavorativa if !perc_lavorativa.blank? && perc_lavorativa > 0 
        #arrotondo a 11 decimali
        valore_servizio = valore_servizio.round(11)
    end

    def self.hash_punteggi_bandi
        hash_punteggi_bandi = {
            'dirigenti' => {
                'max_punti_titoli' => 6,
                'max_punti_titoli_vari' => 4
            },
            'funzionari' => {
                'max_punti_titoli' => 8,
                'max_punti_titoli_vari' => 2
            }
        }
    end


    #Ridefinisco la classe Decimal
    class Decimal < BigDecimal
        include Spider::DataType

        #maps_back_to superclass
        
        take_attributes :scale
        
        # def self.from_value(value)
        #     return nil if value.nil?
        #     super(value.to_s)
        # end
        
        #converto quello che arriva che può essere un importo di formato euro in un numerico
        def self.from_value(value)
            return nil if value.nil?
            if value.is_a?(::BigDecimal)
                super(Moduli.converti_importo(value.to_s('F')))
            else
                #in ruby 2.4 se ho una stringa vuota ritorna *** ArgumentError Exception: invalid value for BigDecimal(): ""
                super(Moduli.converti_importo( (value == "" ? "0.0" : value.to_s ) ))
            end
            
        end

        def prepare
            self.class.from_value(self.round(attributes[:scale] || 2))
        end
        
        # def to_s(s=nil)
        #     s ||= "#{attributes[:scale]}F"
        #     super(s)
        # end
        
        #visualizzo un formato euro, questa classe viene usata solo per valori EURO
        def to_s(s=nil)
            if self.respond_to?(:lformat)
                return self.lformat
            else
                s ||= "#{attributes[:scale]}F"
                super(s)
            end
        end

        def as_json(options = nil) #:nodoc:
            finite? ? to_s : nil
        end
        
        def to_json(options=nil)
            to_f.to_json
        end
        
        # def attributes
        #     {:scale => 2}.merge(super)
        # end
        
        def attributes
            {:scale => nil}.merge(super)
        end


    end
    
	

end
