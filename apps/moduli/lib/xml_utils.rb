# -*- encoding : utf-8 -*-
require 'nokogiri'
require 'json'

module Moduli

class Nokogiri::XML::Node
  def namespaced_name
    "#{namespace && "#{namespace.prefix}:"}#{name}"
  end
end
class Nokogiri::XML::Element
  def to_json(*a)
    [namespaced_name].tap do |parts|
      unless attributes.empty?
        parts << Hash[ attribute_nodes.map{ |a| { a.namespaced_name => a.value} } ]
      end
      parts.concat(children.select{|n| n.text? ? (n.text=~/\S/) : n.element? })
    end.to_json(*a)
  end
end
class Nokogiri::XML::Document
  def to_json(*a); root.to_json(*a); end
end
class Nokogiri::XML::Text
  def to_json(*a); text.to_json(*a); end
end
class Nokogiri::XML::Attr
  def to_json(*a); value.to_json(*a); end
end

end
