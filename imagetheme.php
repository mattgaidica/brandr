<?php
class Imagetheme {
  function __construct() {
    $this->root_path = dirname (__FILE__) . '/';
  }

  function get_border_color($path) {
    $image_info = getimagesize($path);
    $image = imagecreatefrompng($path);
    $width = $image_info[0];
    $height = $image_info[1];
    $border_indexes = array();
    $cache = array();
    $sample_pixels = 1;
    
    for ($y=0;$y<$height;$y++) {
      for ($x=0;$x<$width;$x++) {
        if($y < $sample_pixels || $y > $height - $sample_pixels - 1 || 
          $x < $sample_pixels || $x > $width - $sample_pixels - 1) {
          $border_indexes[] = imagecolorat($image, $x, $y);
        }
      }
    }

    if(!empty($border_indexes)) {
      $border_indexes = array_count_values($border_indexes);
      natsort($border_indexes);
      $border_indexes = array_reverse($border_indexes, TRUE);
    }
    
    if(count($border_indexes) > 30) {
      return FALSE; //too many indexes, too many overall colors
    }

    $pos = 1;
    $border_noise = 0;
    foreach($border_indexes as $index=>$count) {
      if(empty($cache[$index])) {
        $colors = imagecolorsforindex($image, $index);
        $hex = $this->rgb2hex($colors['red'], $colors['green'], $colors['blue']);
        $cache[$index] = array(
          'colors'=>$colors,
          'hex'=>$hex
        );
      }
      if($pos == 1) {
        $max_border_count = $count;
        $max_border_index = $index;
      } else {
        if($count / $max_border_count > .3) {
          //dominant colors must be very similar
          $compare_val = 1;
        } else {
          //less dominant colors can be noisy, but still similar
          $compare_val = 3;
        }
        $compare_colors = $this->compare_colors($cache[$max_border_index]['hex'], $cache[$index]['hex'], $compare_val);
        if($compare_colors == FALSE) {
          $border_noise++;
          if($border_noise > 10) {
            return FALSE; //too many very different colors
          }
          if($count / $max_border_count > .05) {
            return FALSE;
          }
        }
      }
      $pos++;
    }

    if($cache[$max_border_index]['colors']['alpha'] == 0) {
      //full color
      return $cache[$max_border_index]['hex'];
    } elseif($cache[$max_border_index]['colors']['alpha'] == 127) {
      //transparent
      return FALSE;
    } else {
      //semi-transparent
      return FALSE;
    }
  }

