# -*- encoding : utf-8 -*-
module Moduli

    class Opzione < WidgetCampo
        tag 'opzione'

        def prepare_scene(scene)
            scene = super
            scene.name = @name || '_w'+param_name(self)+'[check]'
            scene.checked = @valore
            return scene
        end

        def dati(dati_caricati, dimensioni=nil)
            if dati_caricati.is_a?(Hash) && dati_caricati["check"] == "1"
                @valore = true
            end
            super
        end

        #h.merge(self.params)

        def serializza
            h = super
            h["check"] = self.params["check"]
            h
        end

    end

end

