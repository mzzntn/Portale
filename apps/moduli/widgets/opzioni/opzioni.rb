# -*- encoding : utf-8 -*-
module Moduli

    class Opzioni < WidgetModulo
        is_attribute :nome

        tag 'opzioni'

        def self.parse_content_xml(xml)
            super("<tpl:append>#{xml}</tpl:append>")
        end


    end

end

