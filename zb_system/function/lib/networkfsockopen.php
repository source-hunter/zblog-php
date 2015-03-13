<?php
/**
 * sock类
 *
 * @package Z-BlogPHP
 * @subpackage ClassLib/Network 网络连接
 */
class Networkfsockopen implements iNetwork {

	private $readyState = 0;		#状态
	private $responseBody = NULL;   #返回的二进制
	private $responseStream = NULL; #返回的数据流
	private $responseText = '';	 #返回的数据
	private $responseXML = NULL;	#尝试把responseText格式化为XMLDom
	private $status = 0;			#状态码
	private $statusText = '';	   #状态码文本
	private $responseVersion = '';  #返回的HTTP版体

	private $option = array();
	private $url = '';
	private $postdata = array();
	private $httpheader = array();
	private $responseHeader = array();
	private $parsed_url = array();
	private $timeout = 30;
	private $errstr = '';
	private $errno = 0;
	private $isgzip = false;
	private $maxredirs = 0;
	private $canreinit = true;

	/**
	 * @param $property_name
	 * @param $value
	 * @throws Exception
	 */
	public function __set($property_name, $value){
		throw new Exception($property_name.' readonly');
	}

	/**
	 * @param $property_name
	 * @return mixed
	 */
	public function __get($property_name){
		if(strtolower($property_name)=='responsexml'){
			$w = new DOMDocument();
			return $w->loadXML($this->responseText);
		}elseif(strtolower($property_name)=='scheme'||
				strtolower($property_name)=='host'||
				strtolower($property_name)=='port'||
				strtolower($property_name)=='user'||
				strtolower($property_name)=='pass'||
				strtolower($property_name)=='path'||
				strtolower($property_name)=='query'||
				strtolower($property_name)=='fragment'){
			if(isset($this->parsed_url[strtolower($property_name)]))return $this->parsed_url[strtolower($property_name)];
		}
		else{
			return $this->$property_name;
		}
	}

	/**
	 *
	 */
	public function abort(){

	}

	/**
	 * @return string
	 */
	public function getAllResponseHeaders(){
		return implode("\r\n",$this->responseHeader);
	}

	/**
	 * @param $bstrHeader
	 * @return string
	 */
	public function getResponseHeader($bstrHeader){
		$name=strtolower($bstrHeader);
		foreach($this->responseHeader as $w){
			if(strtolower(substr($w,0,strpos($w,':')))==$name){
				return substr(strstr($w,': '),2);
			}
		}
		return '';
	}

	/**
	 * @param $resolveTimeout
	 * @param $connectTimeout
	 * @param $sendTimeout
	 * @param $receiveTimeout
	 */
	public function setTimeOuts($resolveTimeout,$connectTimeout,$sendTimeout,$receiveTimeout){

	}

	/**
	 * @param $bstrMethod
	 * @param $bstrUrl
	 * @param bool $varAsync
	 * @param string $bstrUser
	 * @param string $bstrPassword
	 * @return bool
	 * @throws Exception
	 */
	public function open($bstrMethod, $bstrUrl, $varAsync=true, $bstrUser='', $bstrPassword=''){ //Async无用
		//初始化变量
		$this->reinit();
		$method=strtoupper($bstrMethod);
		$this->option['method'] = $method;

		$this->parsed_url = parse_url($bstrUrl);
		if(!$this->parsed_url)
		{
			throw new Exception('URL Syntax Error!');
		}
		else{
			//bstrUser & bstrPassword ?
			if(!isset($this->parsed_url['port'])){
				if($this->parsed_url['scheme']=='https'){
					$this->parsed_url['port'] = 443;
				}else{
					$this->parsed_url['port'] = 80;
				}
			}
		}

		return true;
	}

