<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostPreviewTest extends TestCase
{

	public function __construct(
		private readonly BlogPostPreview $blogPostPreview,
		private readonly TemplateFactory $templateFactory,
		private readonly TexyFormatter $texyFormatter,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	public function testSendPreview(): void
	{
		$title = 'Title something';
		$lead = 'Excerpt something';
		$text = 'Text **something**';
		$post = new BlogPost(
			1,
			'',
			2,
			'en_US',
			null,
			Html::fromText($title),
			$title,
			Html::fromText($lead),
			$lead,
			$this->texyFormatter->formatBlock($text),
			$text,
			new DateTime(),
			false,
			null,
			null,
			null,
			null,
			[],
			[],
			[],
			null,
			'https://example.com/something',
			[],
			[],
			[],
			false,
		);

		$name = 'Admin:Blog';
		$presenter = $this->applicationPresenter->createUiPresenter(
			$name, // Has to be a real presenter that extends Ui\Presenter
			$name,
			'default',
		);
		$presenter->loadState(['slug' => 'foo']);
		$template = $this->templateFactory->createTemplate($presenter);
		$rendered = '';
		Assert::noError(function () use ($post, $template, &$rendered): void {
			$this->blogPostPreview->sendPreview(
				function () use ($post): BlogPost {
					return $post;
				},
				$template,
				function (?DefaultTemplate $template) use (&$rendered): void {
					$rendered = $template?->renderToString() ?? '';
				},
			);
		});
		Assert::contains('<p>Text <strong>something</strong></p>', $rendered);
	}

}

TestCaseRunner::run(BlogPostPreviewTest::class);
