# -*- encoding : utf-8 -*-
require 'digest/sha1'
require 'apps/files/models/tag'
require 'fileutils'

module Spider; module Files
    
    class BaseFile < Spider::Model::Managed
        element :sha1, String, :index => true, :hidden => true, :default => lambda{ |obj| 
            return nil if obj.void?
            return obj.calculate_sha1
        }
        element :uuid, Spider::DataTypes::UUID
        element :name, String, :label => _('Name')
        element :title, String, :label => _('Title')
        multiple_choice :tags, Spider::Files::Tag, :label => _('Tags')
        element :tag_list, String, :computed_from => [:tags], :sortable => true, :label => _('Tags'), :hidden => true
        
        def void?
            true
        end

        def exists?
            true 
        end
        
        def size
            raise "Unimplemented"
        end
        
        def mime_type
            self.name ? MIME::Types.type_for(self.name) : ''
        end
        
        def tag_list
            self.tags.map{ |t| t.name }.join(', ')
        end
        
        def self.prepare_query(query)
            query.order.each_index do |i|
                element_name, dir = query.order[i]
                if element_name == :tag_list
                    query.order.delete_at(i)
                    query.order.insert(i, ['tags.name', dir])
                end
            end
            super(query)
        end
        
        def self.prepare_condition(condition)
            condition.conditions_for(:tag_list).each do |c|
                new_cond = Spider::Model::Condition.or
                c_tag_list = c.delete(:tag_list)
                new_cond.set('tags.name', c_tag_list[1], c_tag_list[0])
                # new_cond.set(:tags, '=', nil)
                c << new_cond
            end
            condition
        end
        
        def self.new_from_path
            raise "Unimplemented"
        end
        
        def self.new_from_buffer
            raise "Unimplemented"
        end
        
        def self.path_sha1(path)
            digest = Digest::SHA1.new
            ::File.open(path, 'r') do |f|
                while (!f.eof)
                    buf = f.readpartial(1024)
    				digest.update(buf)
                end
            end
            return digest.hexdigest
        end
        
        def self.sha1(buf)
            digest = Digest::SHA1.new
            digest.update(buf)
            return digest.hexdigest
        end
        
        def calculate_sha1
            digest = Digest::SHA1.new
            self.file_open do |f|
                while (!f.eof)
                    buf = f.readpartial(1024)
    				digest.update(buf)
                end
            end
            return digest.hexdigest
        end
        
        def read
            raise "Unimplemented"
        end
        
        def path_or_tmp(&proc)
            if self.respond_to?(:path)
                if block_given?
                    yield path
                else
                    path
                end
            else
                tmp_file(&proc)
            end
        end
        
        def tmp_file(&proc)
            raise "Not yet implemented"
            # Should write the blob to a temporary file, and delete it after yielding
        end
        
        def url
            self.class.app.url+'/'+self.uuid
        end
        
        def url_with_name
            self.class.app.url+'/'+self.uuid+'/'+self.name
        end
        
        def cleanup
            prev_path = Spider.conf.get('files.previews_path')
            return unless ::File.directory?(prev_path)
            Dir.new(prev_path).each do |dir|
                next unless dir =~ /(\d+)x(\d+)/
                f = ::File.join(prev_path, dir, "#{self.sha1}.png")
                ::File.unlink(f) if ::File.exist?(f)
            end
        end
        
        def generate_preview(width, height)
            dim = "#{width}x#{height}"
            p_path = preview_path(width, height)
            begin
                FileUtils.mkdir_p(::File.join(Spider.conf.get('files.previews_path'), dim))
                require 'rmagick'
                image = nil
                size = nil
                image = Magick::Image.from_blob(self.read)
                thumb = image.first.change_geometry(dim) do |cols, rows, img|
                    img.resize!(cols, rows)
                end
                ::File.open(p_path, 'w') do |f|
                    f << thumb.to_blob{ |img| img.format = 'PNG' }
                end
            rescue => exc
                icon = Spider::Files.icon_for_filename(self.name)
                icon_path = ::File.join(Files.pub_path, "/img/crystal/mimetypes/128x128/#{icon}.png")
                begin
                    ::File.symlink(icon_path, p_path)
                rescue NotImplementedError
                    ::File.cp(icon_path, p_path)
                end
            end
            
        end
        
        def get_preview(width, height)
            p_path = preview_path(width, height)
            generate_preview(width, height) unless ::File.exist?(p_path)
            p_path
        end
        
        def preview_path(width, height)
            dim = "#{width}x#{height}"
            ::File.join(Spider.conf.get('files.previews_path'), dim, "#{self.sha1}.png")
        end
        
        
        
        
    end
    
    
end; end
