<?php  

#################################################################################
# Watermark Image with Text Class 1.0
#################################################################################
# For updates visit http://www.zubrag.com/scripts/
#################################################################################
#
# REQUIREMENTS:
# PHP 4.0.6 and GD 2.0.1 or later
# May not work with GIFs if GD2 library installed on your server 
# does not support GIF functions in full
#
#################################################################################

class Zubrag_watermark {

  var $font = 'arial.ttf';
  var $font_size = 16;
  var $angle = 0;
  var $offset_x = 0;
  var $offset_y = 0;
  var $quality = 100;
  var $image_type = -1; // Image type: 1 = GIF, 2 = JPG, 3 = PNG
  var $force_image_type = -1; // Change image type? (-1 = same as original, 1 = GIF, 2 = JPG, 3 = PNG)
  var $save_to_file = true;
  var $drop_shadow = false;

  function Zubrag_watermark($image_path='') {
    $this->setImagePath($image_path);
  }

  function setImagePath($image_path) {
    $this->image_path = $image_path;
  }

  function setShadow($drop_shadow,$color='#606060') {
    $this->drop_shadow = $drop_shadow;
    $this->shadow_color = $color;
  }

  function setText($text, $color) {
    $this->text = $text;
    $this->color = $color;
  }

  function setAngle($angle) {
    $this->angle = $angle;
  }

  function setOffset($x, $y) {
    $this->offset_x = $x;
    $this->offset_y = $y;
  }

  function setFont($font, $size = 0) {
    if (!empty($font)) $this->font = $font;
    if ($size != 0) $this->font_size = $size;
  }

  function ImageCreateFromType($type,$filename) {
   $im = null;
   switch ($type) {
     case 1:
       $im = ImageCreateFromGif($filename);
       break;
     case 2:
       $im = ImageCreateFromJpeg($filename);
       break;
     case 3:
       $im = ImageCreateFromPNG($filename);
       break;
    }
    return $im;
  }

  function ApplyWatermark($text='', $color='', $angle=0, $offset_x=0, $offset_y=0) {

    $this->setText($text, $color);
    $this->setAngle($angle);
    $this->setOffset($offset_x, $offset_y);

    // Determine image size and type
    $size = getimagesize($this->image_path);
    $size_x = $size[0];
    $size_y = $size[1];
    $image_type = $size[2]; // 1 = GIF, 2 = JPG, 3 = PNG

    // Load image
    $this->image = $this->ImageCreateFromType($image_type, $this->image_path);
    
    // Translate color to decimal
    $color = sscanf($this->color, '#%2x%2x%2x');
    $color_r = $color[0];
    $color_g = $color[1];
    $color_b = $color[2];

    $this->image_type = ($this->force_image_type != -1 ? $this->force_image_type : $image_type);

    /* Calculate TTF text size */
    $ttfsize = imagettfbbox($this->font_size, $this->angle, $this->font, $this->text);
    
    // Add custom insets
    $ttfx = $this->offset_x + max($ttfsize[0],$ttfsize[2],$ttfsize[4],$ttfsize[6]);
    $ttfy = $this->offset_y + max($ttfsize[1],$ttfsize[3],$ttfsize[5],$ttfsize[7]);
    
    /* shadow */
    if ($this->drop_shadow) {
      // Translate color to decimal
      $scolor = sscanf($this->shadow_color, '#%2x%2x%2x');
      $scolor_r = $scolor[0];
      $scolor_g = $scolor[1];
      $scolor_b = $scolor[2];

      $text_color = imagecolorallocate($this->image, $scolor_r, $scolor_g, $scolor_b);
      imagettftext($this->image, $this->font_size, 
        $this->angle, // angle
        $size_x - $ttfx - 2, // left inset
        $size_y - $ttfy - 2, // top inset
        $text_color, $this->font , $this->text)
        or die('Cannot render TTF text.');
    }

    /* Render text */
    $text_color = imagecolorallocate($this->image, $color_r, $color_g, $color_b);
    imagettftext($this->image, $this->font_size, 
      $this->angle, // angle
      $size_x - $ttfx - 3, // left inset
      $size_y - $ttfy - 3, // top inset
      $text_color, $this->font , $this->text)
      or die('Cannot render TTF text.');

  } // ApplyWatermark

  function OutputImageInternal($im, $filename='') {
 
    $res = null;
 
    // ImageGIF is not included into some GD2 releases, so it might not work
    // output png if gifs are not supported
    if(($this->image_type == 1)  && !function_exists('imagegif')) $this->image_type = 3;

    switch ($this->image_type) {
      case 1:
        if ($this->save_to_file) {
          $res = ImageGIF($im,$filename);
        }
        else {
          header("Content-type: image/gif");
          $res = ImageGIF($im);
        }
        break;
      case 2:
        if ($this->save_to_file) {
          $res = ImageJPEG($im,$filename,$this->quality);
        }
        else {
          header("Content-type: image/jpeg");
          $res = ImageJPEG($im, NULL, $this->quality);
        }
        break;
      case 3:
        if (PHP_VERSION >= '5.1.2') {
          // Convert to PNG quality.
          // PNG quality: 0 (best quality, bigger file) to 9 (worst quality, smaller file)
          $quality = 9 - min( round($this->quality / 10), 9 );
          if ($this->save_to_file) {
            $res = ImagePNG($im, $filename, $quality);
          }
          else {
            header("Content-type: image/png");
            $res = ImagePNG($im, NULL, $quality);
          }
        }
        else {
          if ($this->save_to_file) {
            $res = ImagePNG($im, $filename);
          }
          else {
            header("Content-type: image/png");
            $res = ImagePNG($im);
          }
        }
        break;
    }
 
    return $res;
 
  }

  function Output() {
    $this->save_to_file = false;
    $this->OutputImageInternal($this->image);
  }

  function SaveAsFile($filename) {
    $this->save_to_file = true;
    $this->OutputImageInternal($this->image, $filename);
  }

  function Free() {
    imagedestroy($this->image);
  }

}

?>