<?php
/**
 * SQLite3数据库操作类
 *
 * @package Z-BlogPHP
 * @subpackage ClassLib/DataBase 类库
 */
class DbSQLite3 implements iDataBase {

	public $type = 'sqlite';

	/**
	* @var string|null 数据库名前缀
	*/
	public $dbpre = null;
	private $db = null; #数据库连接实例
	/**
	* @var string|null 数据库名
	*/
	public $dbname = null;
	/**
	* @var DbSql|null 
	*/
	public $sql=null;
	/**
	* 构造函数，实例化$sql参数
	*/
	function __construct()
	{
		$this->sql=new DbSql($this);
	}

	/**
	* @param $s
	* @return string
	*/
	public function EscapeString($s){
		return SQLite3::escapeString($s);
	}

	/**
	* @param $array
	* @return bool
	*/
	function Open($array){
		if ($this->db = new SQLite3($array[0]) ){
			$this->dbpre=$array[1];
			$this->dbname=$array[0];
			return true;
		} else{
			return false;
		}
	}

	/**
	* 关闭数据库连接
	*/
	function Close(){
		$this->db->close();
	}

	/**
	* @param $s
	*/
	function QueryMulit($s){return $this->QueryMulti($s);}//错别字函数，历史原因保留下来
	function QueryMulti($s){
		//$a=explode(';',str_replace('%pre%', $this->dbpre, $s));
		$a=explode(';',$s);
		foreach ($a as $s) {
			$s=trim($s);
			if($s<>''){
				$this->db->query($this->sql->Filter($s));
			}
		}

	}

	/**
	* @param $query
	* @return array
	*/
	function Query($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		// 遍历出来
		$results =$this->db->query($this->sql->Filter($query));
		$data = array();
		if($results->numColumns()>0){
			while($row = $results->fetchArray()){
				$data[] = $row;
			}
		}else{
			$data[] = $results->numColumns();
		}
		return $data;
	}

	/**
	* @param $query
	* @return mixed
	*/
	function Update($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		return $this->db->query($this->sql->Filter($query));
	}

	/**
	* @param $query
	* @return mixed
	*/
	function Delete($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		return $this->db->query($this->sql->Filter($query));
	}

	/**
	* @param $query
	* @return mixed
	*/
	function Insert($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		$this->db->query($this->sql->Filter($query));
		return $this->db->lastInsertRowID();
	}

	/**
	* @param $table
	* @param $datainfo
	*/
	function CreateTable($table,$datainfo){
		$this->QueryMulit($this->sql->CreateTable($table,$datainfo));
	}

	/**
	* @param $table
	*/
	function DelTable($table){
		$this->QueryMulit($this->sql->DelTable($table));
	}

	/**
	* @param $table
	*/
	function ExistTable($table){

		$a=$this->Query($this->sql->ExistTable($table));
		if(!is_array($a))return false;
		$b=current($a);
		if(!is_array($b))return false;
		$c=(int)current($b);
		if($c>0){
			return true;
		}else{
			return false;
		}
	}
}
