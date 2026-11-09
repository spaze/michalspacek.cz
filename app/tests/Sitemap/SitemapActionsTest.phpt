<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\TalkSitemapEntries;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\IPresenterFactory;
use Nette\DI\Container;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * A new page is in the sitemap or out of it by a decision, not by being forgotten: every action has to say which,
 * so a new one fails here until it does.
 *
 * @testCase
 */
final class SitemapActionsTest extends TestCase
{

	/** @var list<SitemapAction> */
	private readonly array $actions;


	public function __construct(
		SitemapActions $sitemapActions,
		private readonly Container $container,
		private readonly IPresenterFactory $presenterFactory,
	) {
		$this->actions = $sitemapActions->getActions();
	}


	public function testEveryActionHasExactlyOneAttribute(): void
	{
		$wrong = [];
		foreach ($this->actions as $action) {
			$count = count($action->inSitemap) + count($action->notInSitemap);
			if ($action->methods === []) {
				$wrong[$action->destination] = 'a template only, add a render method to carry the attribute';
			} elseif ($count !== 1) {
				$wrong[$action->destination] = sprintf('%d attributes on %s', $count, implode(', ', $action->methods));
			}
		}
		Assert::same([], $wrong, 'Give each action #[InSitemap] or #[NotInSitemap] with a reason, on its action or render method');
	}


	public function testEveryReasonIsGiven(): void
	{
		$missing = [];
		foreach ($this->actions as $action) {
			foreach ($action->notInSitemap as $notInSitemap) {
				if (trim($notInSitemap->reason) === '') {
					$missing[] = $action->destination;
				}
			}
		}
		Assert::same([], $missing, 'Say why the action is not in the sitemap');
	}


	public function testEveryEntriesClassIsAService(): void
	{
		$missing = [];
		foreach ($this->actions as $action) {
			foreach ($action->inSitemap as $inSitemap) {
				if ($inSitemap->entries !== null && $this->container->findByType($inSitemap->entries) === []) {
					$missing[$action->destination] = $inSitemap->entries;
				}
			}
		}
		Assert::same([], $missing, 'Register the entries class in services.neon');
	}


	public function testActionsAreFound(): void
	{
		$actions = [];
		foreach ($this->actions as $action) {
			$actions[$action->destination] = $action;
		}
		Assert::same([], $actions['Www:Homepage:default']->notInSitemap);
		Assert::equal([new InSitemap()], $actions['Www:Homepage:default']->inSitemap);
		Assert::equal([new InSitemap(TalkSitemapEntries::class)], $actions['Www:Talks:talk']->inSitemap);
		Assert::same(['actionTalk'], $actions['Www:Talks:talk']->methods);
		Assert::same([], $actions['Www:Trainings:success']->inSitemap);
		Assert::count(1, $actions['Www:Trainings:success']->notInSitemap);
		Assert::false(isset($actions['Admin:Homepage:default']), 'Only the Www module is in the sitemap');
	}


	/**
	 * A presenter of the module named other than Foo\FooPresenter isn't skipped like a presenter of another module would
	 * be, or its actions would go unchecked here.
	 */
	public function testPresenterNamedOtherwise(): void
	{
		$container = new class extends Container {

			/**
			 * @return list<string>
			 */
			#[Override]
			public function findByType(string $type): array
			{
				return ['presenter'];
			}


			#[Override]
			public function getServiceType(string $name): string
			{
				return BasePresenter::class;
			}

		};
		$sitemapActions = new SitemapActions($container, $this->presenterFactory);
		Assert::exception(function () use ($sitemapActions): void {
			$sitemapActions->getActions();
		}, ShouldNotHappenException::class, "MichalSpacekCz\\Presentation\\Www\\BasePresenter isn't named Foo\\FooPresenter like the other Www presenters, rename it or teach SitemapActions its presenter name");
	}

}

TestCaseRunner::run(SitemapActionsTest::class);
