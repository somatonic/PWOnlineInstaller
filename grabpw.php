<?php

/**
 * ProcessWire Online Installer Script
 *
 * 1. Upload this php file to the server where you want to install latest ProcessWire
 * 2. Go to the browser and call this script. It will download and extract ProcessWire files
 *    Once done successfully it will redirect to the installer
 * 3. If anything fails, make sure permission are correct on server and remove files manually
 *
 * @author  Philipp Soma Urlich <philipp at urlich dot ch>
 * @version  1.0.1
 */

ini_set('max_execution_time', 120);

define("SOURCE_URL", "http://grab.pw");
define("CURRENT_DIR", getcwd());
define("ZIP_FILE", CURRENT_DIR . '/processwire.zip');

// makre sure directory is writeable before downloading
if(!is_writable(CURRENT_DIR)) {
    die('Make sure the current directory is writeable for PHP.');
}

// download the zip file to server
downloadFile(SOURCE_URL, ZIP_FILE);

// extract the downloaded zip file
$zip = new ZipArchive;
if ($zip->open(ZIP_FILE) === TRUE) {
    for($i = 0; $i < $zip->numFiles; $i++) {
        $zip->extractTo(CURRENT_DIR, array($zip->getNameIndex($i)));
    }
    $extracted_directory = $zip->getNameIndex(0);
    $zip->close();
} else {
    die('Error opening downloaded ' . ZIP_FILE);
}

// move content of extracted folder to the current directory
recursiveMove(CURRENT_DIR . "/" . $extracted_directory , CURRENT_DIR);

// remove extracted zip directory
removeDir(CURRENT_DIR . "/" . $extracted_directory, true);

// we remove also zip file
unlink(ZIP_FILE);

// and also remove this script
unlink(__FILE__);

// redirect to the PW installer
header("Location: install.php");


/**
 * Download file form a url
 * @param  string $url      source url
 * @param  string $fileName target file
 * @return [type]           [description]
 */
function downloadFile($url, $fileName) {

    if((substr($url,0,8) == 'https://') && ! extension_loaded('openssl')) {
       die('OpenSSL extension required but not available. File could not be downloaded from '.$url);
    }

    // Define the options
    $options = array('max_redirects' => 4);
    $context = stream_context_create(array('http' => $options));

    // download the zip
    if(!$content = file_get_contents($url, $fileName, $context)) {
        die('File could not be downloaded '.$url);
    }

    if(($fp = fopen($fileName, 'wb')) === false) {
        die('fopen error for filename '.$fileName);
    }

    fwrite($fp, $content);
    fclose($fp);
}

/**
 * recursively remove directory and it's content, this works great even where unlink() fails
 * @param  string $dir      folder to remove files
 * @param  boolean $DeleteMe should folder be remove also set this to true
 */
function removeDir($dir, $deleteDir) {
    if(!$dh = @opendir($dir)) return;
    while (($obj = readdir($dh))) {
        if($obj=='.' || $obj=='..') continue;
        if (!@unlink($dir.'/'.$obj)) removeDir($dir.'/'.$obj, true);
    }
    if ($deleteDir){
        closedir($dh);
        @rmdir($dir);
    }
}


/**
 * Recursively move files from one directory to another
 *
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function recursiveMove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) {
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) {
            recursiveMove($f->getRealPath(), "$dest/$f");
            //unlink($f->getRealPath());
        }
    }
}

