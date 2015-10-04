<?php
require_once('../alimage.class.class.php');
$ak = '23165315';   // app key
$sk = '82cef51512dbd85dbf3bfb5768a6b042 '; // secret key
$image  = new AlibabaImage($ak, $sk, "TOP" /*$upload_endpoint, $manage_endpoint*/);
$uploadPolicy = new uploadPolicy();
$uploadPolicy->dir = '';    //
$uploadPolicy->name = 'file';  // 文件名不能包含"/"
//$uploadPolicy->namespace= '$bucket'; // type =TOP 必填
$uploadPolicy->bucket=$bucket; // type =CLOUD 必填

// 小文件上传
$res = $image->upload('image/image.jpg', $uploadPolicy, $opts = array());
var_dump($res);
// 直接使用分片上传接口
$res = $image->uploadSuperfile('image/image.jpg', $uploadPolicy);
var_dump($res);

// 分别调用分片上传接口,对于断点续传场景
$file = 'image/image.jpg';
$fileSize = filesize($file);
$filemd5 = md5_file($file);
$subObjSize = 2345677;
$md5_parts = array();
$res = $image->multipartInit($file, $uploadPolicy, 0, $subObjSize);
var_dump($res);
if($res['isSuccess'])
{
    array_push($md5_parts, array('eTag' => $res['eTag'], 'partNumber' => $res['partNumber']));
    $uploadId = $res['uploadId'];
    $id = $res['id'];
    $opts = array();
    $opts['uploadId'] = $uploadId;
    $opts['id'] = $id;
    $opts['partNumber'] = 2;
    $res = $image->multipartUpload($file, $uploadPolicy, $subObjSize, $fileSize-$subObjSize, $opts);
    var_dump($res);
    if($res['isSuccess'])
    {
        array_push($md5_parts, array('eTag' => $res['eTag'], 'partNumber' => $res['partNumber']));
        $opts = array();
        $opts['uploadId'] = $uploadId;
        $opts['id'] = $id;
        $opts['md5'] = $filemd5;
        $res = $image->multipartComplete($uploadPolicy, $md5_parts, $opts);
        var_dump($res);
    }else{
        $opts = array();
        $opts['uploadId'] = $uploadId;
        $opts['id'] = $id;
        $res = $image->multipartCancel($uploadPolicy, $opts);
        var_dump($res);
    }
}
var_dump($res);
