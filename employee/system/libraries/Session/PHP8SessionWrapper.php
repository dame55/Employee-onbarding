<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_SessionWrapper implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {

	protected CI_Session_driver_interface $driver;

	public function __construct(CI_Session_driver_interface $driver)
	{
		$this->driver = $driver;
	}

	public function open(string $save_path, string $name): bool
	{
		return $this->driver->open($save_path, $name);
	}

	public function close(): bool
	{
		return $this->driver->close();
	}

	#[\ReturnTypeWillChange]
	public function read(string $id): mixed
	{
		return $this->driver->read($id);
	}

	public function write(string $id, string $data): bool
	{
		return $this->driver->write($id, $data);
	}

	public function destroy(string $id): bool
	{
		return $this->driver->destroy($id);
	}

	#[\ReturnTypeWillChange]
	public function gc(int $maxlifetime): mixed
	{
		return $this->driver->gc($maxlifetime);
	}

	public function updateTimestamp(string $id, string$data): bool
	{
		return $this->driver->updateTimestamp($id, $data);
	}

	public function validateId(string $id): bool
	{
		return $this->driver->validateId($id);
	}
}
