# -*- encoding : utf-8 -*-
module Moduli

    class TipoModulo

        def initialize(nome)
            @nome = nome
            @path = File.join(Spider.paths[:root], 'moduli', nome)
            @doc = Hpricot(File.read(File.join(@path, "#{nome}.shtml")))
        end

        def titolo
            @doc.root.get_attribute('titolo')
        end

        def codice
            @doc.root.get_attribute('codice')
        end

    end

end
