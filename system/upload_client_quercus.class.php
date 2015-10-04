<?php

if (! defined ( 'ALI_IMAGE_SDK_PATH' )) {
	define ( 'ALI_IMAGE_SDK_PATH', dirname ( __FILE__ ) );
}
require_once (ALI_IMAGE_SDK_PATH . '/conf/conf.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/upload_policy.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/mimetypes.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/utils/encode_utils.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/upload_client.class.php');
require_once (ALI_IMAGE_SDK_PATH . '/manage_client.class.php');

class UploadClientQuercus {
    private $upload_endpoint;
	private $ak;
	private $sk;
    private $type; // "CLOUD" or "TOP"; 
    private $tmpDir;

	public function __construct($ak, $sk, $type = "TOP", $upload_endpoint = Conf::UPLOAD_ENDPOINT) {
        $this->ak = $ak;
        $this->sk = $sk;
        $this->type = $type;
        $this->upload_endpoint = $upload_endpoint;

        $this->tmpDir = ALI_IMAGE_SDK_PATH . '/temp';
	}

    public function upload($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        if(!file_exists($file))
        {
            return $this->_errorResponse("FileNotExist", "file not exist");
        }
        
        // uploadPolicy 检查
        list($result, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$result)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy:".$message);
        }

        $fileSize = filesize($file);
        if($fileSize > Conf::SUB_OBJ_SIZE)
        {
            return $this->_errorResponse("FileIsLarge", "file is too large, use multi part upload please");
        }
        
        $fileMd5 = md5_file($file);
        $url = $this->upload_endpoint . Conf::UPLOAD_API_UPLOAD;
        $this->_setMetaVars('meta', $opts, $meta);
        $this->_setMetaVars('var', $opts, $var);
        $opts['md5'] = $fileMd5;
        $opts['size'] = $fileSize;
        $opts['content'] = "@".$file;
        
