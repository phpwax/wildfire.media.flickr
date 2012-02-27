<?
CMSApplication::register_module("media.flickr", array("hidden"=>true, "plugin_name"=>"wildfire.media.flickr", 'assets_for_cms'=>true));
WildfireMedia::$classes[] = 'WildfireFlickrFile';
?>