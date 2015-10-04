<?php

        
require_once('../alimage.class.class.php');
$ak = '23165315';   // app key
$sk = '82cef51512dbd85dbf3bfb5768a6b042';
$image  = new AlibabaImage($ak, $sk, 'TOP' /*, $type, $upload_endpoint, $manage_endpoint*/);

//$res = $image->existsFile('xxxxxx', '/dir', 'block');
//$res = $image->getFile('xxxxxx', '/ddir', 'upload');
$res = $image->listFiles('typecho', '/');
//$res = $image->deleteFile('xxxxxx', '/dir', 'block');
//$res = $image->createDir('xxxxxx', '/roodt');
//$res = $image->listDirs('typecho', '/');
//$res = $image->existsFolder('xxxxxx', '/droot');
//$res = $image->deleteDir('xxxxxx', '/dir2');

//$res = $image->addPts('xxxxxx', '/²âÊÔPOPS', 'qinning.mp4', "avEncode/encodePreset/video-generic-AVC-320x240", true);
//$res = $image->getPts('xxxxxx', '55a95fc2698370fcfcc55e664142a8089c2410c1439e');
echo "<pre>";
var_dump($res);
echo "</pre>";