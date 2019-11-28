# -*- encoding : utf-8 -*-
# Ruby implementation of a Codice Fiscale generator/validator. 
# Implementazione in Ruby di un generatore e validatore del Codice Fiscale
#
# Author::    Ivan Leider  (mailto:ileider@interlink.com.ar)
# Copyright:: Copyright (c) 2006 Ivan Leider
# License::   GPL

require 'unicode'

module Portal

  # Access functionality to generate the Codice Fiscale
  # through this class 
  class CodiceFiscale

    # returns a Codice Fiscale generated automatically 
    # given the first name, last name, sex (F if female), date of birth and the birth place code 
    def self.genera_codice(nome, cognome, sesso, data_nascita, codice_comune=nil)
      codice = ""
  		@nome = nome.upcase
  		@cognome = cognome.upcase
  		@sesso = sesso.upcase
      begin
  		  @dataNascita = DateTime.strptime(data_nascita, '%d/%m/%Y')
  		rescue Exception => e
        return codice
      end

      @codiceComune = codice_comune.upcase unless codice_comune.blank?
  		
  		anno = @dataNascita.year
  		mese = @dataNascita.month
  		giorno = @dataNascita.day
  		codcontrollo = 0
  		#inizia con il calcolo dei primi sei caratteri corrispondenti al nome e cognome
  		codice = calcola_cognome(non_accentate(@cognome)) + calcola_nome(non_accentate(@nome))
  		#calcola i dati corrispondenti alla data di nascita
  		(@sesso == "F") && (giorno += 40)
  		codice += anno.to_s[2,4] + MESI[mese.to_i - 1].chr.to_s + ((giorno.to_i < 10) ? "0" : "") + giorno.to_s;
  		
  		#aggiunge il codice del comune se non è vuoto
      unless @codiceComune.blank?
        codice += @codiceComune
        #calcola il codice controllo
        15.times {|i|
           codcontrollo += Matricecod[ALL.index(codice[i].chr.to_s).to_s + ((i + 1) % 2).to_s];
        }
        codice += ALFABETO[codcontrollo % 26].chr.to_s;
      end
  		 		
  		codice
    end
    
    # check if given input is a valid codice fiscale, with short check only length, with complete check control char 
    def self.check_codice_fiscale(codice,control_type='short')
      begin
        return false if codice.strip.size != 16
        if control_type == 'complete'
          codice = codice.upcase
          s = 0
          15.times{|x| 
            s += ((x & 1 != 0) ?  PARI : DISPARI).rindex(codice[x,1]) 
          }
          PARI[(s % 26),1] == codice[15,1]
        else
          true
        end      
      rescue Exception => e
        return false
      end
    end

  private

  	MESI = "ABCDEHLMPRST"
  	VOCALI = "AEIOU"
  	CONSONANTI = "BCDFGHJKLMNPQRSTVWXYZ"
  	NUMERI = "0123456789"
  	ALFABETO = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
  	ACCENTATE = "ÀÁÈÉÍÌÓÒÚÙáàèéíìóòúù"
  	NOACCENTO = "AAEEIIOOUUAAEEIIOOUU"
  	ALL = NUMERI + ALFABETO
  	PARI = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    DISPARI="BAKPLCQDREVOSFTGUHMINJWZYX10KPL2Q3R4VOS5T6U7M8N9WZYX"
    
  	#matrice x il calcolo del carattere di controllo
  	Matricecod = {"01" => 1, "00" => 0, "11" => 0, "10" => 1, "21" => 5, "20" => 2, "31" => 7, "30" => 3, "41" => 9, "40" => 4, "51" => 13, "50" => 5, "61" => 15, "60" => 6, "71" => 17, "70" => 7, "81" => 19, "80" => 8, "91" => 21, "90" => 9, "101" => 1, "100" => 0, "111" => 0, "110" => 1, "121" => 5, "120" => 2, "131" => 7, "130" => 3, "141" => 9, "140" => 4, "151" => 13, "150" => 5, "161" => 15, "160" => 6, "171" => 17, "170" => 7, "181" => 19, "180" => 8, "191" => 21, "190" => 9, "201" => 2, "200" => 10, "211" => 4, "210" => 11, "221" => 18, "220" => 12, "231" => 20, "230" => 13, "241" => 11, "240" => 14, "251" => 3, "250" => 15, "261" => 6, "260" => 16, "271" => 8, "270" => 17, "281" => 12, "280" => 18, "291" => 14, "290" => 19, "301" => 16, "300" => 20, "311" => 10, "310" => 21, "321" => 22, "320" => 22, "331" => 25, "330" => 23, "341" => 24, "340" => 24, "351" => 23, "350" => 25}

    
    def self.calcola_nome(s)
      i = 0
      stringa = ""
      cons = ""
      while((cons.length < 4) && (i+1 <= s.length))
        (!CONSONANTI.index(s[i].chr.to_s).nil?) && (cons += s[i].chr.to_s)
        i += 1
      end
      #se sono + di 3 prende 1Â° 3Â° 4Â°
      if cons.length > 3
        stringa = cons[0].chr.to_s + cons[2].chr.to_s + cons[3].chr.to_s
        return stringa    
      else
        stringa = cons
      end
      i = 0
      #se non bastano prende VOCALI
      while((stringa.length < 3) && (i +1 <= s.length))
        (!VOCALI.index(s[i].chr.to_s).nil?) && (stringa += s[i].chr.to_s)
        i += 1
      end
      stringa += "XXX"
      stringa[0,3]
    end
    
    def self.calcola_cognome(s)
      i = 0
      stringa = ""
      #trova CONSONANTI
      while((stringa.length < 3) && (i + 1 <= s.length))
        (!CONSONANTI.index(s[i].chr.to_s).nil?) && (stringa += s[i].chr.to_s)
        i += 1
      end
      i = 0
      #se non bastano prende VOCALI
      while((stringa.length < 3) && (i +1 <= s.length))
        (!VOCALI.index(s[i].chr.to_s).nil?) && (stringa += s[i].chr.to_s)
        i += 1
      end
      stringa += "XXX"
      stringa[0,3]
    end
    
    def self.non_accentate(s)
      result = ""
  		s.each_char {|b|
            c = b.chr.to_s
            idx = ACCENTATE.index(c)
            if idx.nil?
                result += c
            next
  	    end
  		  result += NOACCENTO[idx/2].chr
  		}
    end

  end

end  
