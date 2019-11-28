# -*- encoding : utf-8 -*-
module Spider

    module Files
    
        @icons = {
            ['doc', 'rtf', 'docx'] => 'doc',
            ['pdf'] => 'pdf',
            ['iso'] => 'cdimage',
            ['gz', 'tgz'] => 'tgz',
            ['tar'] => 'tar',
            ['zip', 'rar', '7zip'] => 'zip',
            ['mov'] => 'quicktime',
            ['avi'] => 'video',
            ['log'] => 'log',
            ['mid', 'mp3', 'wav'] => 'midi',
            ['sh'] => 'shellscript',
            ['cvs', 'xls'] => 'spreadsheet',
            ['txt'] => 'txt',
            ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif', 'psd'] => 'images',
        }
        @source_icons = ['c', 'cpp', 'f', 'h', 'j', 'java', 'l', 'moc', 'o', 'p', 'php', 'pl', 'py', 's', 'y']
        @source_other = ['rb', 'css']
    
        def self.icon_for_filename(name)
            ext = ::File.extname(name)[1..-1]
            @icons.each do |arr, icon|
                return icon if arr.include?(ext)
            end
            return "source_#{ext}" if @source_icons.include?(ext)
            return "source" if @source_other.include?(ext)
            return 'misc'
        end
    
    end
    
end
