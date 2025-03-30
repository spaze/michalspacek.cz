<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Makefile;

use MichalSpacekCz\Makefile\Exceptions\MakefileContainsRealTargetsException;
use MichalSpacekCz\Makefile\Exceptions\MakefileNotFoundException;
use MichalSpacekCz\Utils\Arrays;
use Nette\IOException;
use Nette\Utils\FileSystem;

final class Makefile
{

	private const string PHONY_TARGET = '.PHONY';

	/** @var array<string, list<int>> target => lines */
	private array $targetDefinitions = [];


	/**
	 * @return array<string, list<string>> target => prerequisites
	 * @throws MakefileNotFoundException
	 */
	private function getPrerequisites(string $file): array
	{
		$this->targetDefinitions = [];
		$prerequisites = [
			self::PHONY_TARGET => [],
		];
		try {
			$lines = FileSystem::readLines($file);
		} catch (IOException $e) {
			throw new MakefileNotFoundException($file, $e);
		}
		foreach ($lines as $index => $line) {
			if (str_starts_with($line, "\t")) {
				continue;
			}
			$parts = explode('#', $line, 2);
			$line = trim($parts[0]);
			$parts = explode(':', $line, 2);
			$targets = Arrays::filterEmpty(explode(' ', $parts[0]));
			$targetPrerequisites = Arrays::filterEmpty(explode(' ', $parts[1] ?? ''));
			foreach ($targets as $target) {
				if (!isset($this->targetDefinitions[$target])) {
					$this->targetDefinitions[$target] = [$index + 1];
				} else {
					$this->targetDefinitions[$target][] = $index + 1;
				}
				if (!isset($prerequisites[$target])) {
					$prerequisites[$target] = [];
				}
				foreach ($targetPrerequisites as $prerequisite) {
					$prerequisites[$target][] = $prerequisite;
				}
			}
		}
		return $prerequisites;
	}


	/**
	 * @throws MakefileContainsRealTargetsException
	 * @throws MakefileNotFoundException
	 */
	public function checkAllTargetsArePhony(string $file): void
	{
		$notPhonyTargets = [];
		$prerequisites = $this->getPrerequisites($file);
		foreach (array_keys($prerequisites) as $target) {
			if ($target === self::PHONY_TARGET) {
				continue;
			}
			if (!in_array($target, $prerequisites[self::PHONY_TARGET], true)) {
				$notPhonyTargets[$target] = $this->targetDefinitions[$target];
			}
		}
		if ($notPhonyTargets !== []) {
			throw new MakefileContainsRealTargetsException($notPhonyTargets);
		}
	}

}
