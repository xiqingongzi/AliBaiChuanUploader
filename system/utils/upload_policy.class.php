<?php
class UploadPolicy{
    public $detectMime = Conf::DETECT_MIME;         // is auto detecte media file mime type, default is true
    public $insertOnly = Conf::INSERT_ONLY_NONE;    // upload mode. it's not allowd uploading the same name files 
    public $namespace;                              // media namespace name
    public $bucket;                                 // media bucket name
    public $expiration = -1;                             // expiration time, unix time, in milliseconds
    public $dir;                                    // media file dir, magic vars and custom vars are supported
    public $name;                                   // media file name, magic vars and custom vars are supported
    public $sizeLimit;                              // upload size limited, in bytes
    public $mimeLimit;                     // upload mime type limited
    public $callbackUrl;                   // callback urls, ip address is recommended 
    public $callbackHost;                  // callback host
    public $callbackBody;                  // callback body, magic vars and custom vars are supported
    public $callbackBodyType;              // callback body type, default is 'application/x-www-form-urlencoded; charset=utf-8'
    public $returnBody;                    // return body, magic vars and custom vars are supported
}
