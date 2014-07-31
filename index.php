<?php
# imagemash - ask users to rate which image is more something
# Copyright (C) 2014 Michael Homer
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require "config.php";

function get_images() {
    $dir = opendir("images");
    $images = array();
    while (false !== ($entry = readdir($dir))) {
        $ext = substr($entry, -4);
        if ($ext != '.jpg' && $ext != '.png' && $ext != '.gif')
            continue;
        $images[substr($entry, 0, -4)] = $entry;
    }
    return $images;
}

function pick_pair($images) {
    static $used_pairs = array();
    $uniq = false;
    while (!$uniq || in_array($uniq, $used_pairs)) {
        $pair = array_rand($images, 2);
        sort($pair);
        $uniq = join("|", $pair);
    }
    array_push($used_pairs, $uniq);
    shuffle($pair);
    return $pair;
}

function output_template($name) {
    if (file_exists("template/$name"))
        readfile("template/$name");
}

function log_winner($winner, $loser) {
    $images = get_images();
    if (!array_key_exists($winner, $images))
        return;
    if (!array_key_exists($loser, $images))
        return;
    $token = file_get_contents("php://input");
    $fp = fopen("results.txt", "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "$winner > $loser $token\n");
    fclose($fp);
}

function generate_zip() {
    $files = array(
        "index.php",
        "imagemash.css",
        "imagemash.js",
        "config.example.php",
        "images/index.html",
        "template/index.html",
        "agpl-3.0.txt",
        "README"
    );
    $must_regenerate = true;
    if (file_exists("imagemash.zip"))
        $must_regenerate = false;
    if (!$must_regenerate) {
        $ziptime = filemtime("imagemash.zip");
        $must_regenerate = !$ziptime;
        if (!$must_regenerate) {
            foreach ($files as $f) {
                $ftime = filemtime($f);
                if ($ftime >= $ziptime) {
                    $must_regenerate = true;
                    break;
                }
            }
        }
    }
    if (!$must_regenerate)
        return filesize("imagemash.zip");
    unlink("imagemash.zip");
    $zip = new ZipArchive();
    if ($zip->open("imagemash.zip", ZipArchive::CREATE) !== TRUE) {
        return false;
    }
    foreach ($files as $f) {
        $zip->addFile($f, "imagemash/$f");
    }
    $zip->close();
    return filesize("imagemash.zip");
}

function get_source() {
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Download imagemash source</title>
  <style type="text/css">html { overflow: hidden; min-width: 300px;}</style>
 </head>
 <body>
 <?php if (!$_REQUEST['embed']) {?>  <h1>Download imagemash source</h1><?php }?>
<?php
    if ($size = generate_zip()) {
        echo '<p><a href="imagemash.zip">Download</a>';
        echo " (zip, $size bytes)</p>";
        echo "<p>This is the live source code in use on this site.</p>";
    } else {
        echo 'There was an error generating a download zip for this site. ';
        echo 'You can get the main-line source from: ';
        echo '<a href="https://github.com/mwh/imagemash">https://github.com/mwh/imagemash</a>.';
    }
?>
 </body>
</html>
<?php
}

header("Content-type: text/html; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # Saving results
    log_winner($_REQUEST['winner'], $_REQUEST['loser']);
    exit;
}

if ($_REQUEST['get'] == 'source') {
    # User has requested the source code
    get_source();
    exit;
}

# Main page content
?>
<!DOCTYPE html>
<html>
 <head>
  <title><?php echo htmlspecialchars($title) ?></title>
  <link rel="stylesheet" type="text/css" href="imagemash.css" />
<?php output_template("head.html"); ?>
 </head>
 <body>
<?php output_template("header.html"); ?>
  <h1>Which of these <?php echo $nouns?> is <?php echo $adjective?>?</h1>
   <div id="pairs">

    <div id="counter">
     <span id="counter-upto">-</span>
     /
     <span id="counter-of"><?php echo $pairs?></span>
    </div>
    <div class="pair active" id="info">
    <?php output_template("first.html"); ?>
    </div><?php
$images = get_images();

for ($i = 0; $i < $pairs; $i++) {
    $pair = pick_pair($images);
    if($pair > 3 && $i == $pairs-1){
            $pair = $saved_pair;
    }else if($i == $repeat){
            $saved_pair = $pair;
    }
?>
   <div class="pair">
    <div class="image" data-me="<?php echo $pair[0]?>" data-other="<?php echo $pair[1]?>">
     <img src="images/<?php echo $images[$pair[0]]?>" />
     <br />is <?php echo $adjective?>
    </div>
    <div class="image" data-me="<?php echo $pair[1]?>" data-other="<?php echo $pair[0]?>">
     <img src="images/<?php echo $images[$pair[1]]?>" />
     <br />is <?php echo $adjective?>
    </div>
   </div>
<?php
}
?>
   <div class="pair" id="thank-you">
<?php output_template("thank-you.html"); ?>
   </div>
  </div>
  <div style="clear: both;">
   <small>
    Powered by <a href="https://github.com/mwh/imagemash">imagemash</a>,
    distributed under
    <a href="https://www.gnu.org/licenses/agpl-3.0.html">AGPL 3.0</a>.
    <a href="?get=source" id="get-source">Get the source</a>.
   </small>
  </div>
<?php output_template("footer.html");?>
  <script type="text/javascript">
   var token = '<?php echo md5(time() . ":" . rand())?>';
  </script>
  <script src="imagemash.js" type="text/javascript"></script>
 </body>
</html>
