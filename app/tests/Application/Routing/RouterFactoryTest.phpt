<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Routers\RouteList;
use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class RouterFactoryTest extends TestCase
{

	public function __construct(
		private readonly RouterFactory $routerFactory,
	) {
	}


	public function testCreateRouter(): void
	{
		$localeRouter = $this->routerFactory->createRouter();
		Assert::same(['cs_CZ', 'en_US'], array_keys($this->routerFactory->getLocaleRouters()));
		Assert::same($this->routerFactory->getLocaleRouters(), $localeRouter->getLocaleRouters());

		$refUrl = new UrlScript('https://com.example/');
		Assert::same('https://com.example/nette.micro', $localeRouter->getRouteList()->constructUrl([
			'presenter' => 'EasterEgg:Nette',
			'action' => 'micro',
		], $refUrl));
		Assert::same('https://com.example/.well-known/security.txt', $localeRouter->getRouteList()->constructUrl([
			'presenter' => 'WellKnown:WellKnown',
			'action' => 'securityTxt',
		], $refUrl));
		Assert::same('https://api.rizek.test/certificates/log-issued', $localeRouter->getRouteList()->constructUrl([
			'presenter' => 'Api:Certificates',
			'action' => 'logIssued',
		], $refUrl));
	}


	public function testEveryRouteEnforcesHttps(): void
	{
		$localeRouter = $this->routerFactory->createRouter();
		$masks = [];
		foreach ([$localeRouter->getRouteList(), ...array_values($localeRouter->getLocaleRouters())] as $routeList) {
			$masks = [...$masks, ...$this->collectMasks($routeList)];
		}

		Assert::notSame([], $masks);
		$notHttps = [];
		foreach ($masks as $mask) {
			if (!str_starts_with($mask, 'https://')) {
				$notHttps[] = $mask;
			}
		}
		Assert::same([], $notHttps);
	}


	/**
	 * @return list<string>
	 */
	private function collectMasks(RouteList $routeList): array
	{
		$masks = [];
		foreach ($routeList->getRouters() as $router) {
			if ($router instanceof RouteList) {
				$masks = [...$masks, ...$this->collectMasks($router)];
			} elseif ($router instanceof Route) {
				$masks[] = $router->getMask();
			}
		}
		return $masks;
	}

}

TestCaseRunner::run(RouterFactoryTest::class);
