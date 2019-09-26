<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database\Models\Mysql;

class Client
{
	protected $_parentObj=null;
	protected $_connUUID=null;
	
	public function setParent($dbObj)
	{
		$this->_parentObj	= $dbObj;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
	public function setDatabaseName($name)
	{
		$this->_databaseName	= $name;
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
		//Example: $dupAction[]	= array("column" => $colName, "action" => "if(VALUES(`".$colName."`) IS NOT NULL, VALUES(`".$colName."`), `".$colName."`)");
		return $this->getParent()->insert($this, $tableName, $rows, $dupAction);
	}
	public function update($tableName, $row, $query=null, $qPs=null)
	{
		return $this->getParent()->update($this, $tableName, $row, $query, $qPs);
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
}