  function get_accent_colors($path, $border_color=NULL) {
    $image_info = getimagesize($path);
    $image = imagecreatefrompng($path);
    $width = $image_info[0];
    $height = $image_info[1];
    
    $negative_path = $this->root_path.'images/tmp-negative-'.uniqid().'.png';
    $exec = "convert ".escapeshellarg($path)." -define convolve:scale='!' -define morphology:compose=Lighten -morphology Convolve  'Sobel:>' ".escapeshellarg($negative_path);
    exec($exec);
    $negative = imagecreatefrompng($negative_path);
    unlink($negative_path);
    
    $accent_colors = array();
    $cache = array();
    $accent_pixels = 0;
    $include_white = 0;
    $include_black = 0;
    for ($y=0;$y<$height;$y++) {
      for ($x=0;$x<$width;$x++) {
        $negative_index = imagecolorat($negative,$x,$y);
        $negative_colors = imagecolorsforindex($negative,$negative_index);
        if($negative_colors['red'] < 5 && $negative_colors['green'] < 5 && $negative_colors['blue'] < 5) {
          $index = imagecolorat($image,$x,$y);
          if(empty($cache[$index])) {
            $colors = imagecolorsforindex($image,$index);
            $hsv = $this->rgb2hsv($colors['red'],$colors['green'],$colors['blue']);
            $hex = $this->rgb2hex($colors['red'],$colors['green'],$colors['blue']);
            $cache[$index] = array(
              'colors'=>$colors,
              'hsv'=>$hsv,
              'hex'=>$hex
            );
          }

          if($cache[$index]['hsv']['value'] > .13 && 
            $cache[$index]['hsv']['saturation'] > .13 && 
            $cache[$index]['hsv']['value'] * 100 > (832.2265 * pow(($cache[$index]['hsv']['saturation']*100),-.9819))) {
            //accent colors
            $accent_pixels++;
            $accent_colors[] = $cache[$index]['hex'];
          } else {
            //invalid accent colors (too dark, too light)
            if(($cache[$index]['hsv']['value'] * 100) > 96) {
              $include_white = 1;
            }
            if(($cache[$index]['hsv']['value'] * 100) < 10) {
              $include_black = 1;
            }
          }
        }
      }
    }
    unset($cache);
    //unlink($negative_path);
    imagedestroy($image);
    imagedestroy($negative);

    $accent_colors = array_count_values($accent_colors);
    natsort($accent_colors);
    $accent_colors = array_reverse($accent_colors,TRUE);
    
    $accent_groups = array();
    $blacklist = array();
    foreach($accent_colors as $hex1=>$count1) {
      $count_all = 0;
      if(!in_array($hex1,$blacklist)) {
        $percent_cover = ($count1/$accent_pixels) * 100;
        $accent_groups[$hex1] = array(
          'color'=>$hex1,
          'count'=>$count1,
          'group_count'=>$count1,
          'cover'=>$percent_cover,
          'group_cover'=>$percent_cover
          );
        $blacklist[] = $hex1;
        $count_all += $count1;
        foreach($accent_colors as $hex2=>$count2) {
          if(!in_array($hex2,$blacklist)) {
            $compare_colors = $this->compare_colors($hex1,$hex2,0);
            if($compare_colors) {
              $blacklist[] = $hex2;
              $count_all += $count2;
              $accent_groups[$hex1]['group_count'] = $count_all;
              $accent_groups[$hex1]['group_cover'] = ($count_all/$accent_pixels) * 100;
            }
          }
        }
      }
    }
    
    if(!empty($border_color)) {
      $keys = array_keys($accent_groups);
      if(in_array($border_color,$keys)) {
        //$border_color_data = $accent_groups[$border_color];
        $accent_pixels = $accent_pixels - $accent_groups[$border_color]['group_count'];
        unset($accent_groups[$border_color]);
        foreach($accent_groups as $hex=>$data) {
          $accent_groups[$hex]['cover'] = ($accent_groups[$hex]['count'] / $accent_pixels) * 100;
          $accent_groups[$hex]['group_cover'] = ($accent_groups[$hex]['group_count'] / $accent_pixels) * 100;
        }
      }
    }
    
    //$accents = $this->subval_sort($accent_groups,'cover');
    //$accents = array_reverse($accents);
    
    return array(
      'accents'=>$accent_groups,
      'extremes'=>array(
        'black'=>$include_black,
        'white'=>$include_white
      )
    );
  }

