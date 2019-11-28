# -*- encoding : utf-8 -*-
require 'rmagick'
require 'apps/images/models/tag'
require 'apps/files/models/license'

module Spider; module Images
    
    MICRO = 64
    THUMB = 128
    SMALL = 256
    MEDIUM = 512
    BIG = 1024
    SCREEN_W = 1280
    SCREEN_H = 1024
    
    @default_sizes = [
        ['thumb', THUMB, THUMB],
        ['micro', MICRO, MICRO, ],
        ['screen', SCREEN_W, SCREEN_H]
    ]
    
    @sizes = {
        'thumb' => [THUMB, THUMB],
        'micro' => [MICRO, MICRO, {:crop => true, :keep_ratio => true}],
        'screen' => [SCREEN_W, SCREEN_H]
    }
    
    @default_sizes = ['thumb', 'micro', 'screen']
    
    def self.sizes
        @sizes
    end
    
    def self.default_sizes
        @default_sizes
    end
    
    def self.set_size(name, w, h, params)
        @sizes[name] = [w, h, params]
    end
    
    class Image < Spider::Files::File
        label _('Image'), _('Images')
        
        class_table_inheritance
        
        remove_element :tags
        multiple_choice :tags, Spider::Images::Tag, :label => _('Tags')
        element :license, Spider::Files::License, :label => _('License')
        many :copies, Spider::Images::Image, :label => _('Copies'), :add_reverse => :original do
            element :label, String, :label => _('Label')
        end
        element :width, Integer, :label => _('Width'), :hidden => true
        element :height, Integer, :label => _('Height'), :hidden => true
        element :caption, Text
        
        def self.list
            self.where{ |img| img.original == nil }
        end
        
        def url
            "#{Spider::Images.url}/#{self.uuid}"
        end
        
        def copy?
            !original?
        end
        
        def original?
            self.original.nil?
        end
        
        
        def tags
            self.original? ? super : self.original.tags
        end
        
        def tag_list
            self.tags.map{ |t| t.name }.join(', ')
        end
        
        def tags=(val)
            raise "Can't set tags on an image copy" unless original?
            super
        end
        
        def license
            self.original? ? super : self.original.license
        end
        
        def license=(val)
            raise "Can't set license on an image copy" unless original?
            super
        end
        
        def copy(label)
            return self.original.copy(label) unless original?
            self.copies.each do |c|
                return c.spider_images_image if c.label == label.to_s
            end
            return nil
        end
        
        def url_resized(dim)
            #aggiunto return nel caso che non ci siano le immagini nella cartella data/files
            return "" unless self.exists?
            w = h = nil
            s = Spider::Images.sizes[dim]
            if s
                w, h, options = s
            else
                if dim =~ /(\d+)x(\d+)(?:\:(.+))?/
                    w = $1
                    h = $2
                    if $3
                        options = {}
                        $3.split(':').each{ |opt| options[opt.to_sym] = true}
                    end
                else
                    raise "Spider::Images '#{dim}' size is not defined" 
                end
            end
            res = copy(dim)
            res ||= create_resized_copy(w, h, dim, options)
            res.url
        end
        
        def url_t
            url_resized('thumb')
        end
        
        def url_screen
            url_resized('screen')
        end
        
        def url_micro
            url_resized('micro')
        end
        
        def create_resized_copy(width, height, label=nil, options=nil)
            options ||= {}
            options = {
                :keep_ratio => true
            }.merge(options)
            return self.original.create_resized_copy(width, height, label) unless self.original?
            @magick ||= Magick::Image.from_blob(self.read).first
            res = nil
            if options[:keep_ratio]
                if options[:crop]
                    res = @magick.crop_resized(width, height)
                else
                    @magick.change_geometry("#{width}x#{height}") do |cols, rows, img|
                        width = cols; height = rows
                    end
                    res = @magick.scale(width, height)
                end
            else
                res = @magick.scale(width, height)
            end
            image = self.class.new_from_buffer(res.to_blob{self.quality=60})
            label ||= "#{width}x#{height}"
            name = self.name
            if name
                extname = ::File.extname(name)
                basename = ::File.basename(name, extname)
                name = "#{basename}_#{label}#{extname}"
            end
            image.name = name
            image.title = self.title
            image.caption = self.caption
            image.width = width
            image.height = height
            copy = Spider::Images::Image::Copies.new(:image => self, :spider_images_image => image, :label => label)
            image.original = self
            self.copies << copy
            copy.save
            image
        end
        
        def set_dimensions
            @magick ||= Magick::Image.from_blob(self.read).first
            self.width = @magick.columns
            self.height = @magick.rows
        end
        
        with_mapper do
            def before_save(obj, mode)
                obj.set_dimensions unless obj.width && obj.height
                if (mode == :insert && obj.original?)
                    Spider::Images.default_sizes.each do |size|
                        s = Spider::Images.sizes[size]
                        obj.create_resized_copy(s[0], s[1], size, s[2])
                    end
                end
                return super
            end
            
            def before_delete(objects)
                objects.each do |obj|
                    next unless obj.original?
                    obj.copies.each do |copy|
                        copy.delete
                    end
                end
                super
            end
            
        end
        
        
    end
    
    
end; end
