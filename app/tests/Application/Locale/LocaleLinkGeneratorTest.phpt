<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use MichalSpacekCz\Application\Routing\LocaleRouter;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IRequest;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class LocaleLinkGeneratorTest extends TestCase
{

	private LocaleLinkGenerator $localeLinkGenerator;


	public function __construct(
		private readonly NoOpTranslator $translator,
		LocaleRouter $localeRouter,
		IRequest $httpRequest,
		IPresenterFactory $presenterFactory,
		LinkGenerator $linkGenerator,
	) {
		$this->localeLinkGenerator = new LocaleLinkGenerator($localeRouter, $httpRequest, $presenterFactory, $linkGenerator, $translator, [
			'cs_CZ' => [
				'code' => 'cs',
				'name' => 'Česky',
			],
			'en_US' => [
				'code' => 'en',
				'name' => 'English',
			],
		]);
	}


	public function testLinks(): void
	{
		$params = [
			'en_US' => ['name' => 'foo'],
			'cs_CZ' => ['name' => 'fuu'],
		];
		$links = $this->localeLinkGenerator->links('Www:Talks:talk', $params);
		Assert::same('cs_CZ', $this->translator->getDefaultLocale());
		Assert::equal(['en_US' => new LocaleLink('en_US', 'en', 'English', 'https://www.burger.test/talks/foo')], $links);
	}


	public function testLinksNoRoute(): void
	{
		$params = [
			'en_US' => ['param' => 'foo'],
			'cs_CZ' => ['param' => 'fuu'],
		];
		Assert::exception(function () use ($params): void {
			$this->localeLinkGenerator->links('Pulse:PasswordsStorages:site', $params);
		}, InvalidLinkException::class, 'No route for Pulse:PasswordsStorages:site(param=foo)');
	}


	public function testLinksUnknownRoute(): void
	{
		Assert::exception(function (): void {
			$this->localeLinkGenerator->links('Does:Not:exist');
		}, InvalidLinkException::class, "Cannot load presenter 'Does:Not', class 'MichalSpacekCz\Presentation\Does\Not\NotPresenter' was not found.");
	}


	public function testLinksForLocaleParams(): void
	{
		$params = [
			'en_US' => ['name' => 'foo'],
			'cs_CZ' => ['name' => 'fuu'],
		];
		Assert::equal([
			'cs_CZ' => new LocaleLink('cs_CZ', 'cs', 'Česky', 'https://www.rizek.test/prednasky/fuu'),
			'en_US' => new LocaleLink('en_US', 'en', 'English', 'https://www.burger.test/talks/foo'),
		], $this->localeLinkGenerator->linksForLocaleParams('Www:Talks:talk', $params));
	}


	public function testLinksForLocaleParamsSkipsOthers(): void
	{
		Assert::equal([
			'en_US' => new LocaleLink('en_US', 'en', 'English', 'https://www.burger.test/talks/foo'),
		], $this->localeLinkGenerator->linksForLocaleParams('Www:Talks:talk', ['en_US' => ['name' => 'foo']]));
		Assert::equal([
			'cs_CZ' => new LocaleLink('cs_CZ', 'cs', 'Česky', 'https://www.rizek.test/prednasky/fuu'),
		], $this->localeLinkGenerator->linksForLocaleParams('Www:Talks:talk', ['cs_CZ' => ['name' => 'fuu']]));
	}


	public function testLinksForLocaleParamsDefaultParams(): void
	{
		Assert::equal([
			'cs_CZ' => new LocaleLink('cs_CZ', 'cs', 'Česky', 'https://www.rizek.test/rozhovory/foo'),
			'en_US' => new LocaleLink('en_US', 'en', 'English', 'https://www.burger.test/interviews/foo'),
		], $this->localeLinkGenerator->linksForLocaleParams('Www:Interviews:interview', $this->localeLinkGenerator->defaultParams(['name' => 'foo'])));
	}


	/**
	 * The other locale's routes are the Www module's only, where a Pulse page has no route.
	 */
	public function testLinksForLocaleParamsModuleInOneLocale(): void
	{
		Assert::equal([
			'cs_CZ' => new LocaleLink('cs_CZ', 'cs', 'Česky', 'https://pulse.rizek.test/passwords/storages/site/foo'),
		], $this->localeLinkGenerator->linksForLocaleParams('Pulse:PasswordsStorages:site', $this->localeLinkGenerator->defaultParams(['param' => 'foo'])));
	}


	public function testLinksForLocaleParamsAbsoluteDestination(): void
	{
		Assert::equal([
			'cs_CZ' => new LocaleLink('cs_CZ', 'cs', 'Česky', 'https://www.rizek.test/rozhovory/foo'),
			'en_US' => new LocaleLink('en_US', 'en', 'English', 'https://www.burger.test/interviews/foo'),
		], $this->localeLinkGenerator->linksForLocaleParams(':Www:Interviews:interview', $this->localeLinkGenerator->defaultParams(['name' => 'foo'])));
	}


	public function testHasLocale(): void
	{
		Assert::true($this->localeLinkGenerator->hasLocale(['cs_CZ' => ['name' => 'foo']], 'cs_CZ'));
		Assert::false($this->localeLinkGenerator->hasLocale(['cs_CZ' => ['name' => 'foo']], 'en_US'));
		Assert::true($this->localeLinkGenerator->hasLocale($this->localeLinkGenerator->defaultParams([]), 'en_US'));
	}


	public function testLinksForLocaleParamsNoRoute(): void
	{
		Assert::exception(function (): void {
			$this->localeLinkGenerator->linksForLocaleParams('Www:Talks:talk', ['en_US' => []]);
		}, InvalidLinkException::class);
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


	public function testAllLinksUnknownRoute(): void
	{
		Assert::exception(function (): void {
			$this->localeLinkGenerator->allLinks('Exist:Does:not');
		}, InvalidLinkException::class, "Cannot load presenter 'Exist:Does', class 'MichalSpacekCz\Presentation\Exist\Does\DoesPresenter' was not found.");
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

TestCaseRunner::run(LocaleLinkGeneratorTest::class);
