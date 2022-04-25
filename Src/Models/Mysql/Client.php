<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database\Models\Mysql;

class Client
{
	protected $_parentObj=null;
	protected $_connUUID=null;
	
	public function __destruct()
	{
		$this->terminate();
	}
	public function setParent($dbObj)
	{
		$this->_parentObj	= $dbObj;
		return $this;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
	public function setDatabaseName($name)
	{
		$this->_databaseName	= $name;
		return $this;
	}
	public function getDatabaseName()
	{
		return $this->_databaseName;
	}
	public function getAll($query, $qPs=null)
	{
		return $this->getParent()->getAll($this, $query, $qPs);
	}
	public function getRow($query, $qPs=null)
	{
		return $this->getParent()->getRow($this, $query, $qPs);
	}
	public function getCell($query, $qPs=null)
	{
		return $this->getParent()->getCell($this, $query, $qPs);
	}
	public function insert($tableName, $rows, $dupAction=false)
	{
		//$dupAction === false -- duplicate will throw
		//$dupAction === true -- duplicate will be ignored
		//$dupAction === array(...) -- duplicate will update
		//Example1: $dupAction[]	= array("column" => $colName, "action" => "if(VALUES(`".$colName."`) IS NOT NULL, VALUES(`".$colName."`), `".$colName."`)");
		//Example2: $dupAction[]	= array("column" => "lastDate", "action" => "2022/05/11 01:13:24"); on duplicate key update column lastDate to 2022/05/11 01:13:24 for this row
		return $this->getParent()->insert($this, $tableName, $rows, $dupAction);
	}
	public function update($tableName, $row, $query=null, $qPs=null)
	{
		return $this->getParent()->update($this, $tableName, $row, $query, $qPs);
	}
	public function inStatement($inVals=array(), $qPs=array())
	{
		//not finished
		//helper for IN () statements
// 		$wh		= array();
// 		foreach ($inVals as $inVal) {
// 			$nv			= str_replace(" ", "_", ":inVal".$inVal);
// 			$where[]	= $nv;
// 			$qPs[$nv]	= $inVal;
// 		}
// 		$rObj			= new \stdClass();
// 		$rObj->where	= implode(", ", $where);
// 		$rObj->qPs		= $qPs;
// 		return $rObj;
		//USE:
		//$rObj		= $dbConn->inStatement(array(1,2,3,4,5));
		//$dbConn->update("tableName", array("attr"=> $value), "id IN ( $rObj->where )", $rObj->qPs);
		
	}
	public function delete($tableName, $query=null, $qPs=null)
	{
		return $this->getParent()->delete($this, $tableName, $query, $qPs);
	}
	public function setSlowLog($enabled=false, $time=3, $filePath=null)
	{
		return $this->getParent()->setSlowLog($this, $enabled, $time, $filePath);
	}
	public function getUUID()
	{
		if ($this->_connUUID === null) {
			$this->_connUUID		= uniqid("", true);
		}
		return $this->_connUUID;
	}
	public function terminate()
	{
		if (is_object($this->_parentObj) === true) {
			$this->_parentObj->terminateClient($this);
		}
		return $this;
	}
}