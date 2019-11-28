# -*- encoding : utf-8 -*-
require 'apps/files/models/base_file'
require 'mime/types'

module Spider; module Files
    
    class DiskFile < Spider::Files::BaseFile
        class_table_inheritance
        
        def file_open(mode='r')
            if block_given?
                open(self.path, mode) do |f|
                    yield f
                end
            else
                open(self.path, mode)
            end
        end
        
        def path
            return nil unless self.sha1
            @path ||= self.class.base_path+'/'+self.sha1
        end
        
        def pathname
            @pathname ||= Pathname.new(self.path)
        end
        
        def void?
            self.sha1.nil?
        end

        def exists?
            return false unless self.path
            ::File.exists?(self.path)
        end
        
        def size
            begin
                self.pathname.size
            rescue Errno::ENOENT
                nil
            end
        end
        
        def read
            ::File.read(self.path)
        end
                
        
        def self.new_from_path(path)
            hash = self.path_sha1(path)
            dest = ::File.join(self.base_path, hash)
            ::FileUtils.copy(path, dest)
            self.static(:sha1 => hash)
        end
        
        def self.new_from_buffer(buf)
            hash = self.sha1(buf)
            dest = ::File.join(self.base_path, hash)
            ::File.open(dest, 'w') do |f|
                #conversione per problemi in salvataggio
                f.write(buf.respond_to?(:force_encoding) ? buf.force_encoding('UTF-8') : buf)
            end
            self.static(:sha1 => hash)
        end
        
        
        def self.base_path
            Spider.conf.get('files.disk.path')
        end
        
        def cleanup
            super
            begin
                others = self.class.where{ |f| (f.sha1 == self.sha1) & (f.id .not self.id ) }.limit(1)
                ::File.unlink(self.path) unless others[0]
            rescue Errno::ENOENT
            end
        end
        
        with_mapper do
            def before_delete(objects)
                objects.each do |obj|
                    obj.cleanup
                end
                super
            end
        end
    

        
        
    end
    
end; end
