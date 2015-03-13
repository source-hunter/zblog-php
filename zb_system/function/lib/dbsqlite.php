<?php
/**
 * SQLite2数据库操作类
 *
 * @package Z-BlogPHP
 * @subpackage ClassLib/DataBase 类库
 */
class DbSQLite implements iDataBase {

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
		return sqlite_escape_string($s);
	}

	/**
	* @param $array
	* @return bool
	*/
	function Open($array){
		if ($this->db = sqlite_open($array[0], 0666, $sqliteerror)) {
			$this->dbpre=$array[1];
			$this->dbname=$array[0];
			return true;
		} else {
			return false;
		}
	}

	/**
	* 关闭数据库连接
	*/
	function Close(){
		sqlite_close($this->db);
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
				sqlite_query($this->db,$this->sql->Filter($s));
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
		$results = sqlite_query($this->db,$this->sql->Filter($query));
		$data = array();
		if(is_resource($results)){
			while($row = sqlite_fetch_array($results)){
				$data[] = $row;
			}
		}else{
			$data[] = $results;
		}
		return $data;

	}

	/**
	* @param $query
	* @return SQLiteResult
	*/
	function Update($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		return sqlite_query($this->db,$this->sql->Filter($query));
	}

	/**
	* @param $query
	* @return SQLiteResult
	*/
	function Delete($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		return sqlite_query($this->db,$this->sql->Filter($query));
	}

	/**
	* @param $query
	* @return int
	*/
	function Insert($query){
		//$query=str_replace('%pre%', $this->dbpre, $query);
		sqlite_query($this->db,$this->sql->Filter($query));
		return sqlite_last_insert_rowid($this->db);
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
	* @return bool
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
