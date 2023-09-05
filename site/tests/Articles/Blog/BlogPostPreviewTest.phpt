<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class BlogPostPreviewTest extends TestCase
{

	public function __construct(
		private readonly BlogPostPreview $blogPostPreview,
		private readonly TemplateFactory $templateFactory,
		private readonly IPresenterFactory $presenterFactory,
		private readonly TexyFormatter $texyFormatter,
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
		$presenter = $this->presenterFactory->createPresenter($name); // Has to be a real presenter that extends Ui\Presenter
		if (!$presenter instanceof Presenter) {
			Assert::fail('Presenter is of a wrong class ' . get_debug_type($presenter));
		} else {
			$presenter->loadState(['slug' => 'foo']);
			/** @noinspection PhpInternalEntityUsedInspection */
			$presenter->setParent(null, $name); // Set the name
			$presenter->changeAction('default');
			$template = $this->templateFactory->createTemplate($presenter);
			$rendered = '';
			Assert::noError(function () use ($post, $template, &$rendered): void {
				$this->blogPostPreview->sendPreview($post, $template, function (?DefaultTemplate $template) use (&$rendered): void {
					$rendered = $template?->renderToString() ?? '';
				});
			});
			Assert::contains('<p>Text <strong>something</strong></p>', $rendered);
		}
	}

}

TestCaseRunner::run(BlogPostPreviewTest::class);
