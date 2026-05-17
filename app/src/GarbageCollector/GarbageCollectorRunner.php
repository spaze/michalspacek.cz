<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

use Nette\Utils\FileSystem;
use Throwable;
use Tracy\Debugger;

final readonly class GarbageCollectorRunner
{

	public function __construct(
		private string $lockFilePath,
		private GarbageCollectors $garbageCollectors,
	) {
	}


	public function run(): int
	{
		FileSystem::createDir(dirname($this->lockFilePath));
		$lockFile = @fopen($this->lockFilePath, 'c');
		if ($lockFile === false) {
			Debugger::log(sprintf('Failed to open garbage collector lock file at %s', $this->lockFilePath), Debugger::ERROR);
			return 1;
		}
		if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
			fclose($lockFile);
			return 0;
		}
		try {
			$anyFailed = false;
			foreach ($this->garbageCollectors->getAll() as $gc) {
				try {
					$code = $gc->clean();
					if ($code !== GarbageCollectorReturnCode::Ok) {
						$anyFailed = true;
					}
				} catch (Throwable $e) {
					Debugger::log($e);
					$anyFailed = true;
				}
			}
			return $anyFailed ? 1 : 0;
		} finally {
			flock($lockFile, LOCK_UN);
			fclose($lockFile);
		}
	}

}
