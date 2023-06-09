<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpDocRedundantThrowsInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\NoOpTranslator;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Http\IRequest;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class LocaleLinkGeneratorTest extends TestCase
{

	private LocaleLinkGenerator $localeLinkGenerator;


	public function __construct(
		private readonly RouterFactory $routerFactory,
		private readonly IRequest $httpRequest,
		private readonly IPresenterFactory $presenterFactory,
		private readonly LinkGenerator $linkGenerator,
		private readonly NoOpTranslator $translator,
	) {
	}


	protected function setUp(): void
	{
		$this->localeLinkGenerator = new LocaleLinkGenerator($this->routerFactory, $this->httpRequest, $this->presenterFactory, $this->linkGenerator, $this->translator);
	}


	public function testLinks(): void
	{
		$params = [
			'en_US' => ['name' => 'foo'],
			'cs_CZ' => ['name' => 'fuu'],
		];
		$links = $this->localeLinkGenerator->links('Www:Talks:talk', $params);
		Assert::same('cs_CZ', $this->translator->getDefaultLocale());
		Assert::same(['en_US' => 'https://www.burger.test/talks/foo'], $links);
	}


	/**
	 * @throws \Nette\Application\UI\InvalidLinkException No route for Pulse:PasswordsStorages:site(param=foo)
	 */
	public function testLinksNoRoute(): void
	{
		$params = [
			'en_US' => ['param' => 'foo'],
			'cs_CZ' => ['param' => 'fuu'],
		];
		$this->localeLinkGenerator->links('Pulse:PasswordsStorages:site', $params);
	}


	/**
	 * @throws \Nette\Application\UI\InvalidLinkException Cannot load presenter 'Does:Not', class 'MichalSpacekCz\Does\Presenters\NotPresenter' was not found.
	 */
	public function testLinksUnknownRoute(): void
	{
		$this->localeLinkGenerator->links('Does:Not:exist');
	}


	public function testAllLinks(): void
	{
		$params = [
			'en_US' => ['name' => 'foo'],
			'cs_CZ' => ['name' => 'fuu'],
		];
		$links = $this->localeLinkGenerator->allLinks('Www:Talks:talk', $params);
		Assert::same('cs_CZ', $this->translator->getDefaultLocale());
		$expected = [
			'cs_CZ' => 'https://www.rizek.test/prednasky/fuu',
			'en_US' => 'https://www.burger.test/talks/foo',
		];
		Assert::same($expected, $links);
	}


	public function testAllLinksNoRoute(): void
	{
		$params = [
			'en_US' => ['param' => 'foo'],
			'cs_CZ' => ['param' => 'fuu'],
		];
		$expected = [
			'cs_CZ' => 'https://pulse.rizek.test/passwords/storages/site/fuu',
		];
		Assert::same($expected, $this->localeLinkGenerator->allLinks('Pulse:PasswordsStorages:site', $params));
	}


	/**
	 * @throws \Nette\Application\UI\InvalidLinkException Cannot load presenter 'Exist:Does', class 'MichalSpacekCz\Exist\Presenters\DoesPresenter' was not found.
	 */
	public function testAllLinksUnknownRoute(): void
	{
		$this->localeLinkGenerator->allLinks('Exist:Does:not');
	}


	public function testDefaultParams(): void
	{
		$params = ['foo' => 'bar'];
		Assert::same(['*' => $params], $this->localeLinkGenerator->defaultParams($params));
	}


	public function testSetDefaultParams(): void
	{
		$params = ['foo' => ['bar' => 'baz']];
		$defaultParams = ['default' => 'params'];
		$expected = array_merge($params, ['*' => $defaultParams]);
		$this->localeLinkGenerator->setDefaultParams($params, $defaultParams);
		Assert::same($expected, $params);
	}

}

$runner->run(LocaleLinkGeneratorTest::class);
