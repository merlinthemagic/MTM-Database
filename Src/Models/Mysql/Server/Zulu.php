<?php
//� 2023 Martin Peter Madsen
namespace MTM\Database\Models\Mysql\Server;

class Zulu extends Methods
{
	protected $_termCbs=array();
	
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
}