jQuery(document).ready(function(){

  jQuery(window).bind("media.wildfireflickrfile.preview", function(e, row, preview_container){

    var str = "";

    row.find("td").each(function(){
      var html = jQuery(this).html();

      if(html.indexOf("<object") >= 0){
        var h = parseInt(jQuery(html).find("object").attr("height")),
            w = parseInt(jQuery(html).find("object").attr("width")),
            r = 200/w
            ;
        if(h && w) str += html.replace(h, Math.round(h*r)).replace(h, Math.round(h*r)).replace('"'+w+'"',200).replace('"'+w+'"',200);
      }else if(html.indexOf("<img")) str += html.replace("_s.", "_m.").replace('width="40"', "");
      else str += html;
    });
    preview_container.html(str);

  });

});