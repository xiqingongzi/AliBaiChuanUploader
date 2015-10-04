本sdk基于http 接口请求开发
# 使用说明
## 统一说明
所有接口都是集成到AlibabaImage类中。
返回值为array结构。根据array中isSuccess判断是否调用成功。如果失败具体详细信息请参考code,message字段内容

类如
array(8) {
  ["id"]=>
  string(36) "6bec5bca-94f8-4192-afd4-1a7f8cafb254"
  ["eTag"]=>
  string(32) "85EDBFBAABAF0A7A6E5A8D15FFB9817F"
  ["uploadId"]=>
  string(32) "7BABCE44773A4AFDBFBD2E4A9317B475"
  ["dir"]=>
  string(6) "/super"
  ["requestId"]=>
  string(36) "bb66d0f4-e71c-4790-afa6-3f61ed77a773"
  ["name"]=>
  string(10) "superfile4"
  ["partNumber"]=>
  int(1)
  ["isSuccess"]=>
  bool(true)
}

如果调用返回错误：
array(4) {
  ["message"]=>
  string(13) "InternalError"
  ["requestId"]=>
  string(36) "390671e3-d2a8-4425-be76-c081119d5813"
  ["code"]=>
  string(13) "InternalError"
  ["isSuccess"]=>
  bool(false)
}


上传部分：
上传部分需要传入uploadPolicy对象，定义详见upload_policy.class.php。其中初始化type类型为TOP，namespace必填，CLOUD的话bucket必填。name字段必填
上传策略模型
字段 						说明
namespace 			存储服务ID，type = TOP 时必填
bucket 					存储bucket名，type = CLOUD 时必填
expiration 			过期时间,unixtime,单位毫秒
insertOnly 			是否只支持插入模式,0/1
dir 						文件上传的目录
name 						文件上传的文件名，文件名不能包含"/"。如果在这个设置目录则上传时传的name参数无效
sizeLimit 			上传文件大小限制,单位byte
detectMime 			是否自动检查文件mime信息
mimeLimit 			允许的mime信息
callbackUrl 		回调url,建议使用ip形式
callbackHost 		回调host
callbackBody 		回调body信息
callbackBodyType 	回到body类型 

## 初始化
require_once('xxx/alimage.class.class.php');
$image  = new AlibabaImage($ak, $sk, $type, $upload_endpoint, $manage_endpoint);
初始化传入参数:ak,sk是必选参数。type类型有TOP和CLCOUD两种，其中默认值=TOP。$upload_endpoint和$manage_endpoint一般不需要填写，使用默认值即可

/*
* 小文件上传，建议比较小的文件采用此种方式，10M以下
*/
public function upload($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
$file - 文件名
$uploadPloicy  - 上传策略
$opts - 可选项，包含上传超时时间timeout。不携带的话取默认值30秒
$meta - meta信息
$var - 自定义kv信息对

/*
* 小文件上传，建议比较小的文件采用此种方式，10M以下
*/
public function uploadByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
$content - 上传内容
$uploadPloicy  - 上传策略
$opts - 可选项，包含上传超时时间timeout。不携带的话取默认值30秒
$meta - meta信息
$var - 自定义kv信息对

/*
* 大文件上传，建议大的文件采用此种方式，只要文件大于100K都可以采用此种方式
*/
public function uploadSuperfile($file, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
$file - 文件名
$uploadPloicy  - 上传策略
$opts - 可选项，timeout 上传超时时间。不携带的话取默认值30秒
								sliceSize 表示分片大小，单位byte。如果没有此项参数表示分片大小默认10M。用户可以根据自己php解释器的能力取值。
$meta - meta信息
$var - 自定义kv信息对

/*
* 创建分片上传任务
*/
public function multipartInit($file, $uploadPolicy, $start = 0, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array(), $meta = array(), $var = array())
$file - 文件名
$uploadPloicy  - 上传策略
$start - 文件读取的起始位置
$sliceSize - 文件切割获取长度
$opts - 可选项，包含上传超时时间timeout。不携带的话取默认值30秒
$meta - meta信息
$var - 自定义kv信息对

/*
* 创建分片上传任务
*/
public function multipartInitByContent($content, $uploadPolicy, $opts = array(), $meta = array(), $var = array())
$content - 内容
$uploadPloicy  - 上传策略
$opts - 可选项，包含上传超时时间timeout。不携带的话取默认值30秒
$meta - meta信息
$var - 自定义kv信息对

/*
* 分片上传
*/
public function multipartUpload($file, $uploadPolicy, $start, $sliceSize = Conf::SUB_OBJ_SIZE, $opts = array())
$file - 文件名
$uploadPloicy  - 上传策略
$start - 文件读取的起始位置
$sliceSize - 文件切割获取长度
$opts - 其中必须填写multipartInit返回的uploadId，id，以及本次上传的partNumber
			可选项，包含上传超时时间timeout。不携带的话取默认值30秒

/*
* 分片上传
*/
public function multipartUploadByContent($content, $uploadPolicy, $opts = array())
$content - 内容
$uploadPloicy  - 上传策略
$opts - 其中必须填写multipartInit返回的uploadId，id，以及本次上传的partNumber
			可选项，包含上传超时时间timeout。不携带的话取默认值30秒

/*
* 完成分片上传任务
*/
public function multipartComplete($uploadPolicy, $md5Parts, $opts = array())
$uploadPloicy  - 上传策略
$md5Parts - 前面上传的所有文件md5信息汇总，array结构，具体见demo文件
$opts - 其中必须填写multipartInit返回的uploadId，id，以及整个文件的md5
					
/*
* 取消分片上传任务
*/
public function multipartCancel($uploadPolicy, $opts = array())
$uploadPloicy  - 上传策略
$md5Parts - 前面上传的所有文件md5信息汇总，array结构，具体构造见demo文件
$opts - 其中必须填写multipartInit返回的uploadId，id

/*
* 判断文件是否存在
*/
public function existsFile($namespace, $dir, $file);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同
$file - 文件名。文件名不能包含"/"。下同

/*
* 获取文件信息
*/
public function getFile($namespace, $dir, $file);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同
$file - 文件名。文件名不能包含"/"。下同

/*
* 列出文件夹下文件列表
*/
public function listFiles($namespace, $dir);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同

/*
* 删除文件
*/
public function deleteFile($namespace, $dir, $file);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同
$file - 文件名。文件名不能包含"/"。下同

/*
* 创建文件夹
*/

public function createDir($namespace, $dir);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同

/*
* 列出文件夹列表
*/
public function listDirs($namespace, $dir);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同

/*
* 判断文件夹是否存在
*/
public function existsFolder($namespace, $dir);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同

/*
* 删除文件夹
*/
public function deleteDir($namespace, $dir);
$namespace - 命名空间，当TYPE 是 CLOUD的时赋值bucket的内容，下同
$dir - 文件夹。文件上传的目录，必须以"/"开头，不以"/"结尾。下同

