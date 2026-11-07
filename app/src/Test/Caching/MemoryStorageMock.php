<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Caching;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Caching\Storages\MemoryStorage;
use Override;

/**
 * Stores like MemoryStorage, and keeps the dependencies of every write, which MemoryStorage ignores.
 */
final class MemoryStorageMock implements Storage
{

	private MemoryStorage $storage;

	/** @var list<array<string, mixed>> */
	private array $dependencies = [];


	public function __construct()
	{
		$this->storage = new MemoryStorage();
	}


	#[Override]
	public function read(string $key): mixed
	{
		return $this->storage->read($key);
	}


	#[Override]
	public function lock(string $key): void
	{
		$this->storage->lock($key);
	}


	/**
	 * @param array<string, mixed> $dependencies
	 */
	#[Override]
	public function write(string $key, mixed $data, array $dependencies): void
	{
		$this->dependencies[] = $dependencies;
		$this->storage->write($key, $data, $dependencies);
	}


	#[Override]
	public function remove(string $key): void
	{
		$this->storage->remove($key);
	}


	/**
	 * @param array<string, mixed> $conditions
	 */
	#[Override]
	public function clean(array $conditions): void
	{
		$this->storage->clean($conditions);
	}


	/**
	 * @return list<array<string, mixed>>
	 */
	public function getWrittenDependencies(): array
	{
		return $this->dependencies;
	}


	public function reset(): void
	{
		$this->dependencies = [];
		$this->storage->clean([Cache::All => true]);
	}

}
