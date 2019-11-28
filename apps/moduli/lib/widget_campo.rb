# -*- encoding : utf-8 -*-
module Moduli

    class WidgetCampo < WidgetModulo
        is_attribute :label
        is_attribute :size
        is_attribute :"label-align"


        def parse_runtime_content(doc, src_path=nil)
            lab = doc.at('>label')
            @label = lab.innerHTML.strip if lab
            doc.search('>label').remove
            super(doc, src_path)
        end


        def self.parse_content(doc)
            label = nil
            if lab = doc.root.at('>label')
                label = lab.innerHTML.strip
                doc.root.search('>label').remove
            else
                label = doc.root.search('>text()').remove.inject(""){ |l, t| l+t.to_s }.strip
            end
            unless doc.root.to_s.strip.empty?
                app = doc.root.innerHTML
                doc.root.innerHTML = "<tpl:append>#{app}</tpl:append>"
            end 
            runtime, overrides = super(doc)
            runtime = "<label>#{label}</label>" if label
            return [runtime, overrides]
        end

        def valore=(val)
            @valore = val
            @input.value = @valore if @input
        end

        

    end

end
