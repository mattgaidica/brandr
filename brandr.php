<?php
class Brandr {
  function __construct() {
    $this->root_path = dirname (__FILE__) . '/';
  }

  function get_image($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_NOBODY, true); // make it a HEAD request
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
    $head = curl_exec($ch);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    $valid_types = array(
      'image/jpeg',
      'image/png',
      'image/gif'
    );
    if(in_array($type, $valid_types)) {
      $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
      //size is in bytes
      if($size < 700000) {
        $data = file_get_contents($url);
        $path = $this->root_path.'images/'.uniqid().'.png';
        file_put_contents($path, $data);
        return $path;
      }
    }
    return FALSE;
  }

  function format_image($image_path) {
    //trim
    $exec = 'nice -n 19 convert -limit area 64 '.escapeshellarg($image_path).' -resize 300x300\> '.escapeshellarg($image_path);
    exec($exec);

    //$exec = 'pngnq -n 256 '.escapeshellarg($image_path);
    //exec($exec);

    //unlink($image_path);
    //return str_replace('.png', '-nq8.png', $image_path);
    return $image_path;
  }

  function trim($save, $color) {
    if($color === FALSE) {
      return TRUE;
    }
    if($color === '') {
      //transparent
      $exec = 'nice -n 19 convert -limit area 64 '.escapeshellarg($save).' -trim +repage '.escapeshellarg($save);
    } else {
      $exec = 'nice -n 19 convert -limit area 64 '.escapeshellarg($save).' -bordercolor '.escapeshellarg('#'.$color).' -border 1x1 -fuzz 10% -trim +repage '.escapeshellarg($save);
    }
    exec($exec);
    return TRUE;
  }
}
