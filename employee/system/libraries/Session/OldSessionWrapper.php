<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_SessionWrapper implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {

	protected $driver;

	public function __construct(CI_Session_driver_interface $driver)
	{
		$this->driver = $driver;
	}

	public function open($save_path, $name)
	{
		return $this->driver->open($save_path, $name);
	}

	public function close()
	{
		return $this->driver->close();
	}

	public function read($id)
	{
		return $this->driver->read($id);
	}

	public function write($id, $data)
	{
		return $this->driver->write($id, $data);
	}

	public function destroy($id)
	{
		return $this->driver->destroy($id);
	}

	public function gc($maxlifetime)
	{
		return $this->driver->gc($maxlifetime);
	}

	public function updateTimestamp($id, $data)
	{
		return $this->driver->updateTimestamp($id, $data);
	}

	public function validateId($id)
	{
		return $this->driver->validateId($id);
	}
}
