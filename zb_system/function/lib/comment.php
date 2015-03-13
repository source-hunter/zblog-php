<?php
/**
 * 评论类
 *
 * @package Z-BlogPHP
 * @subpackage ClassLib/Article 类库
 */
class Comment extends Base {

	/**
	* @var bool 是否丢弃，如通过插件等判断为垃圾评论则标记为true
	*/
	public $IsThrow=false;
	/**
	* @var int 评论层号
	*/
	public $FloorID=0;

	/**
	* 构造函数
	*/
	function __construct()
	{
		global $zbp;
		parent::__construct($zbp->table['Comment'],$zbp->datainfo['Comment']);
	}

	/**
	* 魔术方法：重载，可通过接口Filter_Plugin_Comment_Call添加自定义函数
	* @param string $method 方法
	* @param mixed $args 参数
	* @return mixed
	*/
	function __call($method, $args) {
		foreach ($GLOBALS['Filter_Plugin_Comment_Call'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($this,$method, $args);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {$fpsignal=PLUGIN_EXITSIGNAL_NONE;return $fpreturn;}
		}
	}

	/**
	* 获取评论楼号
	* @param int $parentid 父评论ID
	* @return array|int|mixed
	*/
	static public function GetRootID($parentid){
		global $zbp;
		if($parentid==0)return 0;
		$c = $zbp->GetCommentByID($parentid);
		if($c->RootID==0){
			return $c->ID;
		}else{
			return $c->RootID;
		}
	}


	/**
	* 评论时间
	* @param string $s 时间格式
	* @return bool|string
	*/
	public function Time($s='Y-m-d H:i:s'){
		return date($s,(int)$this->PostTime);
	}

	/**
	* @param $name
	* @param $value
	* @return null
	*/
	public function __set($name, $value)
	{
		global $zbp;
		if ($name=='Author') {
			return null;
		}
		if ($name=='Comments') {
			return null;
		}
		if ($name=='Level') {
			return null;
		}
		if ($name=='Post') {
			return null;
		}
		parent::__set($name, $value);
	}

	/**
	* @param $name
	* @return array|int|Member|mixed
	*/
	public function __get($name)
	{
		global $zbp;
		if ($name=='Author') {
			$m=$zbp->GetMemberByID($this->AuthorID);
			if($m->ID==0){
				$m->Name=$this->Name;
				$m->Alias=$this->Name;
				$m->Email=$this->Email;
				$m->HomePage=$this->HomePage;
			}
			return $m;
		}
		if ($name=='Comments') {
			$array=array();
			foreach ($zbp->comments as $comment) {
				if($comment->ParentID==$this->ID){
					$array[]=&$zbp->comments[$comment->ID];
				}
			}
			return $array;
		}
		if ($name=='Level') {
			if($this->ParentID==0){return 0;}

			$c1=$zbp->GetCommentByID($this->ParentID);
			if($c1->ParentID==0){return 1;}

			$c2=$zbp->GetCommentByID($c1->ParentID);
			if($c2->ParentID==0){return 2;}

			$c3=$zbp->GetCommentByID($c2->ParentID);
			if($c3->ParentID==0){return 3;}

			return 4;
		}
		if ($name=='Post') {
			$p=$zbp->GetPostByID($this->LogID);
			return $p;
		}
		return parent::__get($name);
	}

	/**
	* 保存评论数据
	* @return bool
	*/
	function Save(){
		global $zbp;
		foreach ($GLOBALS['Filter_Plugin_Comment_Save'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($this);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {$fpsignal=PLUGIN_EXITSIGNAL_NONE;return $fpreturn;}
		}
		return parent::Save();
	}

	/**
	 * @return bool
	 */
	function Del(){
		foreach ($GLOBALS['Filter_Plugin_Comment_Del'] as $fpname => &$fpsignal) {
			$fpreturn=$fpname($this);
			if ($fpsignal==PLUGIN_EXITSIGNAL_RETURN) {$fpsignal=PLUGIN_EXITSIGNAL_NONE;return $fpreturn;}
		}
		return parent::Del();
	}
}
