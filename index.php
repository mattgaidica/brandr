<?php

ini_set('display_errors', TRUE);

if(!empty($_POST['image_url'])) {
  $url = $_POST['image_url']; //do url regex

  include('brandr.php');
  include('imagetheme.php');
  $brandr = new Brandr;
  $imagetheme = new Imagetheme;

  $image = $brandr->get_image($url);

  if($image !== FALSE) {
    $image = $brandr->format_image($image);
    $border = $imagetheme->get_border_color($image);
    $accents = $imagetheme->get_accent_colors($image, $border);

    //limit accents
    $tmp = array();
    $pos = 0;
    foreach($accents['accents'] as $accent) {
      $tmp[] = $accent;
      $pos++;
      if($pos == 5) {
        break;
      }
    }
    $accents['accents'] = $tmp;

    unlink($image);
  } else {
    //echo 'FAIL: ';
    //var_dump($image);
  }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=us-ascii" />

  <title>brandr</title>
  <script type="text/javascript" src="/brandr/style/js/jquery-1.5.1.min.js"></script>
  <link rel="stylesheet" type="text/css" href="/brandr/extras/css/dark_green.css" media="screen" />
  <link href="/brandr/style/css/prettyPhoto.css" rel="stylesheet" type="text/css" />
  <!--[if IE]>
     <style type="text/css">
         .button_colour { -webkit-border-radius: .0em;  -moz-border-radius: .0em; border-radius: .0em;  }
		#portfolio-filter ul { display: block;margin:0px; padding: 0px;}
		#portfolio-filter li{display: inline;}
		span.quoteblock{ font: 80px "OldStandardTTItalic", Arial, Helvetica, sans-serif; position: absolute; top: -30px; left: 0px; text-indent: -10px; }
		span.quoteblock2{ font: 100px 'LeagueGothicRegular', Arial, Helvetica, sans-serif; position: absolute; top: -32px; left: 0px; text-indent: 0px; }
		.bracket span{padding:20px 0px 40px 0px;}
		.blogbracket span{padding:0px 0px 40px 0px; }
</style>
<![endif]-->
</head>

<body>
  <div id="wrapper">
    <div id="header">
      <img src="/brandr/style/images/logo.png" width="120" height="110" alt="" class="logo" />

      <div id="menu">
        <ul>
          <li><a href="#try-it-out">Try It Out</a></li>

          <li><a href="#the-api">The API</a></li>

          <li><a href="#our-process">Our Process</a></li>

          <li><a href="#contact" class="last">Contact</a></li>
        </ul>
      </div>

      <div id="shadow"></div>
    </div><!-- Main Content Starts -->
    <!-- Home Page Starts -->

    <div>
      <div id="try-it-out" class="content">
        <h1>Try it Out</h1>
        <h2>We've created an impressively accurate logo-to-brand algorithm. Instantly colorize your application using a client logo, or streamline your white label process.</h2>
        <div>
        <div style="text-align:center;padding-bottom:40px;">
          <p>Click to try...</p>
          <a style="padding:10px 25px" href="#" onclick="image_url('http://landr.co/brandr/nasa.gif');"><img style="max-height:120px;" src="nasa.gif"/></a>
          <a style="padding:10px 25px" href="#" onclick="image_url('http://landr.co/brandr/php.png');"><img style="max-height:120px;" src="php.png"/></a>
          <a style="padding:10px 25px" href="#" onclick="image_url('http://landr.co/brandr/apple.png');"><img style="max-height:120px;" src="apple.png"/></a>
        </div>
        <form method="POST" id="url_form" action="http://landr.co/brandr/">
          <input type="text" id="image-url" name="image_url" placeholder="link to logo image (png, jpg, gif)"/>
          <input type="submit" class="submit"/>
        </form>
        </div>
        <div class="clear"></div>
        <?php
        if(!empty($border) || !empty($accents['accents'])) {
          echo '<div class="center"><div class="brand"><table>';
          //row 1
          echo '<tr>';
          if(!empty($border)) {
            echo '<td>Border</td>';
          }
          if(!empty($accents['accents'])) {
            foreach($accents['accents'] as $accent) {
              echo '<td>'.round($accent['cover'], 2).'%</td>';
            } 
          }
          echo '</tr>';
          //row 2
          echo '<tr>';
          if(!empty($border)) {
            if(!empty($accents['accents'])) {
              $style = 'border-right:1px dashed #444;';
            }
            echo '<td style="'.$style.'"><img style="background-color:#'.$border.';" src="/brandr/style/images/blank.png"/></td>';
          }
          if(!empty($accents['accents'])) {
            foreach($accents['accents'] as $accent) {
              echo '<td><img style="background-color:#'.$accent['color'].';" src="/brandr/style/images/blank.png"/></td>';
            } 
          }
          echo '</tr>';
          //row 3
          echo '<tr>';
          if(!empty($border)) {
            echo '<td>#'.$border.'</td>';
          }
          if(!empty($accents['accents'])) {
            foreach($accents['accents'] as $accent) {
              echo '<td>#'.$accent['color'].'</td>';
            }
          }
          echo '</tr>';
          echo '</table></div></div>';
          //black and white
          echo '<div class="center">';
          if(!empty($accents) && (!empty($accents['extremes']['white']) || !empty($accents['extremes']['black']))) {
            if(!empty($accents['extremes']['white']) && !empty($accents['extremes']['black'])) {
              echo '<tr><td>It contains <span style="text-decoration:underline;">white</span> and <span style="text-decoration:underline;">black</span>.</td></tr>';
            } else {
              if(!empty($accents['extremes']['white'])) {
                echo '<tr><td>It contains <span style="text-decoration:underline;">white</span>.</td></tr>';
              }
              if(!empty($accents['extremes']['black'])) {
                echo '<tr><td>It contains <span style="text-decoration:underline;">black</span>.</td></tr>';
              }
            }
          }
          echo '</div>';
        }
        if(!empty($_POST['image_url']) && empty($border) && empty($accents['accents'])) {
          echo '<div class="center">Please try again.</div>';
        }
        /*
        if(!empty($_POST['image_url']) && (!empty($border) || !empty($accents['accents']))) {
          echo '<div class="center"><img class="center" id="image" style="display:none;width:200px;" src="'.$_POST['image_url'].'"/><span id="image-span" style="color:#9dbb40;">&#171;&nbsp;<a href="#" onclick="'."showstuff('image');hidestuff('image-span');".'">show image</a>&nbsp;&#187;</span></div>';
        }
        */

        ?>

      </div>

      <div id="the-api" class="content">
        <h1>The API</h1>
        <h2>Call the API. Use it in your application.</h2>
        <p>The API is aimed to deliver the important color data we gather from the logo, giving you enough information to brand, or colorize your application. Use any method you wish to POST the logo image to our server, and we will return a JSON encoded array.</p>
        <h3>Example</h3>
        <p><strong>POST</strong> http://landr.co/brandr/api.php image_url=http://builtbyprime.com/img/logo.png</p>
        <p><strong>JSON Response</strong> {"accents":[{"color":"4a88d4","cover":100}],"border":"ffffff","white":1,"black":1,"sample":"http:\/\/landr.co\/brandr\/images\/4ec7fa4058cb3-nq8.png"}</p>
        <p><strong>Data Format</strong><br>
<span style="margin-left:20px;">Array</span><br>
<span style="margin-left:40px;">(</span><br>
<span style="margin-left:60px;">[accents] => Array</span><br>
<span style="margin-left:80px;">(</span><br>
<span style="margin-left:100px;">[0] => Array</span><br>
<span style="margin-left:120px;">(</span><br>
<span style="margin-left:140px;">[color] => 4a88d4</span><br>
<span style="margin-left:140px;">[cover] => 100</span><br>
<span style="margin-left:120px;">)</span><br>
<span style="margin-left:80px;">)</span><br>
<span style="margin-left:60px;">[border] => ffffff</span><br>
<span style="margin-left:60px;">[white] => 1</span><br>
<span style="margin-left:60px;">[black] => 1</span><br>
<span style="margin-left:60px;">[sample] => http://landr.co/brandr/images/4ec7f9f3f404e-nq8.png</span><br>
<span style="margin-left:40px;">)</span><br>
        </p>
        <h3>Notes</h3>
        <p>The data return structure is quite simple. By sending a POST request to the API location with a field "image_url" equal to the existing location of the image, you will receive an output. The types of data are as follows.
          <ul class="bullet">
            <li>Accents: These are colors within the image that we have determined to be suitable for branding, or colorizing. The colors are listed in order from most prominent to least prominent, and include a "cover" percentage which is a metric we use to describe the amount that particular color is used versus the other accent colors. Colors are hex values.</li>
            <li>Border: If a border color is found this will return the hex color of the border. Border colors are not included in the accent color list, because they are generally used differently when building a page aesthetic. If no border color is found, this field is empty.</li>
            <li>White/Black: It is sometimes important to know if a logo uses pure white or pure black, because they are often very bold colors to use unless the brand includes them. These values will be either a 0 or 1.</li>
            <li>Sample: This field gives the URL to the logo that we used to create the data. Once we grab the initial logo from the provided URL it goes through a mixture of resizing and optimizing so we can analyze it more quickly and accurately.</li>
          </ul>
        If you choose to read the HTTP headers, you will find that a status of 200 means the process was successful, while a status of 400 means it was not successful.
        </p>

      </div>

      <div id="our-process" class="content">
        <h1>Our Process</h1>
        <h2>This is not a palette generator.</h2>
        <p>The problem we aimed to solve was trying to overcome the basic limitations that a simple palette generator had. There are many tools and methods that produce a list of the mostly used colors in an image, but they turn out to be useless in the process automating an aesthetic. We used our own design background, machine learning processes, and creative coding to deliver a package of data that can visually rebuild a brand.</p>
        <div class="col2">
          <h4>Border Analyzing</h4>
          <img src="http://landr.co/brandr/style/images/process-border.png" class="right">
          <p>Once of the major flaws in simply placing a client logo onto a page is not taking into consideration the border or background color. We use a number of filtering techniques, mostly for handling "color noise", to determine if the image has a border, and what color that border is.</p>
          <h4>Alias and Edge Detection</h4>
          <img src="http://landr.co/brandr/style/images/process-aliasing.png" class="right">
          <p>When humans look at a brand, we see distinct colors, but what we don't realize is there is likely some type of alias to any object in an image. This alias is the slight fade between colors, or on the edge of text. These "in-between" colors are not important to the brand, and are not counted in our process. We use a mixture of negative image processing and sobel convolution to determine what is important.</p>
        </div>
        <div class="col2 last">
          <h4>Accent Metrics</h4>
          <img src="http://landr.co/brandr/style/images/process-metrics.png" class="right">
          <p>After we optimize the image, we iterate every pixel, which helps us build a set of information about the different colors. We used a mahcine learning process to determine what humans consider a strong brand color, versus what colors should not be used. This is where design assumptions are made.</p>
          <h4>Accent Grouping</h4>
          <img src="http://landr.co/brandr/style/images/process-grouping.png" class="right">
          <p>The first factor in grouping colors is the obvious one- that we want to make a distinction between the major, and most prominent colors used in the image. The second is that most images include slight color variation, whether due to noise or intentional fading, but in either case, similar shades of similar colors need to be grouped.</p>
        </div>
      </div>

      <div id="contact" class="content">
        <h1>Contact</h1>
        <h2>Let me know what you think...</h2>

        <p class="blogbracket">
          <span class="left"></span>
            <a href="mailto:matt@gaidi.ca">matt@gaidi.ca</a>
          <span class="right"></span>
        </p>

        <p>We all know what happens when we give access to a color picker- everything becomes a highlighter color.</p>
      </div>

      <div id="footer">
        <p>&copy; <a href="http://landr.co">Landr.co</a></p>
      </div>
    </div>
  </div>

  <script>
    function showstuff(boxid){
       document.getElementById(boxid).style.display = 'block';
    }

    function hidestuff(boxid){
       document.getElementById(boxid).style.display = 'none';
    }

    function image_url(value) {
      $('#image-url').val(value);
    }
  </script>

</body>
</html>

