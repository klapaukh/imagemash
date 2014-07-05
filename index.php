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
    $fp = fopen("results.txt", "a");
    fwrite($fp, "$winner > $loser\n");
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
        return true;
    unlink("imagemash.zip");
    $zip = new ZipArchive();
    if ($zip->open("imagemash.zip", ZipArchive::CREATE) !== TRUE) {
        return false;
    }
    foreach ($files as $f) {
        $zip->addFile($f, "imagemash/$f");
    }
    $zip->close();
    return true;
}

function get_source() {
?>
<!DOCTYPE html>
<html>
 <head><title>Download imagemash source</title></head>
 <body>
  <h1>Download imagemash source</h1>
<?php
    if (generate_zip()) {
        echo '<a href="imagemash.zip">Download</a>';
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
     <span id="counter-upto">1</span>
     /
     <span id="counter-of"><?php echo $pairs?></span>
    </div>
<?php
$images = get_images();

for ($i = 0; $i < $pairs; $i++) {
    $pair = pick_pair($images);
?>
   <div class="pair<?php if (!$i) echo " active";?>">
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
    <a href="?get=source">Get the source</a>.
   </small>
  </div>
<?php output_template("footer.html");?>
  <script type="text/javascript">
   var token = '<?php echo md5(time() . ":" . rand())?>';
  </script>
  <script src="imagemash.js" type="text/javascript"></script>
 </body>
</html>
