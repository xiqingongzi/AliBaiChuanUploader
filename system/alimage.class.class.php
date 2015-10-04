<?php
if (! defined ( 'ALI_IMAGE_SDK_PATH' )) {
	define ( 'ALI_IMAGE_SDK_PATH', dirname ( __FILE__ ) );
}

require_once (ALI_IMAGE_SDK_PATH . '/conf/conf.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/upload_policy.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/mimetypes.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/encode_utils.class.php');
#if(function_exists("imageroundcorner")){
require_once (ALI_IMAGE_SDK_PATH . '/upload_client_quercus.class.php');
#}else{
require_once (ALI_IMAGE_SDK_PATH . '/upload_client.class.php');
#}
require_once (ALI_IMAGE_SDK_PATH . '/manage_client.class.php');


class AlibabaImage {

    private $upload_client;
    private $manage_client;
    
    private $upload_endpoint = Conf::UPLOAD_ENDPOINT;
    private $manage_endpoint = Conf::MANAGE_ENDPOINT;
	private $ak;
	private $sk;
    private $type = "TOP"; //"CLOUD"

	/**
	 * 构造函数
	 * @param string $ak  云存储公钥
	 * @param string $sk  云存储私钥
     * @param string $type  兼容TOP与tea云的 ak/sk
	 * @param string $upload_endpoint 云存储上传Api访问地址
     * @param string $manage_endpoint 云存储管理Api访问地
	 * @throws Exception
	 */
	public function __construct($ak, $sk, $type = "TOP", $upload_endpoint = null, $manage_endpoint = null) {
		/*
		if (empty($ak) || empty($sk)) 
        {
			throw new Exception ( 'key was not passed into the constructor.' );
		}
        */
        $this->ak = $ak;
        $this->sk = $sk;
        $this->type = $type;
        if(null !== $upload_endpoint)
        {
            $this->upload_endpoint = $upload_endpoint;
        }
        if(null !== $manage_endpoint)
        {
            $this->manage_endpoint = $manage_endpoint;
        }
        $isQuercus= (function_exists("imageroundcorner")) ? "Quercus": "";
        $uploadObj = "UploadClient" . $isQuercus;
        $this->upload_client = new ${uploadObj}($ak, $sk, $type, $this->upload_endpoint);
        $this->manage_client = new ManageCLient($ak, $sk, $type, $this->manage_endpoint);
	}

    /*
     * 直接上传文件,适合文件比较小的情况
    */
    public function upload($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        return $this->upload_client->upload($file, $uploadPolicy, $opts, $meta, $var);
    }

    /*
     * 直接上传内容,适合内容比较小的情况
    */
    public function uploadByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        return $this->upload_client->uploadByContent($content, $uploadPolicy, $opts, $meta, $var);
    }

    /*
     * 分片上传，文件比较大的时候用来分片上传
    */
    public function uploadSuperfile($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        return $this->upload_client->uploadSuperfile($file, $uploadPolicy, $opts, $meta, $var);
    }

    /*
     * 创建分片上传任务，根据文件
    */
    public function multipartInit($file, $uploadPolicy, $start = 0, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array(), $meta = array(), $var = array())
    {
        return $this->upload_client->multipartInit($file, $uploadPolicy, $start, $sliceSize, $opts, $meta, $var);
    }

    /*
     * 创建分片上传任务,根据内容
    */
    public function multipartInitByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        return $this->upload_client->multipartInitByContent($content, $uploadPolicy, $opts, $meta, $var);
    }

    /*
     * 分片上传，根据文件
    */
    public function multipartUpload($file, $uploadPolicy, $start, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array())
    {
        return $this->upload_client->multipartUpload($file, $uploadPolicy, $start, $sliceSize, $opts);
    }

    /*
     * 分片上传，根据内容
    */
    public function multipartUploadByContent($content, $uploadPolicy, $opts = array())
    {
        return $this->upload_client->multipartUploadByContent($content, $uploadPolicy, $opts);
    }

    /*
     * 完成分片上传任务
    */
    public function multipartComplete($uploadPolicy, $md5Parts, $opts = array())
    {
        return $this->upload_client->multipartComplete($uploadPolicy, $md5Parts, $opts);
    }

    /*
     * 取消分片上传任务
    */
    public function multipartCancel($uploadPolicy, $opts = array())
    {
        return $this->upload_client->multipartCancel($uploadPolicy, $opts);
    }

    /*
     * 文件是否存在
    */
    public function existsFile($namespace, $dir, $file)
    {
        return $this->manage_client->existsFile($namespace, $dir, $file);
    }

    /*
     * 获取文件元数据
    */
    public function getFile($namespace, $dir, $file)
    {
        return $this->manage_client->getFile($namespace, $dir, $file);
    }

    /*
     * 获取文件列表
    */
    public function listFiles($namespace, $dir, $page = 1, $pageSize = 100)
    {
         return $this->manage_client->listFiles($namespace, $dir, $page, $pageSize);
    }

    /*
     * 删除文件
    */
    public function deleteFile($namespace, $dir, $file)
    {
        return $this->manage_client->deleteFile($namespace, $dir, $file);
    }
    
    /*
     * 文件夹是否存在
    */
    public function existsFolder($namespace, $dir)
    {
         return $this->manage_client->existsFolder($namespace, $dir);
    }

    /*
     * 创建文件夹
    */
    public function createDir($namespace, $dir)
    {
        return $this->manage_client->createDir($namespace, $dir);
    }

    /*
     * 获取子文件夹列表
    */
    public function listDirs($namespace, $dir, $page = 1, $pageSize = 100)
    {
         return $this->manage_client->listDirs($namespace, $dir, $page, $pageSize);
    }

    /*
     * 删除文件夹
    */
    public function deleteDir($namespace, $dir)
    {
        return $this->manage_client->deleteDir($namespace, $dir);
    }

    /*
     * 添加持久化任务
    */
    public function addPts($namespace, $dir, $file, $fops, $force=false,$notifyURL = null)
    {
        return $this->manage_client->addPts($namespace, $dir, $file, $fops, $force,$notifyURL);
    }
    
    /*
     * 查询持久化任务
    */
    public function getPts($namespace, $popId)
    {
        return $this->manage_client->getPts($namespace, $popId);
    }
}
