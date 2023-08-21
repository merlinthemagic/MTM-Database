<?php
//� 2023 Martin Peter Madsen
namespace MTM\Database\Models\Mysql\Server;

abstract class Alpha extends \MTM\Utilities\Tools\Validations\V1
{
	protected $_hostname=null;
	protected $_dbUsername=null;
	protected $_dbPassword=null;
	protected $_dbPort=3306;
	
	protected $_debug=false;
	protected $_exRewrites=array();
	
	
	public function __construct()
	{
		//default exception rewrites
		$this->setExceptionRewrite("1062", "Duplicate Entry", 18775);//duplicate in unique index
		$this->setExceptionRewrite("42S01", "Table or view already exists", 18781);//duplicate table
		$this->setExceptionRewrite("40000", "Transaction rollback", 18786);//rollback
		
		$this->setExceptionRewrite("2006", "Database server has gone away", 18790);//MySQL server has gone away
		$this->setExceptionRewrite("08S01", "Database server shutdown in progress", 18791);//MySQL server is going away
		$this->setExceptionRewrite("1290", "Database server is read only", 18792);//MySQL server is going away
	}
	public function setHostname($hostname)
	{
		$this->_hostname	= $hostname;
	}
	public function getHostname()
	{
		return $this->_hostname;
	}
	public function getDebug()
	{
		return $this->_debug;
	}
	public function setDebug($bool)
	{
		$this->_debug	= $bool;
	}
	public function setConnectionDetail($username, $password, $port=3306)
	{
		$this->_dbUsername	= $username;
		$this->_dbPassword	= $password;
		$this->_dbPort		= $port;
		
		return $this;
	}
	public function setExceptionRewrite($dbCode, $exMsg="", $exCode=0)
	{
		//allows thrown exceptions to be rewritten to fit your needs
		if (is_int($dbCode) === false && is_string($dbCode) === false) {
			throw new \Exception("Invalid database error code");
		} elseif (is_string($exMsg) === false) {
			throw new \Exception("Invalid exception mesage");
		} elseif (is_int($exCode) === false) {
			throw new \Exception("Invalid exception code");
		} elseif (array_key_exists($dbCode, $this->_exRewrites) === false) {
			$this->_exRewrites[$dbCode]	= new \stdClass();
		}
		$dbCode			= (string) $dbCode;
		$obj			= $this->_exRewrites[$dbCode];
		$obj->dbCode	= $dbCode;
		$obj->exMsg		= $exMsg;
		$obj->exCode	= $exCode;
		
		return $this;
	}
}