# -*- encoding : utf-8 -*-
$SPIDER_RUNMODE = 'test'
require 'spiderfw/test/cucumber'
require 'spiderfw/test/capybara'
Spider.init
Spider.conf.set('portal.email_amministratore', 'admin@test.com')

Before do
    Spider::Auth::SuperUser.create(:username => 'admin', :password => 'admin')
end
