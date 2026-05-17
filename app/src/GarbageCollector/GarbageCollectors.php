<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

final readonly class GarbageCollectors
{

	/**
	 * @param iterable<GarbageCollector> $instances
	 */
	public function __construct(
		private iterable $instances,
	) {
	}


	/**
	 * @return iterable<GarbageCollector>
	 */
	public function getAll(): iterable
	{
		return $this->instances;
	}

}