	/**
	 * @param string $varBody
	 * @return mixed|void
	 */
	public function send($varBody=''){
		$data=$varBody;
		if(is_array($data)){
			$data=http_build_query($data);
		}

		if($this->option['method']=='POST')
		{

			if($data==''){
				$data=http_build_query($this->postdata);
			}
			$this->option['content'] = $data;

			$this->httpheader[] = 'Content-Type: application/x-www-form-urlencoded';
			$this->httpheader[] = 'Content-Length: ' . strlen($data);

		}

		$this->httpheader[] = 'Host: ' . $this->parsed_url['host'];
		//$this->httpheader[] = 'Referer: ' . 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$this->httpheader[] = 'Connection: close';

		if(!isset($this->httpheader['Accept'])){
			if(isset($_SERVER['HTTP_ACCEPT'])){
				$this->httpheader['Accept']='Accept:' . $_SERVER['HTTP_ACCEPT'];
			}
		}

		if(!isset($this->httpheader['Accept-Language'])){
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
				$this->httpheader['Accept-Language']='Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			}
		}

		if($this->isgzip == true){
			$this->httpheader['Accept-Encoding']='Accept-Encoding: gzip';
		}

		$this->option['header'] = implode("\r\n",$this->httpheader);

		ZBlogException::SuspendErrorHook();
		$socket = fsockopen(
					($this->scheme=='https'?'ssl://':'') . $this->parsed_url['host'],
					$this->port,
					$this->errno,
					$this->errstr,
					$this->timeout
				  );
		ZBlogException::SuspendErrorHook();
		if(!$socket)return ;

		$url = $this->option['method'] . ' ' . $this->parsed_url['path'];

		if(isset($this->parsed_url["query"]))
		{
			$url.= "?" . $this->parsed_url["query"];
		}
		fwrite($socket,
			   $url . ' HTTP/1.1' . "\r\n"
		);
		fwrite($socket,$this->option['header']."\r\n");
		fwrite($socket,"\r\n");
		if(isset($this->option['content'])){
			fwrite($socket,$this->option['content']."\r\n");
			fwrite($socket,"\r\n");
		}

		while (!feof($socket))
		{
			$this->responseText .= fgets($socket,128);
		}

		$this->responseHeader = substr($this->responseText,0,strpos($this->responseText, "\r\n\r\n"));

		$this->responseText = substr($this->responseText, strpos($this->responseText, "\r\n\r\n") + 4);

		$this->responseHeader = explode("\r\n",$this->responseHeader);

		$i=$this->maxredirs;
		if($this->maxredirs>0){
			if (strstr($this->responseHeader[0],' 301 ') ||
				strstr($this->responseHeader[0],' 302 ') ||
				strstr($this->responseHeader[0],' 303 ') ||
				strstr($this->responseHeader[0],' 307 ')
				){
				fclose($socket);
				$url = $this->getResponseHeader('Location');
				$this->canreinit=false;
				$this->open('Get',$url);
				$this->setMaxRedirs($i-1);
				$this->canreinit=true;
				return $this->send();
			}
		}

		if($this->getResponseHeader('Transfer-Encoding')=='chunked'){
			if(!function_exists('http_chunked_decode')){
				$this->responseText=$this->http_chunked_decode($this->responseText);
			}else{
				$this->responseText=http_chunked_decode($this->responseText);
			}
		}

		if($this->getResponseHeader('Content-Encoding')=='gzip'){
			if(!function_exists('gzdecode')){
				$this->responseText=$this->gzdecode($this->responseText);
			}else{
				$this->responseText=gzdecode($this->responseText);
			}
		}
		
		if(isset($this->responseHeader[0])){
			$this->statusText=$this->responseHeader[0];
			$a=explode(' ',$this->statusText);
			if(isset($a[0]))$this->responseVersion=$a[0];
			if(isset($a[1]))$this->status=$a[1];
			unset($this->responseHeader[0]);
		}
		
		fclose($socket);

	}

	/**
	 * @param $bstrHeader
	 * @param $bstrValue
	 * @param bool $append
	 * @return bool
	 */
	public function setRequestHeader($bstrHeader, $bstrValue, $append=false){
		if($append==false){
			$this->httpheader[$bstrHeader]=$bstrHeader.': '.$bstrValue;
		}else{
			if(isset($this->httpheader[$bstrHeader])){
				$this->httpheader[$bstrHeader] = $this->httpheader[$bstrHeader].$bstrValue;
			}else{
				$this->httpheader[$bstrHeader]=$bstrHeader.': '.$bstrValue;
			}
		}
		return true;
	}

	/**
	 * @param $bstrItem
	 * @param $bstrValue
	 */
	public function add_postdata($bstrItem, $bstrValue){
		array_push($this->postdata,array(
			$bstrItem => $bstrValue
		));
	}

	/**
	 *
	 */
	private function reinit(){
		global $zbp;
		$this->httpheader = array();
	
		if(!$this->canreinit)return;
		$this->readyState = 0;		#状态
		$this->responseBody = NULL;   #返回的二进制
		$this->responseStream = NULL; #返回的数据流
		$this->responseText = '';	 #返回的数据
		$this->responseXML = NULL;	#尝试把responseText格式化为XMLDom
		$this->status = 0;			#状态码
		$this->statusText = '';	   #状态码文本

		$this->option = array();
		$this->url = '';
		$this->postdata = array();
		$this->responseHeader = array();
		$this->parsed_url = array();
		$this->timeout = 30;
		$this->errstr = '';
		$this->errno = 0;

		$this->setRequestHeader('User-Agent','Mozilla/5.0 ('.$zbp->cache->system_environment.') Z-BlogPHP/' . ZC_BLOG_VERSION);
		$this->setMaxRedirs(1);
	}

	/**
	 * @param $chunk
	 * @return null|string
	 */
	private function http_chunked_decode($chunk) {
		$pos = 0; 
		$len = strlen($chunk); 
		$dechunk = null; 

		while(($pos < $len) 
			&& ($chunkLenHex = substr($chunk,$pos, ($newlineAt = strpos($chunk,"\n",$pos+1))-$pos)))
		{ 
			if (! $this->is_hex($chunkLenHex)) { 
				trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
				return $chunk; 
			} 

			$pos = $newlineAt + 1; 
			$chunkLen = hexdec(rtrim($chunkLenHex,"\r\n")); 
			$dechunk .= substr($chunk, $pos, $chunkLen); 
			$pos = strpos($chunk, "\n", $pos + $chunkLen) + 1; 
		} 
		return $dechunk; 
	} 

	/** 
	 * determine if a string can represent a number in hexadecimal 
	 * 
	 * @param string $hex 
	 * @return boolean true if the string is a hex, otherwise false 
	 */ 
	private function is_hex($hex) { 
		// regex is for weenies 
		$hex = strtolower(trim(ltrim($hex,"0"))); 
		if (empty($hex)) { $hex = 0; }; 
		$dec = hexdec($hex); 
		return ($hex == dechex($dec)); 
	}

	/**
	 * @param $string
	 * @return string
	 */
	private function gzdecode($string) {// no support for 2nd argument
		return file_get_contents('compress.zlib://data:zbp/ths;base64,'. base64_encode($string));
	}

	/**
	 *
	 */
	public function enableGzip(){
		$this->isgzip = true;
	}

	/**
	 * @param int $n
	 */
	public function setMaxRedirs($n=0){
		$this->maxredirs=$n;
	}
}
