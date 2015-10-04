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

class ManageClient {
    private $upload_endpoint;
	private $ak;
	private $sk;
    private $type;

	public function __construct($ak, $sk, $type = "TOP", $upload_endpoint = Conf::MANAGE_ENDPOINT) {
        $this->ak = $ak;
        $this->sk = $sk;
        $this->type = $type;
        $this->upload_endpoint = $upload_endpoint;
	}

    public function existsFile($namespace, $dir, $file)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/files/' . $this->_buildResourceId($namespace, $dir, $file) . '/exists';
        return $this->_send_request('GET', $uri, $namespace);
    }

    public function getFile($namespace, $dir, $file)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/files/' . $this->_buildResourceId($namespace, $dir, $file);
        return $this->_send_request('GET', $uri, $namespace);
    }

    public function listFiles($namespace, $dir, $page = 1, $pageSize = 100)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/files';

        $namespaceKey = $this->_getNamespaceKey();
        $opts = array(
                    "dir" => $dir,
                    "currentPage" => $page,
                    "pageSize" => $pageSize,
                    $namespaceKey => $namespace,
                );
        return $this->_send_request('GET', $uri, $namespace, $opts);
    }

    public function deleteFile($namespace, $dir, $file)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/files/' . $this->_buildResourceId($namespace, $dir, $file);
        return $this->_send_request('DELETE', $uri, $namespace);
    }

    public function existsFolder($namespace, $dir)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/folders/' . $this->_buildResourceId($namespace, $dir, null) . '/exists';
        return $this->_send_request('GET', $uri, $namespace);
    }

    public function createDir($namespace, $dir)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/folders/' . $this->_buildResourceId($namespace, $dir, null);
        return $this->_send_request('POST', $uri, $namespace);
    }

    public function listDirs($namespace, $dir, $page = 1, $pageSize = 100)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/folders';
        $namespaceKey = $this->_getNamespaceKey();
        $opts = array(
                    "dir" => $dir,
                    "currentPage" => $page,
                    "pageSize" => $pageSize,
                    $namespaceKey => $namespace,
                );
        return $this->_send_request('GET', $uri, $namespace, $opts);
    }

    public function deleteDir($namespace, $dir)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/folders/' . $this->_buildResourceId($namespace, $dir, null);
        return $this->_send_request('DELETE', $uri, $namespace);
    }
    
    public function addPts($namespace, $dir, $file, $fops, $force=false,$notifyURL = null)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/pts';

        $resourceId = $this->_buildResourceId($namespace, $dir, $file);
        //$outerResourceId = $this->_buildResourceId($namespace, $outerDir, $outerFile);
        $opts['resourceId'] = $resourceId;
        $opts['fops'] = $fops;
        if(!empty($notifyURL))
        {
            $opts['notifyURL'] = urlencode($opts['notifyURL']);
        }

        $opts['force'] = ($force==false) ? 0 : 1;
        
        return $this->_send_request('POST', $uri, $namespace, $opts);
    }
    
    public function getPts($namespace, $popId)
    {
        $uri = '/' . Conf::MANAGE_API_VERSION . '/pts/' . $popId;
        return $this->_send_request('GET', $uri, $namespace);
    }
    
    protected function _send_request($method, $uri, $namespace, $opts = array(), $headers = NULL) 
    {
        ksort($opts);
        if(!empty($opts) && $method == "GET")
        {
            $uri = "{$uri}?";
            $andStr = "";
            foreach($opts as $key => $val)
            {
                $uri = $uri . $andStr . $key . "=" . urlencode($val);
                $andStr = "&";
            }
            $opts = array();
        }
        $_headers = array('Expect:');
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        list($s1, $s2) = explode(' ', microtime());
        $s1 =  sprintf('%03s', intval($s1*1000));
        $date = $s2.$s1;
        array_push($_headers, "Date: {$date}");
        $authorization = $this->_getAuthorization($uri, $date, $opts);
        array_push($_headers, "Authorization: {$authorization}");
        array_push($_headers, "User-Agent: {$this->_getUserAgent()}");

        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }
        $url = $this->_get_manage_url($uri);
        $ch = $this->curlInit($url);

        $length = 0;
        if (!empty($opts))
        {
            $body = '';
            $andStr = '';
            foreach($opts as $key => $val)
            {
                $body = $body . $andStr . $key . "=" . urlencode($val);
                $andStr = "&";
            }
            $length = @strlen($body);
            //array_push($_headers, "Content-Type: {$contentType}");
            array_push($_headers, "Content-Length: {$length}");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }else {
            array_push($_headers, "Content-Length: {$length}");
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $body = '';
        $res = explode("\r\n\r\n", $response);
        #var_dump($res);
        $success = ($http_code == 200) ? true : false;
        $body = isset($res[1]) ? $res[1] : '';
        #var_dump("http_code $http_code response $body");
        $res = json_decode($body, true);
        $result = (empty($res)) ? array() : $res;
        $result['isSuccess'] = $success;
        return $result;
    }

    protected function _getUserAgent() 
    {
        if ($this->type == "TOP") 
        {
            return "ALIMEDIASDK_PHP_TAE/" . Conf::SDK_VERSION;
        } else {
            return "ALIMEDIASDK_PHP_CLOUD/" . Conf::SDK_VERSION;
        }
    }

    protected function _get_manage_url($uri)
    {
        return Conf::MANAGE_ENDPOINT . $uri;
    }

    protected function _getNamespaceKey() {
        if ($this->type == "TOP") {
            return "namespace";
        } else {
            return "bucketName";
        }
    }
    
    protected function _getAuthorization($uri, $date, $opts)
	{      
		$query_string = '';
		foreach ($opts as $key => $value) {
			$query_string = $query_string . $key . "=" . urlencode($value) . "&";
		}
        if(strlen($query_string) > 2)
        {
            $query_string = substr($query_string, 0, strlen($query_string)-1);
        }

        $sb = "$uri\n$query_string\n$date";
        $string2BeSigned = $this->ak . ":" . hash_hmac( 'sha1', $sb, $this->sk);
        return "ACL_" . $this->type . " " . EncodeUtils::encodeWithURLSafeBase64($string2BeSigned);
	}

    protected function _buildResourceId($namespace, $dir, $name) {
        $jsonData = array();
        array_push($jsonData, $namespace);
        array_push($jsonData, $dir);
        array_push($jsonData, $name);
        return EncodeUtils::encodeWithURLSafeBase64(json_encode($jsonData));
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
