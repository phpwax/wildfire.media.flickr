
<?
class WildfireFlickrFile{

  public static $hash_length = 6;
  public static $name = "Flickr";
  public $api_base = "http://www.flickr.com/services/rest/";
  public $lookups = array('galleries'=>"flickr.galleries.getList",
                          "photosets"=>"flickr.photosets.getList"
                          );

  public $singular = array( 'galleries'=>'gallery',
                            'photosets'=>'photoset',
                            'sizes'=>'size');
  public $fetch = array('galleries'=>"flickr.galleries.getPhotos",
                        "photosets"=>"flickr.photosets.getPhotos",
                        "photo"=>"flickr.photos.getInfo",
                        'sizes'=>'flickr.photos.getSizes'
                        );
  /**
   * no uploads to flickr ... yet
   **/
  public function set($media_item){
    return false;
  }
  //should return a url to display the image
  public function get($media_item, $width=false, $return_obj = false){
    //fetch the size details for the item
    $url = $this->api_base . "?photo_id=".$media_item->source."&method=".$this->fetch['sizes']."&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");
    $curl = new WaxBackgroundCurl(array('url'=>$url));
    $obj = json_decode($curl->fetch());

    if($media_item->file_type == "video"){
      $compare = array('param'=>"label", 'value'=>'Video Player');
      $var = "source";
    }
    else{
      $compare = array('param'=>'media', 'value'=>"photo");
      $var = "source";
    }

    if($sizes = $obj->sizes->size){
      $index = $difference = false;
      $diff = 99999;
      if($width == false) $width = 9999;
      foreach($sizes as $i => $s){
        $diff = abs($width - $s->width);
        if((!$difference || ($diff < $difference)) && $s->{$compare['param']} == $compare['value']){
          $difference = $diff;
          $index = $i;
        }
      }
      //depending on the param, either return the array of just the source
      if($index !== false && $return_obj) return $sizes[$index];
      elseif($index !== false) return $sizes[$index]->$var;
    }
    return "";
  }

  //this will actually render the contents of the image
  public function show($media_item, $size=false){
    $data = $this->get($media_item, $size, true);
    header("Location: ".$data['source']);

  }
  //generates the tag to be displayed - return generic icon if not an image
  public function render($media_item, $size, $title="preview"){
    $url = $this->get($media_item, $size);
    if($media_item->file_type == "video"){
      //need to refetch the data to find the original size
      $data = $this->get($media_item, $size, true);
      //flickrs embedd code template
      $template = '<object type="application/x-shockwave-flash" width="-WIDTH-" height="-HEIGHT-" data="http://www.flickr.com/apps/video/stewart.swf?v=109786" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"><param name="flashvars" value="intl_lang=en-us&photo_secret=-SECRET-&photo_id=-PHOTO_ID-" ></param><param name="movie" value="http://www.flickr.com/apps/video/stewart.swf?v=109786"></param><param name="bgcolor" value="#000000"></param><param name="allowFullScreen" value="true"></param><embed type="application/x-shockwave-flash" src="http://www.flickr.com/apps/video/stewart.swf?v=109786" bgcolor="#000000" allowfullscreen="true" flashvars="intl_lang=en-us&photo_secret=-SECRET-&photo_id=-PHOTO_ID-" height="-HEIGHT-" width="-WIDTH-"></embed></object>';
      //work out the new height
      $h = ($data->height / ($data->width/$size));
      return str_replace("-PHOTO_ID-", $media_item->source, str_replace("-SECRET-", $media_item->hash, str_replace("-WIDTH-", $size, str_replace("-HEIGHT-", $h, $template))));
    }else{
      return "<img src='".$url."' alt='".$title."' class='flickr' width='".$size."'>";
    }

  }

  /**
   * have to find all photosets & galleries
   */
  public function sync_locations(){

    foreach($this->lookups as $key=>$service){
      $url = $this->api_base . "?method=".$service."&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");
      $curl = new WaxBackgroundCurl(array('url'=>$url));
      $obj = json_decode($curl->fetch());
      if($obj->$key->total > 0){
        $group = $this->singular[$key];
        foreach($obj->$key->$group as $set) $info[$set->id] = array('value'=>$key."@".$set->id, 'label'=>$set->title->_content);
      }
    }
    return $info;
  }

  public function sync($location){
    $info = array();

    list($service_key, $id) = explode("@", $location);
    $service = $this->fetch[$service_key];
    $single = $this->singular[$service_key];
    $type = $single."_id";

    //find the photoset name
    $method = "flickr.photosets.getInfo";
    $set_url = $this->api_base . "?".$type."=".$id."&method=".$method."&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");
    $curl = new WaxBackgroundCurl(array('url'=>$set_url));
    $set_data = json_decode($curl->fetch());
    $cat = new WildfireCategory;
    //find the category based on the photoset
    if(($name = $set_data->photoset->title->_content) && (!$found_cat = $cat->filter("title", $name)->first())) $found_cat = $cat->update_attributes(array('title'=>$name));


    $url = $this->api_base . "?".$type."=".$id."&method=".$service."&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");

    $curl = new WaxBackgroundCurl(array('url'=>$url));
    $data = json_decode($curl->fetch());

    $class = get_class($this);
    if($data->$single->total){
      $ids = array();
      foreach($data->$single->photo as $pic){
        $source = $pic->id;
        $model = new WildfireMedia;
        $info_url = $this->api_base."?method=".$this->fetch['photo']."&photo_id=$source&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");
        $ncurl = new WaxBackgroundCurl(array('url'=>$info_url));
        if($pic_info = json_decode($ncurl->fetch())){

          if($found = $model->filter("media_class", $class)->filter("source", $pic->id)->first()) $found->update_attributes(array('status'=>1));
          else $found = $model->update_attributes(array('source'=>$source,
                                                    'uploaded_location'=>str_replace(Config::get("flickr/user_id"), "@USER@", str_replace(Config::get("flickr/key"), "@KEY@", $info_url)),
                                                    'status'=>1,
                                                    'media_class'=>$class,
                                                    'media_type'=>self::$name,
                                                    'ext'=>$pic_info->photo->originalformat,
                                                    'file_type'=>($pic_info->photo->media == "photo") ? "image/".$pic_info->photo->originalformat : "video",
                                                    'title'=>$pic->title,
                                                    'hash'=> $pic->secret,
                                                    'sync_location'=>$location
                                                    ));

          $ids[] = $found->primval;
          $info[] = $found;
          //join
          $found->categories = $found_cat;
          //tags -> categories
          foreach((array) $pic_info->photo->tags->tag as $tag){
            $model = new WildfireCategory;
            if(($tag = trim($tag->_content)) && $tag){
              if($cat = $model->filter("title", $tag)->first()) $found->categories = $cat;
              else $found->categories = $model->update_attributes(array('title'=>$tag));
            }
          }
        }
      }

      $media = new WildfireMedia;
      foreach($ids as $id) $media->filter("id", $id, "!=");
      foreach($media->filter("status", 1)->filter("media_class", $class)->filter("sync_location", $location)->all() as $missing) $missing->update_attributes(array('status'=>-1));

    }
    return $info;
  }


}
?>