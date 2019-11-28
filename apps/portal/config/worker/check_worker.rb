require 'net/http'
require 'uri'

if Spider.config.get('portal.check_worker.attiva') == true then
	Spider::Worker.cron(Spider.config.get('portal.check_worker.cron')) do
		url = Spider.config.get('portal.check_worker.dest_url')
		storage_url = Spider.config.get('storages.default.url')
		arr = Spider::Model::Storage::Db::Mysql.parse_url(storage_url)
		site = Spider.config.get('portal.url_sito') 
		uri = URI.parse("#{url}/?db=#{arr[3]}&site=#{site}")
		response = Net::HTTP.get(uri)
	end
end