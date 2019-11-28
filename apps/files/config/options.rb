# -*- encoding : utf-8 -*-
module Spider
   
   config_option 'files.storage', _("Where to store files"), :default => 'disk', :choices => ['disk'] 
   config_option 'files.disk.path', _("Path where to store files on disk"), :default => lambda{ Spider.paths[:data]+'/files' }
   config_option 'files.previews_path', _("Path where to store previews"), :default => lambda{ Spider.paths[:data]+'/file_previews' }
    
end
