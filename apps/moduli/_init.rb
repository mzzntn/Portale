# -*- encoding : utf-8 -*-
module Moduli
    include Spider::App
    @controller = :ModuliController
    unless Spider.conf.get('moduli.label_per_elenco_in_csv').blank?
        begin
        	@labels = YAML.load_file('labels_per_elenco_csv.yml')
        rescue Exception => e
        	Spider.logger.error "Inserire file per label dell'elenco per estrazione dati"
        	@labels = nil
        end
    end

end

#require 'apps/moduli/lib/tipo_modulo'

##DA TESTARE: IMPOSTO PAGAMENTI PRIMA DI MODULI PER NON AVERE ERRORI
# #Controllo se presente pagamenti nelle apps che sia inserito prima di moduli
# path_file_yml = File.join(Spider.paths[:root],"config/config.yml")
# configurazioni = YAML.load_file(path_file_yml)
# apps = configurazioni['apps']
# if apps.include?('pagamenti') && apps.index('pagamenti') > apps.index('moduli')
#     index_pagamenti = apps.index('pagamenti')
#     index_moduli = apps.index('moduli')
#     #scambio le due app  
#     apps[index_pagamenti] = 'moduli'
#     apps[index_moduli] = 'pagamenti'
#     File.open(path_file_yml, 'w+') { |file| file.write(configurazioni.to_yaml) }
# end

require 'apps/moduli/moduli'
require 'apps/moduli/models/categoria_giuridica'
require 'apps/moduli/models/importo'
require 'apps/moduli/models/modulo_salvato'
require 'apps/moduli/models/tipo_modulo'


require 'apps/moduli/lib/widget_modulo'
require 'apps/moduli/lib/widget_campo'
require 'apps/moduli/widgets/dato_utente/dato_utente'
require 'apps/moduli/widgets/modulo/modulo'
require 'apps/moduli/widgets/gruppo/gruppo'
require 'apps/moduli/widgets/opzioni/opzioni'
require 'apps/moduli/widgets/opzione/opzione'
require 'apps/moduli/widgets/dato/dato'
require 'apps/moduli/widgets/allegato/allegato'
require 'apps/moduli/widgets/data_firma/data_firma'

require 'apps/moduli/lib/xml_utils'
require 'apps/moduli/lib/funzioni'

#verifico i moduli online e in base a quelli carico i relativi gestori
pattern = File.join("moduli/**", "*.shtml")
mod = Dir.glob(pattern)
mod.each do |m|  
    prefix = m.gsub(/moduli\/(.*?)\/.*/) {$1}  #trovo la cartella di riferimento dei moduli
    modulo = m.gsub(/moduli\/.*\/(.*).shtml/) {$1}
    next if modulo[0] == '_' #attualmente non ci sono gestori per la testata di un modulo quindi salto
    #carico i gestori per caricare dati su modulo
    path_modulo = "apps/moduli/gestori/#{prefix}/gestore_#{modulo.gsub('.rb','')}"
    require path_modulo if File.exist?(File.join(Spider.paths[:root], path_modulo + '.rb'))
end
# #carico i gestori per caricare dati su modulo
# Dir.foreach('apps/moduli/gestori') do |item|
#         #salto le cartelle nascoste
#         next if item[0] == '.'
#         require 'apps/moduli/gestori/'+item.gsub('.rb','')
# end

require 'apps/moduli/controllers/moduli_controller'
require 'apps/moduli/controllers/gestione_moduli_controller'

Spider::Template.register_namespace('moduli', Moduli)

#aggiunta per avere l'amministratore dei moduli
Spider::Admin.register_app(Moduli, Moduli::GestioneModuliController, {
    :icon => 'app_icon.png', :priority => 6, :name => 'Moduli', :users => [Portal::Amministratore], 
    :check => Proc.new { |user| user.is_a?(Spider::Auth::SuperUser) || (user.respond_to?(:servizi) && user.servizi.include?('moduli')) }
})

