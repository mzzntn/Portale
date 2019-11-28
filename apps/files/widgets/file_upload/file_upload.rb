# -*- encoding : utf-8 -*-
require 'json'
require "uuidtools"
require 'uri'


module Spider; module Files
    
    class FileUpload < Spider::Widget
        tag 'upload'
        
        attribute :"delete-uploaded", :type => Spider::DataTypes::Bool, :default => true
        
        attr_reader :uploaded_file
        
        def prepare
            super
            @uploaded_file = session[:uploaded_file]
        end
        
        def run
            if session[:uploaded_file] && @attributes[:"delete-uploaded"]
                path = session[:uploaded_file][:path]
                begin
                    ::File.unlink(path)
                rescue Errno::ENOENT
                end
                session.delete(:uploaded_file)
                @uploaded_file = nil
            end
            super
        end
        
        __.text
        def upload
            file_path = Spider.paths[:tmp]+"/file_upload_#{UUIDTools::UUID.random_create.to_s}"
            name = ( @request.params['name'].blank? ? nil : URI.encode(@request.params['name']) )
            f = ::File.new(file_path, 'wb')
            @request.body do |buf|
                f.write buf
            end
            f.close
            session[:uploaded_file] = {
                :name => name,
                :content_type => @request.env['HTTP_CONTENT_TYPE'],
                :path => file_path
            }
            $out << name
        end
        
        def get_uploaded
            uploaded_file = session[:uploaded_file]
            session.delete(:uploaded_file)
            uploaded_file
        end
        
    end
    
end; end
