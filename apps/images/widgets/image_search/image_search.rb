# -*- encoding : utf-8 -*-
require 'flickraw-cached'
require 'json'
require 'net/http'
require 'uri'
require 'cgi'

module Spider; module Images
    
    
    class ImageSearch < Spider::Widget
        tag 'search'
        
        attribute :"max-images", :type => Integer, :default => 8
        
        def prepare
            
            FlickRaw.api_key = "59b664a9e060e9d7285b591e944c14ea"
            #inserito shared_secret vuoto per errore della gemma flickraw su server spider
            FlickRaw.shared_secret = ""
            @images = []
            @scene.images = @images
            super
        end
        
        def run
            @free = params.key?('free') ? params['free'] : true
            @search_q = params['search_q'] || session[:search_q]
            if params['search_q'] || params['page']
                session[:search_q] = @search_q
            else
                session.delete(:search_q)
            end
            search if @search_q
            #list = flickr.interestingness.getList(:per_page => 10, :page => 1)
                
            @scene << {
                :search_q => @search_q,
                :images => @images,
                :free => @free,
                :page => @page,
                :max_images => attributes[:"max-images"]
            }
            super
        end
        
        __.json :scene => [:images]
        def search
            @page = params['page'] ? params['page'].to_i : 1
            @scene.engine = params['engine'] || session['engine']
            session['engine'] = @scene.engine
            @scene.engine = 'flickr'
            if @scene.engine == 'panoramio'
                search_panoramio
            else
                search_flickr
            end
        end
        
        def search_flickr
            search_params = {
                :extras => 'o_dims,url_t,url_o,url_b',
                :text =>@search_q, 
                :page => @page, 
                :per_page => attributes[:"max-images"]
            }
            search_params[:license] = '1,2,3,4,5,6,7,8' if @free
            
            list = flickr.photos.search(search_params)

            list.each do |details|
                image = {
                    :id => "flickr-#{details["id"]}",
                    :title => details["title"],
                    :name => "flickr_#{details["id"]}.jpg",
                    :thumb => details['url_t'],
                    :url => details['url_o'],
                    :dimensions => details['o_dims'],
                    :provider => 'flickr'
                }
                #info = flickr.photos.getInfo(:photo_id => details["id"], :secret => details["secret"])
                #image[:thumb] = FlickRaw.url_t(info)
                #image[:url] = FlickRaw.url_b(info)
                @images << image
            end
            @scene.total_pages = (list.total.to_f / attributes[:"max-images"]).ceil
        end
        
        def search_panoramio
            geocode_url = "http://maps.googleapis.com/maps/api/geocode/json?address=#{CGI.escape(@search_q)}&sensor=false"
            url = URI.parse(geocode_url)
            res = Net::HTTP.get_response(url)
    
            geocode = JSON.parse(res.body)
            geo = geocode["results"][0]["geometry"]
            lat_sw = geo["viewport"]["southwest"]["lat"].to_f
            lng_sw = geo["viewport"]["southwest"]["lng"].to_f
            lat_ne = geo["viewport"]["northeast"]["lat"].to_f
            lng_ne = geo["viewport"]["northeast"]["lng"].to_f
            
            lat_min = [lat_sw, lat_ne].min; lng_min = [lng_sw, lng_ne].min
            lat_max = [lat_sw, lat_ne].max; lng_max = [lng_sw, lng_ne].max
            
            
            
            base_url = 'http://www.panoramio.com/map/get_panoramas.php?'
            params = {}
            params[:set] = @free ? 'public' : 'full'
            params[:from] = (@page - 1) * attributes[:"max-images"]
            params[:to] = params[:from] + attributes[:"max-images"]
            params[:size] = 'original'
            params[:minx] = lng_min; params[:miny] = lat_min
            params[:maxx] = lng_max; params[:maxy] = lat_max
            
            url = base_url+params.map{ |k, v| "#{k}=#{v}"}.join('&')

            url = URI.parse(url)
            res = Net::HTTP.get_response(url)
            list = JSON.parse(res.body)

            list["photos"].each do |details|
                image = {
                    :id => "panoramio-#{details["photo_id"]}",
                    :title => details["photo_title"],
                    :name => "panoramio_#{details["photo_id"]}.jpg",
                    :thumb => "http://mw2.google.com/mw-panoramio/photos/thumbnail/#{details['photo_id']}.jpg",
                    :url => details["photo_url"],
                    :dimensions => "#{details["width"]}x#{details["height"]}",
                    :provider => 'panoramio'
                }
                @images << image
            end
            @scene.total_pages = (list["count"].to_f / attributes[:"max-images"]).ceil
        end
        
        def clicked?
            @widgets[:search_browser] && @widgets[:search_browser].clicked?
        end
        
        def get_clicked
            return nil unless @widgets[:search_browser]
            photo_id = @widgets[:search_browser].clicked
            provider, id = photo_id.split('-', 2)
            if provider == 'flickr'
                info = flickr.photos.getInfo(:photo_id => id)
                return {
                    :id => id,
                    :title => info["title"].gsub("\"", '').strip,
                    :name => "flickr_#{id}.jpg",
                    :url => FlickRaw.url_b(info),
                    :thumb => FlickRaw.url_t(info)
                    #:tags => info["tags"].map{ |tag| tag["raw"] }.uniq.join(' ')
                }
            elsif provider == 'panoramio'
                
                return {
                    :id => id,
                    :title => ''
                }
            end
        end
        
    end
    
    
end; end
