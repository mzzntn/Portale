# -*- encoding : utf-8 -*-
require 'mime/types'
require 'open-uri'
require 'tempfile'
require 'uri'

module Spider; module Files
   
   class FileViewer < Spider::Widget
       include FileModelWidget
       
       tag 'viewer'
       
       attr_accessor :file
       attr_accessor :tmp_file
       attr_reader :saved
       
       attribute :"no-buttons"
       attribute :"require-title", :type => Spider::DataTypes::Bool, :default => true
       attribute :"url-with-name", :type => Spider::DataTypes::Bool, :default => false
       
       
       
       PREVIEW_WIDTH  = 128
       PREVIEW_HEIGHT = 128
       
       
       def prepare
           super
       end
       
       
       def run
           @scene.no_buttons = attributes[:"no-buttons"]
           @scene.require_title = attributes[:"require-title"]
           
           set_file_obj
           return super unless @file || @tmp_file
           save if params['save']
           name = nil
           if @tmp_file
               name = @tmp_file[:name]
               size = 0
               tags = @tmp_file[:tags]
               title = @tmp_file[:title]
               if @tmp_file[:path]
                   pathname = Pathname.new(@tmp_file[:path])
                   size = pathname.size
               elsif @tmp_file[:url]
                   require 'net/http'
                   url_scheme, url_user_info, url_host, url_port, url_registry, url_path = URI::split(@tmp_file[:url])
                   Net::HTTP.start(url_host, url_port) do |http|
                       response = http.head(url_path)
                       size = response["Content-Length"].to_i
                   end
               end
               @scene << {
                   :name => name,
                   :mime_type => MIME::Types.type_for(name).first,
                   :size => readable_file_size(size),
                   :tags => tags,
                   :title => title
               }
           else
               name = @file.name
               mime_type = @file.mime_type
               mime_type = mime_type.first if mime_type.is_a?(Array)
               @scene << {
                   :name => name,
                   :mime_type => mime_type,
                   :size => readable_file_size(@file.size),
                   :title => @file.title,
                   :tags => @file.tags.map{ |t| t.name }.join(' '),
                   :uuid => @file.uuid
               }
               
           end          
           session[:file] = @file ? @file.uuid : nil
           session[:tmp_file] = @tmp_file
           if saved?
               @css_classes << 'saved'
           end
           super
       end
       
       __.json( {:scene => [:saved]})
       def save
           set_file_obj
           if @tmp_file
               path = @tmp_file[:path]
               if !path && @tmp_file[:url]
                   tmp = ::Tempfile.new('file_viewer', Spider.paths[:tmp])
                   open(@tmp_file[:url]) do |f|
                       while buf = f.read(1024)
                           tmp << buf
                       end
                   end
                   path = tmp.path
               end
               file = self.file_model.new_from_path(path)
               ::File.unlink(path)
               file.insert
               @file = file
               @tmp_file = nil
           else
               file = @file
           end
           raise "Title is required" if attributes[:"require-title"] && params.key?('title') && (!params['title'] || params['title'].empty?)
           file.name = params['name'] if params.key?('name')
           file.title = params['title'] if params.key?('title')
           if params.key?('tags')
               params['tags'].split(/\s+/).each do |t|
                   tag = self.tag_model.load(:name => t)
                   unless tag
                       tag = self.tag_model.new(:name => t)
                       tag.save
                   end
                   file.tags << tag 
               end
           end
           file.save
           @saved = file
           session[:file] = file.uuid if file
           session.delete(:tmp_file) unless @tmp_file
               
           @scene.saved = {
               :params => params,
               :uuid => file.uuid,
               :url => attributes[:"url-with-name"] ? file.url_with_name : file.url
           }
       end
       
       __.action
       def get_file
           set_file_obj
           raise NotFound.new("file") unless @file || @tmp_file
           if @tmp_file && @tmp_file[:url]
               redirect @tmp_file[:url]
           else
               redirect_to_file @file if @file
               name = @file ? @file.name : @tmp_file[:name]
               @response.headers['Content-Description'] = 'File Transfer'
               @response.headers['Content-Disposition'] = "attachment; filename=\"#{name}\""
               output_static(@tmp_file[:path])
           end
       end
       
       def redirect_to_file(file)
           redirect @file.url
       end
       
       __.action
       def preview
           set_file_obj
           return redirect(@tmp_file[:thumb]) if @tmp_file && @tmp_file[:thumb]
           preview = nil
           if @file
               preview = @file.get_preview(PREVIEW_WIDTH, PREVIEW_HEIGHT)
           elsif @tmp_file
               begin
                   require 'rmagick'
                   size = nil
                   image = Magick::ImageList.new(@tmp_file[:path] || @tmp_file[:url])
                   thumb = image.first.change_geometry("#{PREVIEW_WIDTH}x#{PREVIEW_HEIGHT}") do |cols, rows, img|
                       img.resize!(cols, rows)
                   end
                   blob = thumb.to_blob{ |img| img.format = 'PNG' }
                   @response.headers['Content-Type'] = 'image/png'
                   @response.headers['Content-Length'] = blob.length
                   $out << blob
                   return
               rescue => exc
                   name = @tmp_file[:name]
                   icon = Spider::Files.icon_for_filename(name)
                   preview = Files.pub_path+"/img/crystal/mimetypes/128x128/#{icon}.png"
               end
           else
               preview = Spider::Files.pub_path+"/img/crystal/mimetypes/128x128/unknown.png"
           end
           output_static(preview)
       end
       
       def saved?
           @saved
       end
       
       private
       GIGA_SIZE = 1073741824.0
       MEGA_SIZE = 1048576.0
       KILO_SIZE = 1024.0

       # Return the file size with a readable style.
       def readable_file_size(size, precision=2)
           return "0" unless size
           case
           when size == 1 then "1 Byte"
           when size < KILO_SIZE then "%d Bytes" % size
           when size < MEGA_SIZE then "%.#{precision}f KB" % (size / KILO_SIZE)
           when size < GIGA_SIZE then "%.#{precision}f MB" % (size / MEGA_SIZE)
           else "%.#{precision}f GB" % (size / GIGA_SIZE)
           end
       end
       
       
       def set_file_obj
           return if @file || @tmp_file
           if session[:tmp_file]
               @tmp_file = session[:tmp_file]
           elsif session[:file]
               @file = self.file_model.load(:uuid => session[:file])
           end
       end
       

       
       
       
   end
    
end; end
