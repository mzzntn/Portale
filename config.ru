#ENV['LANG'] = 'it_IT.UTF-8'
$:.push('/usr/local/lib/spider/lib')
require 'spiderfw/init'
require 'spiderfw/http/adapters/rack'
if defined?(PhusionPassenger)
    PhusionPassenger.on_event(:starting_worker_process) do
        Spider.startup
    end
end

use Rack::Session::Cookie,  :expire_after => 2592000,
                            :secret => Spider.conf.get("rack_session_cookie.secret")


#configurazione per twitter
use OmniAuth::Builder do
  provider :twitter, Spider.conf.get("twitter.client"), Spider.conf.get("twitter.secret")
end
# fine configurazione per twitter


require 'rack/cors'
use Rack::Cors do
  allow do
    origins '*'
    resource '*',
        headers: :any,
        methods: [:get, :post, :put, :patch, :delete, :options, :head]
  end
end



rack_app = Spider::HTTP::RackApplication.new
app = proc do |env|
    rack_app.call(env)
end

run app


