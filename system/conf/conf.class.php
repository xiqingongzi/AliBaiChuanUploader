<?php

class Conf{
    const CHARSET = "UTF-8";
	const SDK_VERSION = '1.0.0';
    const MANAGE_API_VERSION = "3.0";

    const UPLOAD_ENDPOINT = "http://upload.media.aliyun.com";
    const MANAGE_ENDPOINT = "http://rs.media.aliyun.com";

    const UPLOAD_API_UPLOAD = "/api/proxy/upload.json";
    const UPLOAD_API_BLOCK_INIT = "/api/proxy/blockInit.json";
    const UPLOAD_API_BLOCK_UPLOAD = "/api/proxy/blockUpload.json";
    const UPLOAD_API_BLOCK_COMPLETE = "/api/proxy/blockComplete.json";
    const UPLOAD_API_BLOCK_CANCEL = "/api/proxy/blockCancel.json";

    const TYPE_TOP = "TOP";
    const TYPE_CLOUD = "CLOUD";

    const DETECT_MIME = 1;
    const DETECT_MIME_NONE = 0;
    const INSERT_ONLY = 1;
    const INSERT_ONLY_NONE = 0;
    const MIN_OBJ_SIZE = 102400; //1024*100;
    const SUB_OBJ_SIZE = 10485760; //1024*1024*10;
}
