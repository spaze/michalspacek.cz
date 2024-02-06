<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\BadRequestException;

/**
 * Nette CVE-2020-15227, here to easter-egg some bots
 */
class NetteCve202015227
{

	/**
	 * @param array<string, string> $params
	 */
	public function rce(string $callback, array $params): NetteCve202015227Rce
	{
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

		$data = [];

		$param = $params[$paramNames[$callback]] ?? null;
		if ($param === null) {
			throw new BadRequestException(sprintf("[%s] Empty param '%s' for callback '%s'", __CLASS__, $paramNames[$callback], $callback));
		}
		if (str_contains($param, 'ifconfig')) {
			foreach (['Rx', 'Tx'] as $dir) {
				foreach (['Packets', 'Bytes'] as $type) {
					$data['eth0' . $dir . $type] = $this->getRandom();
					$data['eth1' . $dir . $type] = $this->getRandom();
					$data['lo' . $dir . $type] = $this->getRandom();
				}
			}
			$view = NetteCve202015227View::Ifconfig;
		} elseif (str_contains($param, 'ls')) {
			$view = NetteCve202015227View::Ls;
		} elseif (str_contains($param, 'wget')) {
			$view = NetteCve202015227View::Wget;
		} elseif (str_contains($param, 'echo')) {
			$data['command'] = 'echo';
			$view = NetteCve202015227View::NotFound;
		} elseif (str_contains($param, 'bash')) {
			$data['command'] = 'bash';
			$view = NetteCve202015227View::NotFound;
		} elseif (str_contains($param, 'sa.exe')) {
			$data['command'] = 'sa.exe';
			$view = NetteCve202015227View::NotRecognized;
		} elseif (str_contains($param, 'certutil')) {
			$data['command'] = 'certutil.exe';
			$view = NetteCve202015227View::NotRecognized;
		} elseif (str_contains($param, 'sh')) {
			$data['command'] = 'zsh';
			$view = NetteCve202015227View::NotFound;
		} else {
			throw new BadRequestException(sprintf("[%s] Unknown value '%s' for callback '%s' and param '%s'", __CLASS__, $param, $callback, $paramNames[$callback]));
		}
		return new NetteCve202015227Rce($view, ...$data);
	}


	private function getRandom(): string
	{
		/** @noinspection PhpUnhandledExceptionInspection We should have a good enough random source */
		return (string)random_int(1337, 3133731337);
	}

}
