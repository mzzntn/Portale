# -*- encoding : utf-8 -*-
module Spider

    module Files
        include Spider::App
        @controller = :FilesController
    

    end
    
end

Spider::Template.register_namespace('files', Spider::Files)

require 'apps/files/controllers/files_controller'
require 'apps/files/lib/file_model_widget'
require 'apps/files/widgets/file_upload/file_upload'
require 'apps/files/widgets/file_viewer/file_viewer'
require 'apps/files/widgets/file_browser/file_browser'
require 'apps/files/widgets/file_archive/file_archive'
require 'apps/files/widgets/file_manager/file_manager'
require 'apps/files/widgets/file_form/file_form'

case Spider.conf.get('files.storage')
when 'disk'
    require 'apps/files/models/disk_file'
    Spider::Files.const_set(:File, Spider::Files::DiskFile)
    require 'fileutils'
    FileUtils.mkdir_p(Spider.conf.get('files.disk.path'))
end

Spider::Template.define_named_asset 'plupload', [
     [:js, 'plupload/src/javascript/plupload.js', Spider::Files],
     [:js, 'plupload/src/javascript/plupload.gears.js', Spider::Files],
     [:js, 'plupload/src/javascript/plupload.silverlight.js', Spider::Files],
     [:js, 'plupload/src/javascript/plupload.flash.js', Spider::Files],
     [:js, 'plupload/src/javascript/plupload.browserplus.js', Spider::Files],
     [:js, 'plupload/src/javascript/plupload.html5.js', Spider::Files]
]

Spider::Template.define_named_asset 'plupload-queue', [
    [:js, 'plupload/src/javascript/jquery.plupload.queue/jquery.plupload.queue.js', Spider::Files]
], :depends => ['jquery', 'plupload']
 
