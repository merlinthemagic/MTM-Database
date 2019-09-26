<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database\Tools\Mysql;

class Columns extends Datatypes
{
	public function getColumnDefinitionFromArray($datas)
	{
		//provides a column definition that will support the data in the array
		$type	= $this->getDataTypeFromArray($datas);
		
		echo "\n <code><pre> \nClass:  ".get_class($this)." \nMethod:  ".__FUNCTION__. "  \n";
		//var_dump($_SERVER);
		echo "\n 2222 \n";
		print_r($type);
		echo "\n 3333 \n";
		print_r($datas);
		echo "\n ".time()."</pre></code> \n ";
		die("end");
	}
}