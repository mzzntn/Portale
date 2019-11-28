# -*- encoding : utf-8 -*-
module Portal

    module Hippo

        module ModelliBeneficio

            def self.included(mod)
                mod.mapper_include(Mapper)
            end

            module Mapper
                def storage_value_to_mapper(type, value)
                     return nil if value.blank?
                     case type.name
                     when 'Date', 'DateTime'
                        return super(type, value[0..7]+'T'+value[8..-1])
                     end
                     return super
                 end

            end

        end

    end

end