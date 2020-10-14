<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Nette CVE-2020-15227, here to easter-egg some bots
 */
class NetteCve202015227
{

	/**
	 * @param string $callback
	 * @param array<string, string> $params
	 * @return array{0:string, 1:array<string, string>}
	 * @throws BadRequestException
	 */
	public function rce(string $callback, array $params): array
	{
		$view = null;
		$data = [];

		$callback = strtolower($callback);
		$paramNames = [
			'exec' => 'command',
			'passthru' => 'command',
			'proc_open' => 'cmd',
			'shell_exec' => 'cmd',
			'system' => 'command',
			'pcntl_exec' => 'path',
		];

		if (!isset($paramNames[$callback])) {
			throw new BadRequestException(sprintf("[%s] Unknown callback '%s'", __CLASS__, $callback));
		}

		$param = $params[$paramNames[$callback]] ?? null;
		if (!$param) {
			throw new BadRequestException(sprintf("[%s] Empty param '%s' for callback '%s'", __CLASS__, $paramNames[$callback], $callback));
		}

		if (Strings::contains($param, 'ifconfig')) {
			foreach (['Rx', 'Tx'] as $dir) {
				foreach (['Packets', 'Bytes'] as $type) {
					$data['eth0' . $dir . $type] = $this->getRandom();
					$data['eth1' . $dir . $type] = $this->getRandom();
					$data['lo' . $dir . $type] = $this->getRandom();
				}
			}
			$view = 'nette.micro-ifconfig';
		} elseif (Strings::contains($param, 'ls')) {
			$view = 'nette.micro-ls';
		} else {
			throw new BadRequestException(sprintf("[%s] Unknown value '%s' for callback '%s' and param '%s'", __CLASS__, $param, $callback, $paramNames[$callback]));
		}
		return [$view, $data];
	}


	private function getRandom(): string
	{
		/** @noinspection PhpUnhandledExceptionInspection We should have a good enough random source */
		return (string)random_int(1337, 3133731337);
	}

}
