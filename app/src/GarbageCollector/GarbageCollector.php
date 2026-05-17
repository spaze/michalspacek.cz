<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

interface GarbageCollector
{

	public function getGarbageCollectorType(): GarbageCollectorType;


	/**
	 * @return int Interval in seconds at which this garbage collector is expected to run
	 */
	public function getIntervalSeconds(): int;


	public function clean(): GarbageCollectorReturnCode;

}
