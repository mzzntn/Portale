# -*- encoding : utf-8 -*-
require 'cmdparse'

module Spider; module Images

    class Cmd < ::CmdParse::Command

        def initialize
            super('spider_images', true)

            cleanup = CmdParse::Command.new('cleanup', false)
            cleanup.short_desc = _('Delete missing images from the db')
            cleanup.set_execution_block do |args|
                Spider::Images::Image.all.each do |img|
                   img.delete unless img.exists? 
                end
            end
            self.add_command(cleanup)
        end

    end


end; end