  function compare_colors($hex,$hex_compare,$bool_safety='') {

    $rgb = $this->hex2rgb($hex);
    $hsv = $this->rgb2hsv($rgb['red'],$rgb['green'],$rgb['blue']);
    
    $rgb_compare = $this->hex2rgb($hex_compare);
    $hsv_compare = $this->rgb2hsv($rgb_compare['red'],$rgb_compare['green'],$rgb_compare['blue']);
    
    $hue_compensated = $hsv['hue'] + 1;
    $hue_compare_compensated = $hsv_compare['hue'] + 1;
    $hue_compare_spread = array(
      $hue_compare_compensated-1,
      $hue_compare_compensated,
      $hue_compare_compensated+1
    );
    $min_hue_compare = array();
    foreach($hue_compare_spread as $hue_compare_element) {
      $min_hue_compare[] = abs($hue_compensated-$hue_compare_element);
    }
    
    $delta_hue = min($min_hue_compare);
    $delta_saturation = abs($hsv['saturation']-$hsv_compare['saturation']);
    $delta_value = abs($hsv['value']-$hsv_compare['value']);
    
    $val_sat_distance = sqrt(pow($delta_saturation,2) + pow($delta_value,2));

    switch(true) {
      case($val_sat_distance > .8) :
        $val_sat_safety = 3; //dangerous
        break;
      case($val_sat_distance > .6) :
        $val_sat_safety = 2; //moderate-dangerous
        break;
      case($val_sat_distance > .4) :
        $val_sat_safety = 1; //moderate
        break;
      default :
        $val_sat_safety = 0; //safe
    }
    
    switch(true) {
      case($delta_hue > .13) :
        $hue_safety = 3; //dangerous
        break;
      case($delta_hue > .09) :
        $hue_safety = 2; //moderate-dangerous
        break;
      case($delta_hue > .06) :
        $hue_safety = 1; //safe-moderate
        break;
      default :
        $hue_safety = 0; //safe
    }
    
    $total_safety = $val_sat_safety + $hue_safety;
    
    if(is_int($bool_safety)) {
      if($bool_safety >= $total_safety) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
    
    $keep = array('total_safety');
    $vars = get_defined_vars();
    foreach($vars as $key=>$value) {
      if(!in_array($key,$keep)) {
        unset($$key);
      }
    }
    unset($vars);
    unset($key);
    unset($value);
    unset($keep);
    
    return $total_safety;
  }
  
  function rgb2hex($r,$g,$b) {
    $hex = '';
    $hex.= str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
		$hex.= str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
		$hex.= str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

		return $hex;
  }
  
  function hex2rgb($color) {
    $color = (string)$color;
    if (strlen($color) == 6) {
      list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    } else {
      return false;
    }

    $r = hexdec($r); 
    $g = hexdec($g); 
    $b = hexdec($b);
    return array('red'=>$r, 'green'=>$g, 'blue'=>$b);
  }
  
  function rgb2hsv($R,$G,$B) {
    $HSL = array();

    $var_R = ($R / 255);
    $var_G = ($G / 255);
    $var_B = ($B / 255);

    $var_Min = min($var_R, $var_G, $var_B);
    $var_Max = max($var_R, $var_G, $var_B);
    $del_Max = $var_Max - $var_Min;

    $V = $var_Max;

    if ($del_Max == 0) {
      $H = 0;
      $S = 0;
    } else {
      $S = $del_Max / $var_Max;
      $del_R = ((($del_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
      $del_G = ((($del_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
      $del_B = ((($del_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

      if      ($var_R == $var_Max) $H = $del_B - $del_G;
      else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
      else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

      if ($H<0) $H++;
      if ($H>1) $H--;
    }

    $HSL['hue'] = $H;
    $HSL['saturation'] = $S;
    $HSL['value'] = $V;
    return $HSL;
  }

  //not sure if this works, had to tweak other function
  function hsv2rgb($h,$s,$v) {
    if($s == 0) {
      $r = $g = $B = $v * 255;
    } else {
      $var_H = $h * 6;
      $var_i = floor($var_H);
      $var_1 = $v * (1 - $s);
      $var_2 = $v * (1 - $s * ($var_H - $var_i ));
      $var_3 = $v * (1 - $s * (1 - ($var_H - $var_i)));

      if       ($var_i == 0) { $var_R = $v     ; $var_G = $var_3  ; $var_B = $var_1 ; }
      else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $v      ; $var_B = $var_1 ; }
      else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $v      ; $var_B = $var_3 ; }
      else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $v     ; }
      else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $v     ; }
      else                   { $var_R = $v     ; $var_G = $var_1  ; $var_B = $var_2 ; }

      $r = $var_R * 255;
      $g = $var_G * 255;
      $B = $var_B * 255;
    }
    return array('red'=>$r,'green'=>$g,'blue'=>$B);
  }
  
  function hex2hsv($hex) {
    $rgb = $this->hex2rgb($hex);
    return $this->rgb2hsv($rgb['red'],$rgb['green'],$rgb['blue']);
  }
  
  function hsv2hex($h,$s,$v) {
    $rgb = $this->hsv2rgb($h,$s,$v);
    return $this->rgb2hex($rgb['red'],$rgb['green'],$rgb['blue']);
  }
}
