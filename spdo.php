<?php
class SPDO extends PDO{
	private static $instance = null;
	public function __construct(){
		$connect = 'pgsql:host=localhost;port=5432;dbname=productos';
		parent::__construct($connect,'postgres','1234');
		parent::setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}
	public static function singleton(){
		if(self::$instance == null){
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function __destruct(){ self::$instance = null; }
}
?>