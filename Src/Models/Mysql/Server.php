<?php
//� 2019 Martin Peter Madsen
namespace MTM\Database\Models\Mysql;

class Server
{
	protected $_adapObjs=array();
	protected $_hostname=null;
	protected $_dbUsername=null;
	protected $_dbPassword=null;
	protected $_dbPort=3306;
	
	protected $_debug=false;
	protected $_exRewrites=array();
	
	public function __construct()
	{
		//default exception rewrites
		$this->setExceptionRewrite(1062, "Duplicate Entry", 18775);//duplicate in unique index
		$this->setExceptionRewrite("42S01", "Table or view already exists", 18781);//duplicate in unique index
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
		$obj			= $this->_exRewrites[$dbCode];
		$obj->dbCode	= $dbCode;
		$obj->exMsg		= $exMsg;
		$obj->exCode	= $exCode;

		return $this;
	}
	public function getNewClient($dbName)
	{
		$rObj	= new \MTM\Database\Models\Mysql\Client();
		if ($dbName !== null) {
			$rObj->setDatabaseName($dbName);
		}
		$rObj->setParent($this);
		return $rObj;
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
	protected function getAdaptor($connObj, $resursive=false)
	{
	    try {

	    	if (array_key_exists($connObj->getGuid(), $this->_adapObjs) === true) {
    			
	    		$adapObj	= $this->_adapObjs[$connObj->getGuid()];
	    		
	    		//adaptor exists, lets make sure it is still valid
	    		$tOut	= ($adapObj->last + $adapObj->maxWait - 5) - time();
	    		if ($tOut < 0) {
	    			//our last request was a long time ago
	    			//so the connection may have timed out, do a more complete test
	    			try {

	    				//dont use the conn obj as it will end in a endless loop
	    				$adapObj->adaptor->query("SELECT 1");
	    				
	    			} catch (\Exception $e) {
	    				unset($this->_adapObjs[$connObj->getGuid()]);
	    				$this->exceptionHandler($e);

	    			} catch (\PDOException $e) {
	    				//no good, redo the adaptor
	    				unset($this->_adapObjs[$connObj->getGuid()]);
	    				if ($resursive === false) {
	    					return $this->getAdaptor($connObj, true);
	    				} else {
	    					$this->exceptionHandler($e);
	    				}
	    			}
	    		}
    
    		} else {
    			
    			if ($connObj->getDatabaseName() === null) {
    				throw new \Exception("Default database name must be set");
    			}
    			$e	= null;
    			try {
    				
    				//Install PDO classes on CentOS: yum install php-mysqlnd --enablerepo=remi,epel
    				//stop from raising errors
    				$adaptor = new \PDO("mysql:host=".$this->_hostname.":".$this->_dbPort.";dbname=".$connObj->getDatabaseName(), $this->_dbUsername, $this->_dbPassword);
    				//use exceptions
    				$adaptor->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    				$adaptor->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    				
    				$adapObj				= new \stdClass();
    				$adapObj->init			= time();
    				$adapObj->last			= time();
    				$adapObj->trans			= time();
    				$adapObj->adaptor		= $adaptor;
    				$adapObj->maxWait		= 28800;
    				$adapObj->maxPacket		= 1048576;
    				$adapObj->maxPrepCount	= 16382;
    				
    				
    				$query		= "SHOW GLOBAL VARIABLES LIKE \"wait_timeout\"";
    				$stmt		= $adapObj->adaptor->prepare($query);
    				$stmt->execute();
    				$row		= $stmt->fetch(\PDO::FETCH_ASSOC);
    				if ($row !== false) {
    					$adapObj->maxWait		= intval($row["Value"]);
    				}
    				$query		= "SHOW GLOBAL VARIABLES LIKE \"max_allowed_packet\"";
    				$stmt		= $adapObj->adaptor->prepare($query);
    				$stmt->execute();
    				$row		= $stmt->fetch(\PDO::FETCH_ASSOC);
    				if ($row !== false) {
    					$adapObj->maxPacket		= intval($row["Value"]);
    				}
    				
    				$query		= "SELECT @@max_prepared_stmt_count AS Count";
    				$stmt		= $adapObj->adaptor->prepare($query);
    				$stmt->execute();
    				$row		= $stmt->fetch(\PDO::FETCH_ASSOC);
    				if ($row !== false) {
    					$adapObj->maxPrepCount	= intval($row["Count"]);
    				}
    				
    				$this->_adapObjs[$connObj->getGuid()]	= $adapObj;
    				
    			} catch (\Exception $e) {
    				switch ($e->getCode()) {
    					case 2002:
    						if (strpos(strtolower($e->getMessage()), "getaddrinfo failed") !== false) {
    							//DNS resolution error
    							throw new \Exception("Cannot resolve database host: " . $this->_hostname);
    						} else {
    							throw $e;
    						}
    						break;
    					default:
    						throw $e;
    				}
    			} catch (\PDOException $e) {
    				$this->exceptionHandler($e);
    			}
    		} 
    		return $adapObj;
    		
	    } catch (\Exception $e) {
	        $this->exceptionHandler($e);
	    } catch (\PDOException $e) {
	    	$this->exceptionHandler($e);
	    }
	}
	public function terminateClient($connObj)
	{
		if (array_key_exists($connObj->getGuid(), $this->_adapObjs) === true) {
			$adapObj	= $this->_adapObjs[$connObj->getGuid()];
			unset($this->_adapObjs[$connObj->getGuid()]);
			try {
				$adapObj->adaptor->query("KILL CONNECTION_ID()");
			} catch (\Exception $e) {
				//nothing, its just an interupted execution (pid went away)
			}
		}
		return $this;
	}
	public function getMaxPacket($connObj)
	{
		return $this->getAdaptor($connObj)->maxPacket;
	}
	public function getMaxPreparedCount($connObj)
	{
		return $this->getAdaptor($connObj)->maxPrepCount;
	}
	public function getTransaction($connObj)
	{
		return $this->getAdaptor($connObj)->trans;
	}
	
	//Select
	public function getAll($connObj, $query, $qPs)
	{
		try {

			$adapObj		= $this->getAdaptor($connObj);
			$adapObj->last	= time();
			$stmt			= $adapObj->adaptor->prepare($query);
			if ($qPs === null) {
				$stmt->execute();
			} else {
				//use parameters
				$stmt->execute($qPs);
			}
		
			$rows	= $stmt->fetchAll(\PDO::FETCH_ASSOC);
			if (count($rows) == 0) {
				$rows	= null;
			}
			return $rows;
				
		} catch(\Exception $e) {
		    $this->exceptionHandler($e);
		}
	}
	public function getRow($connObj, $query, $qPs)
	{
		try {
			
			$adapObj	= $this->getAdaptor($connObj);
			$query		= trim($query);
			if (preg_match("/^select/i", $query) == 1 && preg_match("/limit/i", $query) == 0) {
				//select query does not have a limit in it get row implies a single return
				//adding limit 1 when retriving a single row is faster
				$query	.= "
						LIMIT 1";
			}
			$adapObj->last	= time();
			$stmt			= $adapObj->adaptor->prepare($query);
			if ($qPs === null) {
				$stmt->execute();
			} else {
				//use parameters
				$stmt->execute($qPs);
			}

			$row	= $stmt->fetch(\PDO::FETCH_ASSOC);
			if ($row === false) {
				$row	= null;
			}
			return $row;
			
		} catch(\Exception $e) {
		    $this->exceptionHandler($e);
		}
	}
	public function getCell($connObj, $query, $qPs)
	{
		$row		= $this->getRow($connObj, $query, $qPs);
		if ($row !== null) {
			return current($row);
		} else {
			return null;
		}
	}
	
	//Insert, pass data by referance, we are duplicating the data, so no changes will be made
	//without referace we would have the data in 3 places
	public function insert($connObj, $tableName, &$rows, $dupAction)
	{
		try {
			
			$adapObj	= $this->getAdaptor($connObj);
			
			//prep the rows
			if (is_array($rows) === true) {
				if (is_array(current($rows)) === false) {
					//must be 2 dimentional array, this is a single row insert
					$rows	= array($rows);
				}
				$rowCount	= count($rows);
				if ($rowCount > 0) {
					$colOrder	= current($rows);
					ksort($colOrder);
					$colCount	= count($colOrder);
					
					if ($colCount > 0) {
						
						//get the columns we are inserting
						$colNames	= array();
						$qMarks		= array();
						$rowSize	= 0;
						$i=0;
						foreach ($colOrder as $colName => $colVal) {
							$colNames[] 		= $colName;
							$colPos[$colName] 	= $i++;
							$qMarks[]			= "?";
							$rowSize			+= strlen($colVal);
						}
						
						$queries		= array();
						$qMarkStr		= "(" . implode(",", $qMarks) . ")";
						$maxPacket		= $this->getMaxPacket($connObj);
						$maxPrep		= $this->getMaxPreparedCount($connObj);
						
						
						//rough estimate of how many rows we should insert per query
						//this guards against huge queries failing because the server does not accept
						//large payloads. a major flaw is the $rowSize is only a sample from the very first row
						//however we compensate a bit by * 0.75, but its not solid logic
						$rowsPerQuery	= ceil(($maxPacket / $rowSize * 0.75));
						
						//Mysql only accepts a max number of prepared arguments that might
						//be smaller than the max packet would allow
						$iPerQuery		= floor($maxPrep / $colCount);
						if ($iPerQuery > 0 && $rowsPerQuery > $iPerQuery) {
							//the packet size is good, but we need to limit the prepared
							//statement count
							$rowsPerQuery	= $iPerQuery;
						}
						
						
						$rIdMax			= $rowCount - 1;
						
						$iValues		= array();
						$rValues		= array();
						$rCount			= 0;
						
						foreach ($rows as $rId => $row) {
							$rCount++;
							if (count($row) == $colCount) {
								ksort($row);
								foreach ($row as $cName => $cVal) {
									$iValues[]	= $cVal;
									if (isset($colPos[$cName]) === false) {
										throw new \Exception("Invalid Input: Row Key: " . $rId . ", has column name: " . $cName . " that is not consistant with other rows");
									}
								}
								$rValues[]	= $qMarkStr;
								
							} else {
								throw new \Exception("Invalid Input: Row Key: " . $rId . ", has: " . count($row) . " columns, it should have: " . $colCount);
							}
							
							if ($rId == $rIdMax || $rCount == $rowsPerQuery) {
								$queries[]		= array("iValues" => $iValues, "rValues" => $rValues);
								$iValues		= array();
								$rValues		= array();
								$rCount			= 0;
							}
						}
						
						//all rows are clean to insert
						$dubQuery	= "";
						$baseQuery	= "INSERT";
						if ($dupAction === true) {
							$baseQuery	.= " IGNORE";
						}
						
						$baseQuery	.= " INTO `".$connObj->getDatabaseName(). "`.`" . $tableName . "` (`" . implode("`, `", $colNames) . "`) VALUES ";
						
						if (is_array($dupAction) === true) {
							//will insert or if there is a duplicate update
							//Example: $dupAction[]	= array("column" => $colName, "action" => "if(VALUES(`".$colName."`) IS NOT NULL, VALUES(`".$colName."`), `".$colName."`)");
							//the above will replace values in a specific column if a duplicate is found
							foreach ($dupAction as $dubId => $dupData) {
								if (isset($dupData["column"]) === true && isset($dupData["action"]) === true) {
									$dupAction[$dubId]	= "`" . $dupData["column"] . "`=" . $dupData["action"];
								} else {
									throw new \Exception("Invalid Input. Duplication update array must contain column name and action");
								}
							}
							$dubQuery	.= " ON DUPLICATE KEY UPDATE " . implode(", ", $dupAction);
						}
						
						foreach ($queries as $aQuery) {
							$query			= $baseQuery . implode(", ", $aQuery["rValues"]) . $dubQuery;
							$adapObj->last	= time();
							$stmt			= $adapObj->adaptor->prepare($query);
							$stmt->execute($aQuery["iValues"]);
						}
						
						if ($rowCount == 1) {
							return $adapObj->adaptor->lastInsertId();
						} else {
							return;
						}

					} else {
						//no columns, nothing to do
						throw new \Exception("Invalid Input: No columns in the first row");
					}
				} else {
					//no rows, nothing to do
					throw new \Exception("Invalid Input: No rows to insert");
				}
			} else {
				throw new \Exception("Invalid Input: Rows");
			}
		} catch(\Exception $e) {
		    $this->exceptionHandler($e);
		}
	}
	public function update($connObj, $tableName, $row, $query, $qPs)
	{
		try {
			
			$adapObj	= $this->getAdaptor($connObj);
			
			if (is_array($row) === true) {
				$colCount	= count($row);
				if ($colCount > 0) {
					if (is_array(current($row)) === false) {
						
						//need a unique parameter name for binding 
						$qpVar	= ":qp_";
						if ($qPs === null) {
							$qPs	= array();
						} else {
							//find a variable that is not in the current QPs
							if (array_key_exists($qpVar, $qPs) === true) {
								$c=0;
								while (true) {
									$qpTest	= $qpVar . $c;
									if (array_key_exists($qpTest, $qPs) === false) {
										$qpVar	= $qpTest . "_";
										break;
									}
									$c++;
								}
							}
						}
						
						$update	= "UPDATE `".$connObj->getDatabaseName(). "`.`" . $tableName . "` SET";
						$x=0;
						foreach ($row as $key => $val) {
							$curQp			= $qpVar . $x;
							$qPs[$curQp]	= $val;
							$update .= " `".$key."`=" . $curQp;
							$x++;
							
							if ($colCount > $x) {
								$update .= ",";
							}
						}
						
						if ($query !== null) {
							$update	.= " WHERE " . $query;
						}
						$adapObj->last	= time();
						$stmt			= $adapObj->adaptor->prepare($update);
						$stmt->execute($qPs);

					} else {
						throw new \Exception("Invalid Input: Row");
					}
					
				} else {
					throw new \Exception("Invalid Input: No columns in the row");
				}
			} else {
				throw new \Exception("Invalid Input: Row");
			}

		} catch(\Exception $e) {
		    $this->exceptionHandler($e);
		}
	}
	public function delete($connObj, $tableName, $query, $qPs)
	{
		try {
			
			$adapObj	= $this->getAdaptor($connObj);
			$delete	= "DELETE FROM `".$connObj->getDatabaseName(). "`.`" . $tableName . "`";
			if ($query !== null) {
				$delete	.= " WHERE " . $query;
			}
	
			$adapObj->last	= time();
			$stmt			= $adapObj->adaptor->prepare($delete);
			if ($qPs === null) {
				$stmt->execute();
			} else {
				//use parameters
				$stmt->execute($qPs);
			}

		} catch(\Exception $e) {
		    $this->exceptionHandler($e);
		}
	}
	protected function exceptionHandler($e)
	{
		//quash the original exception and issue a generic one
		//otherwise the exception might leak data database might bubble
		//e.g. SQLSTATE[HY000] [1044] Access denied for user 'XXXXX'@'%' to database 'XXXXX' - Code: 1044
		//set the error code so error handlers can still understand what is going on, the dangerous part is in the message
		//if you dont want to leak the code, rewrite it
		$dbCode	= $e->getCode();
		if (strpos($e->getMessage(), "violation: 1062 Duplicate entry") !== false) {
			$dbCode	= 1062;
		}
		if (array_key_exists($dbCode, $this->_exRewrites) === true) {
			$rwObj	= $this->_exRewrites[$dbCode];
			throw new \Exception($rwObj->exMsg, $rwObj->exCode);
			
		} elseif ($this->getDebug() === true) {
			//want all errors to be thrown as exceptions rather that PDOExceptions (can use string codes)
			$errMsg		= $e->getMessage();
			$errCode	= $e->getCode();
			if (ctype_digit((string) $errCode) === false) {
				$errMsg		.= " --- '".$errCode."'";
				$errCode	= 18622;
			}
			throw new \Exception($errMsg, $errCode);
		} else {
			//default
			throw new \Exception("MAC-DB", 0);
		}
	}
}