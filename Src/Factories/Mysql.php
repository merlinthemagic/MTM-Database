<?php
//� 2019 Martin Peter Madsen
namespace MTM\Database\Factories;

class Mysql extends Base
{
	public function getConnection($host, $user, $pass, $port)
	{
		$rObj	= new \MTM\Database\Models\Mysql\Server\Zulu();
		$rObj->setHostname($host);
		$rObj->setConnectionDetail($user, $pass, $port);
		
		return $rObj;
	}
	public function getTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Database\Tools\Mysql\Actions();
		}
		return $this->_cStore[__FUNCTION__];
	}
}