<?php
//� 2023 Martin Peter Madsen
namespace MTM\Database\Models\Mysql\Server;

class Zulu extends Methods
{
	public function addTerminationCb($obj, $method)
	{
		if (
			is_object($obj) === true
			&& is_string($method) === true
			&& method_exists($obj, $method) === true
		) {
			$this->_termCbs[]	= array($obj, $method);
		} else {
			throw new \Exception("Invalid input");
		}
		return $this;
	}
	public function terminate()
	{
		foreach ($this->_termCbs as $cb) {
			try {
				call_user_func_array($cb, array($this));
			} catch (\Exception $e) {
			}
		}
		$this->_termCbs			= array();
	}
	public function setExceptionCb($obj, $method)
	{
		//on exception this object will be called
		if ($obj === null && $method === null) {
			$this->_termCb		= null;
		} elseif (is_object($obj) === false) {
			throw new \Exception("Invalid input, object expected", 19847);
		} elseif (is_string($method) === false) {
			throw new \Exception("Invalid input, string expected", 19848);
		} elseif (method_exists($obj, $method) === false) {
			throw new \Exception("Invalid input, object does not contain method", 19849);
		} else {
			$this->_exCb		= array($obj, $method);
		}
		
		return $this;
	}
}