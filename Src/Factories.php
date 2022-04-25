<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database;

class Factories
{
	private static $_cStore=array();
	
	//USE: $aFact		= \MTM\Database\Factories::$METHOD_NAME();
	
	public static function getMysql()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\Database\Factories\Mysql();
		}
		return self::$_cStore[__FUNCTION__];
	}
}