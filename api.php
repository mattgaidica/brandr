<?php

ini_set('display_errors', TRUE);

if(!empty($_POST['image_url'])) {
  $url = $_POST['image_url'];

  include('brandr.php');
  include('imagetheme.php');
  $brandr = new Brandr;
  $imagetheme = new Imagetheme;

  $image = $brandr->get_image($url);
  if($image !== FALSE) {
    $image = $brandr->format_image($image);
    $border = $imagetheme->get_border_color($image);
    $accents = $imagetheme->get_accent_colors($image, $border);

    $final = array();
    $final['accents'] = array();
    foreach($accents['accents'] as $accent) {
      $final['accents'][] = array(
        'color'=>$accent['color'],
        'cover'=>round($accent['cover'], 2)
      );
    }

    $final['border'] = $border;
    $final['white'] = $accents['extremes']['white'];
    $final['black'] = $accents['extremes']['black'];
    $parts = explode('/', $image);
    $final['sample'] = 'http://landr.co/brandr/images/'.$parts[count($parts) - 1];

    header('HTTP/1.1 200 OK');
    echo json_encode($final);
    die();
  }
}
header("HTTP/1.0 400 Bad Request");
echo 'error';