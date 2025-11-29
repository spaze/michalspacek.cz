<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Utils\Sleep;
use Nette\Application\BadRequestException;
use Nette\Application\Routers\RouteList;
use Nette\Application\UI\Component;
use Nette\Application\UI\InvalidLinkException;

/**
 * Nette CVE-2020-15227, here to easter-egg some bots
 *
 * Example URLs:
 * - https://www.michalspacek.cz/nette.micro displays mc
 * - https://www.michalspacek.cz/nette.micro?callback=exec&command=ifconfig
 * - https://www.michalspacek.cz/nette.micro?callback=passthru&command=ls
 * - https://www.michalspacek.cz/nette.micro?callback=proc_open&cmd=bash
 * - https://www.michalspacek.cz/nette.micro?callback=shell_exec&cmd=certutil
 * - https://www.michalspacek.cz/nette.micro?callback=pcntl_exec&path=wget
 */
final readonly class NetteCve202015227
{

	private const array PARAM_NAMES = [
		'exec' => 'command',
		'passthru' => 'command',
		'proc_open' => 'cmd',
		'shell_exec' => 'cmd',
		'system' => 'command',
		'pcntl_exec' => 'path',
	];


	public function __construct(
		private Sleep $sleep,
		private Robots $robots,
	) {
	}


	public function rce(?string $callback, Component $component): NetteCve202015227Rce
	{
		$this->sleep->randomSleep(5, 20);
		$this->robots->setHeader([RobotsRule::NoIndex]);
		if ($callback === null) {
			return $this->midnight($component);
		}

		$callback = strtolower($callback);
		if (!isset(self::PARAM_NAMES[$callback])) {
			throw new BadRequestException(sprintf("[%s] Unknown callback '%s'", __CLASS__, $callback));
		}

		$data = [];

		$param = $component->getParameters()[self::PARAM_NAMES[$callback]] ?? null;
		assert(is_string($param) || $param === null);
		if ($param === null) {
			throw new BadRequestException(sprintf("[%s] Empty param '%s' for callback '%s'", __CLASS__, self::PARAM_NAMES[$callback], $callback));
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
			throw new BadRequestException(sprintf("[%s] Unknown value '%s' for callback '%s' and param '%s'", __CLASS__, $param, $callback, self::PARAM_NAMES[$callback]));
		}
		return new NetteCve202015227Rce($view, $data);
	}


	private function getRandom(): string
	{
		/** @noinspection PhpUnhandledExceptionInspection We should have a good enough random source */
		return (string)random_int(1337, 3133731337);
	}


	private function midnight(Component $component): NetteCve202015227Rce
	{
		$hints = [
			'Hint: Shell commands will not work when you are on a non-local file system.     ', // 80 chars long
			'Hint: Bring text back from the dead with C-y.                                   ',
			'Hint: F13 (or Shift-F3) invokes the viewer in raw mode.                         ',
			'Hint: FTP is built in the Midnight Commander, check the File/FTP link menu.     ',
			'Hint: To use the mouse cut and paste may require holding the shift key.         ',
			'Hint: Moe van deze hints? Zet ze uit in Opties/Vormgeving.                      ',
			'ヒント：タブは現在のパネルを変更します。                                           ', // Shorter due to the wide chars
			'Tip: Kobyla má malý bok.                                                        ',
			'Porada: Ale fajny bober, kurwa gryzie.                                          ',
			'Hinweis: Kraftfahrzeughaftpflichtversicherung.                                  ',
		];
		$files = [
			[$this->midnightLink($component, 'shell_exec', 'certutil'), 'certutil.exe    ', '1593344', 'Oct 30  2025'], // file 16 chars, size 7 chars, date 12 chars
			[$this->midnightLink($component, 'passthru', 'ls'), 'ls              ', ' 141365', 'Jun 16 14:56'],
			[$this->midnightLink($component, 'exec', 'ifconfig'), 'ifconfig        ', '  83272', 'Sep  6 16:16'],
			[$this->midnightLink($component, 'pcntl_exec', 'wget'), 'wget            ', ' 531536', 'Oct  2 17:30'],
			[$this->midnightLink($component, 'proc_open', 'zsh'), 'zsh             ', '1017448', 'Sep  6 17:41'],
		];
		return new NetteCve202015227Rce(
			NetteCve202015227View::MidnightCommander,
			[
				'hint' => $hints[array_rand($hints)],
				'files' => $files,
				'emptyLines' => 9, // To make it 25 lines total, because I'm a 80×25 kid
			],
		);
	}


	private function midnightLink(Component $component, string $callback, string $paramValue): string
	{
		try {
			return $component->link('this', ['callback' => $callback, self::PARAM_NAMES[$callback] => $paramValue]);
		} catch (InvalidLinkException) {
			return 'http://localhost';
		}
	}


	public function addRoute(RouteList $router): void
	{
		$router->withModule('EasterEgg')->addRoute('/nette.micro', ['presenter' => 'Nette', 'action' => 'micro']);
	}

}
