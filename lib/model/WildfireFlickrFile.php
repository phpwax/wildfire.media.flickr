<?
class WildfireFlickrFile{

  public static $hash_length = 6;
  public static $name = "Flickr";
  public $api_base = "http://www.flickr.com/services/rest/";
  public $lookups = array('galleries'=>"flickr.galleries.getList",
                          "photosets"=>"flickr.photosets.getList");

  public $singular = array('galleries'=>'gallery',
                            'photosets'=>'photoset');
  public $fetch = array('galleries'=>"flickr.galleries.getPhotos",
                        "photosets"=>"flickr.photosets.getPhotos");
  /**
   * no uploads to flickr yet
   **/
  public function set($media_item){
    return false;
  }
  //should return a url to display the image
  public function get($media_item, $size=false){
    return "";
  }

  //this will actually render the contents of the image
  public function show($media_item, $size=false){
    return "";
  }
  //generates the tag to be displayed - return generic icon if not an image
  public function render($media_item, $size, $title="preview"){
    return "";
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
    $url = $this->api_base . "?".$type."=".$id."&method=".$service."&nojsoncallback=1&format=json&api_key=".Config::get("flickr/key")."&per_page=500&user_id=".Config::get("flickr/user_id");
    $curl = new WaxBackgroundCurl(array('url'=>$url));
    $data = json_decode($curl->fetch());
    if($data->total){
      foreach($data->photo as $pic){

      }
    }
    return $info;
  }


}
?>