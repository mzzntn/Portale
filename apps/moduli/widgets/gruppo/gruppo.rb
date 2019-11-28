# -*- encoding : utf-8 -*-
module Moduli

    class Gruppo < WidgetModulo
        is_attribute :nome
        is_attribute :titolo

        tag 'gruppo'

        def self.parse_content_xml(xml)
            super("<tpl:append>#{xml}</tpl:append>")
        end


    end

end

