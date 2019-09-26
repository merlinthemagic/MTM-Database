<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database\Tools\Mysql;

class Datatypes
{
	protected function getDataTypeObj()
	{
		$rObj				= new \stdClass();
		$rObj->valid		= null;
		$rObj->name			= null;
		$rObj->signed		= null;
		$rObj->length 		= null;
		$rObj->precision	= null;
		$rObj->scale		= null;
		
		return $rObj;
	}
	public function getDataTypeFromArray($datas)
	{
		//returns the data type that will support the array values
		$rObj				= $this->getDataTypeObj();
		$rObj->valid		= true;
		
		foreach ($datas as $data) {
			$obj	= $this->getDataTypeFromValue($data);
			if ($obj->valid === true) {
				echo "\n <code><pre> \nClass:  ".get_class($this)." \nMethod:  ".__FUNCTION__. "  \n";
				//var_dump($_SERVER);
				echo "\n 2222 \n";
				print_r($obj);
				echo "\n 3333 \n";
				print_r($datas);
				echo "\n ".time()."</pre></code> \n ";
				die("end");
			} else {
				$rObj->valid		= false;
				break;
			}
		}
		return $rObj;
	}
	public function getDataTypeFromValue($value)
	{
		$rObj				= $this->getDataTypeObj();
		$rObj->valid		= true;
		
		if (is_numeric($value) === true) {

			if (ctype_digit((string) $value) === true) {
				
				//src: https://dev.mysql.com/doc/refman/8.0/en/integer-types.html
				if ($value > -2147483648 && $value < 2147483648) {
					$rObj->name		= "int";
					$rObj->signed	= true;
				} elseif ($value > -1 && $value < 4294967296) {
					$rObj->name		= "int";
					$rObj->signed	= false;
				} elseif ($value > -9223372036854775808 && $value < 9223372036854775808) {
					$rObj->name		= "bigint";
					$rObj->signed	= true;
				} elseif ($value > -1 && $value < 18446744073709551616) {
					$rObj->name		= "bigint";
					$rObj->signed	= false;
				} else {
					$rObj->valid	= false;
				}
				
			} else {
				//src: https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html
				if (preg_match("/([0-9]+)\.([0-9]+)/", $value, $raw) == 1) {
					$rObj->name			= "decimal";
					$rObj->signed		= true;
					$rObj->precision	= strlen($raw[1]);
					$rObj->scale		= strlen($raw[2]);
				} else {
					$rObj->valid	= false;
				}
			}
			
		} elseif (is_string($value) === true) {
			$len	= strlen($value);
			if ($len < 255) {
				$rObj->name			= "char";
				$rObj->length		= $len;
			} elseif ($len < 65536) {
				//src: https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
				$rObj->name			= "text";
			} elseif ($len < 16777216) {
				//src: https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
				$rObj->name			= "mediumtext";
			} elseif ($len < 4294967296) {
				//src: https://stackoverflow.com/questions/6766781/maximum-length-for-mysql-type-text
				$rObj->name			= "longtext";
			} else {
				$rObj->valid	= false;
			}
			
		} elseif (is_null($value) === true) {
			//not a datatype
			
		} else {
			$rObj->valid	= false;
		}
		
		return $rObj;
	}
}