<?php
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
Header("Content-Type: image/png");

$width=800;
$height=600;
$xoffset = 30;
$yoffset = 20;
$font = 1;


function array_max($array, &$key, &$value) {
   $value = 0;
   $key = 0;
   foreach ($array as $k=>$v) {
      if ($v > $value) {
          $key = $k; 
          $value = $v;
      }
   }
}

function array_min($array, &$key, &$value) {
   array_max($array, $key, $max);
   $value = $max;
   foreach ($array as $k=>$v) {
      if ($v < $value) {
          $key = $k; 
          $value = $v;
      }
   }
}


$res = db_query(" select to_char(created,'YYYY-MM-DD') as day,count(id) from incidents group by day order by day");
if (!$res) {
    airt_error('DB_QUERY', 'graph.php:'.__LINE__);
    Header("Location: $_SERVER[PHP_SELF]");
    exit;
}

$data = array();
while ($row = db_fetch_next($res)) {
    $data[$row['day']] = $row['count'];
}

$image = imagecreate($width, $height);
$background = imagecolorallocate($image, 255,255,255);
$linecolor = imagecolorallocate($image, 0, 0, 0);
$textcolor = imagecolorallocate($image, 0, 0, 0);
$markcolor = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $background);


$xinterval = ($width-2*$xoffset) / sizeof($data);
array_max($data, $date, $ymax);
array_min($data, $date, $ymin);
$yrange = $ymax - $ymin;
$ynum =  ($height-2*$yoffset) / 10;
$ygap = $yrange / $ynum;

  // draw x-axis

  imageline($image, $xoffset, $height-$yoffset, $width-$xoffset,
    $height-$yoffset, $linecolor);

  // draw y-axis
  imageline($image, $xoffset, $yoffset, $height-$yoffset, $linecolor);

   // x-labels
   $keys = array_keys($data);
   for ($i=1; $i <= sizeof($data); $i++) {
       $x = $xoffset+($i*$xinterval);
       $y = $height-$yoffset;
       imageline($image, $x, $y-5, $x, $y+5, $linecolor);
       if ($i % 2 == 0)
         imagestring($image, $font, $x-19, $height-9, $keys[$i-1], $textcolor);
       else
         imagestring($image, $font, $x-19, $height-16, $keys[$i-1], $textcolor);
   }

   // y-labels
   for ($i=1; $i < $ynum; $i++) {
       $x = $xoffset;
       $y = $height-$xoffset-10*$i;
       imageline($image, $x-5, $y, $x+5, $y, $linecolor);
       imagestring($image, $font, 1, $y-5, $ymin+floor($min+($i*$ygap)), $textcolor);
   }

   // plot observations and connect the dots
   $xprev = $yprev = 0;
   $values = array_values($data);
   for ($i=0; $i<sizeof($data); $i++) {
       $x = $xoffset+(1+$i)*$xinterval;
       $y = $height-$yoffset-10*(($values[$i] - $ymin)/$ygap);
       imageline($image, $x-5, $y, $x+5, $y, $markcolor);
       imageline($image, $x, $y-5, $x, $y+5, $markcolor);

       if ($xprev != 0) {
           imageline($image, $xprev, $yprev, $x, $y, $linecolor);
       }
       $xprev = $x;
       $yprev = $y;
   }

   imagepng($image);
?>

?>
