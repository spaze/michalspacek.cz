<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Routers\RouteList;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class RouterFactoryTest extends TestCase
{

	public function __construct(
		private readonly RouterFactory $routerFactory,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
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


	public function testSitemapRoute(): void
	{
		$this->database->setFetchDefaultResult(['slug' => 'sitemap.xml']); // a blog post, which the sitemap route goes before
		$localeRouter = $this->routerFactory->createRouter();
		$routeLists = [
			'https://www.rizek.test/sitemap.xml' => [$localeRouter->getRouteList(), 'Www:Sitemap'],
			'https://www.burger.test/sitemap.xml' => [$localeRouter->getLocaleRouters()['en_US'], 'Www:Sitemap'],
			'https://pulse.rizek.test/sitemap.xml' => [$localeRouter->getRouteList(), 'Pulse:Sitemap'], // before the presenter route, which would read it as a Sitemap:Xml presenter
		];
		foreach ($routeLists as $url => [$routeList, $presenter]) {
			$params = $routeList->match(new Request(new UrlScript($url)));
			Assert::same(['presenter' => $presenter, 'action' => 'default'], $params);
			Assert::same($url, $routeList->constructUrl(['presenter' => $presenter, 'action' => 'default'], new UrlScript($url)));
		}
	}


	/**
	 * Only the link has the slash encoded, the address with the slash finds the same page, for the presenter to redirect.
	 */
	public function testPulseSiteAliasWithPath(): void
	{
		$routeList = $this->routerFactory->createRouter()->getRouteList();
		$url = 'https://pulse.rizek.test/passwords/storages/site/example.com%2Ffoo';
		$params = ['action' => 'site', 'param' => 'example.com/foo', 'presenter' => 'Pulse:PasswordsStorages'];
		Assert::same($url, $routeList->constructUrl($params, new UrlScript($url)));
		Assert::same($params, $routeList->match(new Request(new UrlScript($url))));
		Assert::same($params, $routeList->match(new Request(new UrlScript('https://pulse.rizek.test/passwords/storages/site/example.com/foo'))));
		$params = ['presenter' => 'Pulse:PasswordsStorages', 'action' => 'site', 'param' => 'www.facebook.com,twitter.com'];
		Assert::same('https://pulse.rizek.test/passwords/storages/site/www.facebook.com,twitter.com', $routeList->constructUrl($params, new UrlScript($url)), 'Only the slash is encoded');
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
