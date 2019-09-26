<?php
//© 2019 Martin Peter Madsen
namespace MTM\Database\Factories;

class Mysql extends Base
{
	public function getConnection($host=null, $user=null, $pass=null, $port=null)
	{
		$hash	= hash("sha256", $host . $user . $pass . $port);
		if (array_key_exists($hash, $this->_cStore) === false) {
			$rObj	= new \MTM\Database\Models\Mysql\Server();
			if ($host !== null) {
				$rObj->setHostname($host);
			}
			if ($user !== null && $pass !== null) {
				$rObj->setConnectionDetail($user, $pass, $port);
			}
			$this->_cStore[$hash]	= $rObj;
		}
		return $this->_cStore[$hash];
	}
	public function getTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Database\Tools\Mysql\Actions();
		}
		return $this->_cStore[__FUNCTION__];
	}
}