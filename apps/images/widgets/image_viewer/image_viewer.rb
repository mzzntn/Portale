# -*- encoding : utf-8 -*-
require 'rmagick'
require 'apps/files/widgets/file_viewer/file_viewer'


module Spider; module Images
    
    class ImageViewer < Files::FileViewer
        include Spider::Files::FileModelWidget
        
        tag 'viewer'
        
        attribute :"resize-saved"
        attribute :"allow-resize", :type => Spider::Bool
        attribute :"show-caption", :type => Spider::Bool
                
        def self.file_model
            Spider::Images::Image
        end
        
        def self.tag_model
            Spider::Images::Tag
        end
        
        def prepare
            super            
        end
        
        def run
            @scene.allow_resize = attributes[:"allow-resize"]
            @scene.show_caption = attributes[:"show-caption"]
            super
            if @tmp_file
                begin
                    magick ||= Magick::Image.read(@tmp_file[:path]).first
                    width = magick.columns
                    height = magick.rows
                    @scene.dimensions = [magick.columns, magick.rows]
                    if attributes[:"resize-saved"]
                        dim = get_resize_saved_dim
                        res_width = width; res_height = height
                        if width > dim || height > dim
                            res_width = nil; res_height = nil
                            res = magick.change_geometry("#{dim}x#{dim}") do |cols, rows, img|
                                res_width = cols; res_height = rows
                            end
                        end
                        @scene.resize_dimensions = [res_width, res_height]
                    end
                rescue => exc
                    Spider.logger.error(exc)
                end
            elsif @file
                @scene.dimensions = [@file.width, @file.height]
                if @file.original?
                    if attributes[:"resize-saved"]
                        magick = Magick::Image.from_blob(@file.read).first
                        width = magick.columns
                        height = magick.rows
                        dim = get_resize_saved_dim

                        res_width = width; res_height = height
                        if width > dim || height > dim
                            res = magick.change_geometry("#{dim}x#{dim}") do |cols, rows, img|
                                res_width = cols; res_height = rows
                            end
                        end
                        @scene.resize_dimensions = [res_width, res_height]
                    end
                else
                    @scene.original = {
                        :dimensions => [@file.original.width, @file.original.height]
                    }
                    @scene.copy = true
                end
                @scene.caption = @file.caption if attributes[:"show-caption"]
            end
            @scene.dimensions ||= []
            @scene.resize_dimensions ||= @scene.dimensions
        end
        
        __.json( {:scene => [:saved]})
        def save
            super
            p_w = params['width'].to_i; p_h = params['height'].to_i
            if @tmp_file && ( (attributes[:"allow-resize"] && p_w != 0 && p_h != 0) || attributes[:"resize-saved"])
                if (p_w == 0 || p_h == 0) && attributes[:"resize-saved"]
                    dim = get_resize_saved_dim
                    p_w = dim; p_h = dim
                end
                if p_w != @saved.width || p_h != @saved.height
                    options = {:keep_ratio => !params['keep_ratio'].blank?}
                    @saved = @saved.create_resized_copy(p_w, p_h, nil, options)
                    @saved.save
                end
            elsif @file && p_w != 0 && p_h != 0 && (p_w != @file.width || p_h != @file.height)
                options = {:keep_ratio => !params['keep_ratio'].blank?}
                copy = @file.create_resized_copy(p_w, p_h, nil, options)
                @file.delete unless @file.original?
                copy.save
                @saved = copy
            end
            @saved.caption = params['caption'] if attributes[:"show-caption"]
            @saved.save
            @scene.saved.merge!({
                :uuid => @saved.uuid,
                :url => @saved.url,
                :thumb => @saved.url_t
            })
            
        end
        
        __.action
        def preview
            set_file_obj
            if !@tmp_file && @file
                thumb = @file.copy('thumb')
                return redirect(thumb.url) if thumb
            end
            super
        end
        
        __.action
        def get_file
            super
        end
               
               
        
        private
        
        def get_resize_saved_dim
            dim = attributes[:"resize-saved"].to_i
            if dim == 0 && Spider::Images.const_defined?(attributes[:"resize-saved"].upcase.to_sym)
                dim = Spider::Images.const_get(attributes[:"resize-saved"].upcase.to_sym)
            end
            raise "resize-saved attribute must be a Integer or a constant" if dim == 0
            dim
        end
        
        def redirect_to_file(file)
            redirect Spider::Images.url+'/'+@file.uuid
        end
        
    end
    
end; end
