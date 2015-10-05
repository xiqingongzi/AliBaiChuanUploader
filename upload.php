<?php
require_once('config.php');
require_once('system/alimage.class.class.php');
function bytesToSize1024($bytes, $precision = 2) {
    $unit = array('B','KB','MB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}

$image  = new AlibabaImage($ak, $sk, "TOP" /*$upload_endpoint, $manage_endpoint*/);
$uploadPolicy = new uploadPolicy();
$uploadPolicy->dir = $ak;    //
$uploadPolicy->name =$_FILES['image_file']['name'];  // ÎÄ¼þÃû²»ÄÜ°üº¬"/"
$uploadPolicy->namespace= $bucket; // type =TOP ±ØÌî
$res = $image->upload($_FILES['image_file']['tmp_name'],$uploadPolicy, $opts = array());
//var_dump($res);
$sFileName = $_FILES['image_file']['name'];
$sFileType = $_FILES['image_file']['type'];
$sFileSize = bytesToSize1024($_FILES['image_file']['size'], 1);
$sFileUrl=$res['url'];
echo <<<EOF
<p>Your file: {$sFileName} has been successfully received.</p>
<p>Type: {$sFileType}</p>
<p>Size: {$sFileSize}</p>
<p>URL:{$sFileUrl}</p>
EOF;