        return $this->_send_request('POST', $url, $uploadPolicy, $opts);
    }

    public function uploadByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        // uploadPolicy 检查
        list($result, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$result)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy:".$message);
        }
        
        list($s1, $s2) = explode(' ', microtime());
        $s1 =  sprintf('%03s', intval($s1*1000));
        $date = $s2.$s1;
        $tmpFile = $this->_getTempFile($uploadPolicy->name);
        file_put_contents($tmpFile, $content);
        $res = $this->upload($tmpFile, $uploadPolicy, $opts, $meta, $var);
        @unlink ($tmpFile);
        return $res;
    }

    public function uploadSuperfile($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        if(!file_exists($file))
        {
            return $this->_errorResponse("FileNotExist", "file not exist");
        }
        // uploadPolicy 检查
        list($result, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$result)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy".$message);
        }

        $md5_parts = array();
        
        $fileSize = filesize($file);
        $fileMd5 = md5_file($file);
        $subObjSize = isset($opts['sliceSize']) ? $opts['sliceSize'] : Conf::SUB_OBJ_SIZE;
        $sliceNum = intval(ceil($fileSize/$subObjSize));
        $uploadId = null;
        $id = null;
        $partNumber = 0;
        for($i = 0; $i < $sliceNum; $i ++) 
        {
            if (($i + 1) === $sliceNum) {
				//last sub object
				$size = (0 === $fileSize % $subObjSize) ? $subObjSize : ($fileSize % $subObjSize);
			} else {
				$size = $subObjSize;
			}

            $seekTo = $i * $subObjSize;
            $opts = array();
            $content = file_get_contents($file, 0, null, $seekTo, $size);
            $md5 = md5($content);
            $opts['md5'] = $md5;
            $opts['size'] = $size;
            $tmpFile = $this->_getTempFile($uploadPolicy->name);
            file_put_contents($tmpFile, $content);
            //  content 必须放在最后一位
            if(0 == $i)
            {
                // 初始化
                $this->_setMetaVars('meta', $opts, $meta);
                $this->_setMetaVars('var', $opts, $var);
                $opts['content'] = "@".$tmpFile;
                $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_INIT;
                $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
                $uploadId = isset($result['uploadId']) ? $result['uploadId'] : null;
                $id = isset($result['id']) ? $result['id']: null;
            }else{
                $opts['uploadId'] = $uploadId;
                $opts['id'] = $id;
                $opts['partNumber'] = $i + 1;
                $opts['content'] = "@".$tmpFile;
                $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_UPLOAD;
                $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
            }

            @unlink ($tmpFile);
            // 失败处理,cancel 掉
            if(!$result['isSuccess'])
            {
                // 记录上次失败原因
                $message = $result['message'];
                $code = $result['code'];
                $requestId = $result['requestId'];
                $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_CANCEL;
                $opts = array('id' => $id, 'uploadId' => $uploadId);
                $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);   // 不判断结果
                return $this->_errorResponse($code, "fail upload super file:". $message, $requestId);
            }

            // 记录下次使用
            $eTag = $result['eTag'];
            $partNumber = $result['partNumber'];
            array_push($md5_parts, array('eTag' => $eTag, 'partNumber' => $partNumber));
        }
            
        // 分片上传完成
        $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_COMPLETE;
        $parts = EncodeUtils::encodeWithURLSafeBase64(json_encode($md5_parts));
        $opts = array('id' => $id, 
                    'uploadId' => $uploadId, 
                    'md5' => $fileMd5,
                    'parts' => $parts);
        $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
        return $result;
    }
    
    public function multipartInit($file, $uploadPolicy, $start = 0, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array(), $meta = array(), $var = array())
    {
        if(!file_exists($file))
        {
            return $this->_errorResponse("FileNotExist", "file not exist");
        }
        $content = file_get_contents($file, 0, null, $start, $sliceSize);
        return $this->multipartInitByContent($content, $uploadPolicy, $opts, $meta, $var);
    }

    public function multipartInitByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
    {
        // uploadPolicy 检查
        list($pass, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$pass)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy".$message);
        }

        $tmpFile = $this->_getTempFile($uploadPolicy->name);
        $opts['size'] = strlen($content);
        $this->_setMetaVars('meta', $opts, $meta);
        $this->_setMetaVars('var', $opts, $var);
        // content 必须放在最后一位
        file_put_contents($tmpFile, $content);
        $opts['md5'] = md5($content);
        $opts['content'] = "@".$tmpFile;
        $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_INIT;
        $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
        @unlink ($tmpFile);
        return $result;
    }

    public function multipartUpload($file, $uploadPolicy, $start, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array())
    {
        if(!file_exists($file))
        {
            return $this->_errorResponse("FileNotExist", "file not exist");
        }
        $content = file_get_contents($file, 0, null, $start, $sliceSize);
        return $this->multipartUploadByContent($content, $uploadPolicy, $opts);
    }
    
    public function multipartUploadByContent($content, $uploadPolicy, $opts = array())
    {
        // uploadPolicy 检查
        list($pass, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$pass)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy".$message);
        }
        
        if(!isset($opts['id']) || !isset($opts['uploadId']) || !isset($opts['partNumber']))
        {
            return $this->_errorResponse("opts parms error", "please add id,uploadId and partNumber into opts");
        }

        $tmpFile = $this->_getTempFile($uploadPolicy->name);
        file_put_contents($tmpFile, $content);
        $opts['md5'] = md5($content);
        $opts['size'] = strlen($content);
        $opts['content'] = "@".$tmpFile;
        $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_UPLOAD;
        $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
        @unlink ($tmpFile);
        return $result;
    }

    public function multipartComplete($uploadPolicy, $md5Parts, $opts = array())
    {
        if(empty($md5Parts))
        {
            return $this->_errorResponse("Md5PartsEmpty", "slice md5 parts empty");
        }
        // uploadPolicy 检查
        list($pass, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$pass)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy:".$message);
        }
        if(!isset($opts['id']) || !isset($opts['uploadId']) || !isset($opts['md5']))
        {
            return $this->_errorResponse("opts parms error", "please add id,uploadId and md5(for file) into opts");
        }
        $parts = EncodeUtils::encodeWithURLSafeBase64(json_encode($md5Parts));
        $opts['parts'] = $parts;
        $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_COMPLETE;
        $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
        return $result;
    }

    public function multipartCancel($uploadPolicy, $opts = array())
    {
        // uploadPolicy 检查
        list($pass, $message) = $this->uploadPolicyCheck($uploadPolicy);
        if (!$pass)
        {
            return $this->_errorResponse("ErrorUploadPolicy", "error upload policy:".$message);
        }
        if(!isset($opts['id']) || !isset($opts['uploadId']))
        {
            return $this->_errorResponse("opts parms error", "please add id and uploadId into opts");
        }
        $url = $this->upload_endpoint . Conf::UPLOAD_API_BLOCK_CANCEL;
        $result = $this->_send_request('POST', $url, $uploadPolicy, $opts);
        return $result;
    }

    protected function _send_request($method, $url, $uploadPolicy, $opts= array(), $headers = NULL) 
    {
        $ch = $this->curlInit($url);

        $_headers = array('Expect:');
        $token = $this->_getUploadToken($uploadPolicy);
        array_push($_headers, "Authorization: {$token}");
        array_push($_headers, "User-Agent: {$this->_getUserAgent($token)}");

        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        $timeout = 30;
        if(isset($opts['timeout']))
        {
            $timeout = $opts['timeout'];
            unset($opts['timeout']);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'PUT' || $method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
        }
        else {
			curl_setopt($ch, CURLOPT_POST, 0);
        }

        #var_dump($opts);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($opts));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $body = '';
        $res = explode("\r\n\r\n", $response);
        $success = ($http_code == 200) ? true : false;
        $body = isset($res[1]) ? $res[1] : '';
        #var_dump("http_code $http_code response $body");
        $res = json_decode($body, true);
        $result = (empty($res)) ? array() : $res;
        $result['isSuccess'] = $success;
        return $result;
    }
   
    protected function _errorResponse($code = "UnknownError", $message = "unkonown error", $requestId = null)
    {
        return array(
            "isSuccess" => false,
            "code" => $code,
            "message" => $message,
            "requestId" => $requestId,
             );
    }
    
    protected function uploadPolicyCheck($uploadPolicy)
    {
        $result = true;
        $message = null;
        if(empty($uploadPolicy->bucket) && empty($uploadPolicy->namespace))
        {
            $result = false;
            $message = 'namespace and bucket is empty';
        }

        if(empty($uploadPolicy->name))
        {
            $result = false;
            $message = 'name is empty';
        }
            
        return array($result, $message);
    }

    protected function _getUserAgent($token) {
        if (strpos($token, "UPLOAD_AK_TOP") === 0) {
            return "ALIMEDIASDK_PHP_TAE/" . Conf::SDK_VERSION;
        } else {
            return "ALIMEDIASDK_PHP_CLOUD/" . Conf::SDK_VERSION;
        }
    }

    protected function _setMetaVars($prefix, $opts, $meta)
    {
        foreach($meta as $key => $val)
        {
            $key = $prefix . '-'.$key;
            $opts[$key] = $val;
        }

        return $opts;
    }
    
    protected function _getUploadToken($uploadPolicy)
    {
        $encodedPolicy = EncodeUtils::encodeWithURLSafeBase64(json_encode($uploadPolicy));
        $signed = hash_hmac( 'sha1', $encodedPolicy, $this->sk);
        $token = $this->ak . ":" . $encodedPolicy . ":" . $signed;
        $result = "UPLOAD_AK_" . $this->type . " " . EncodeUtils::encodeWithURLSafeBase64($token);
        return $result;
    }

    protected function _getTempFile($name)
    {
        list($s1, $s2) = explode(' ', microtime());
        $s1 =  sprintf('%03s', intval($s1*1000));
        $date = $s2.$s1;
        $file = $this->tmpDir . "/" . $this->sk . "-" . $date . "-" . rand(0,65536) . "-" . $name;
        return $file;
    }
    
    protected function curlInit($url)
    {
        if(function_exists('_ace_curl_init'))
        {
            return _ace_curl_init($url);
        }else{
            return curl_init($url);
        }
    }
}
