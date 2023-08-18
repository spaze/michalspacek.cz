<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Templating\TemplateFactory;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class BlogPostPreviewTest extends TestCase
{

	public function __construct(
		private readonly BlogPostPreview $blogPostPreview,
		private readonly TemplateFactory $templateFactory,
		private readonly IPresenterFactory $presenterFactory,
	) {
	}


	public function testSendPreview(): void
	{
		$post = new BlogPost();
		$post->postId = 1;
		$post->titleTexy = 'Title something';
		$post->href = 'https://example.com/something';
		$post->published = new DateTime();
		$post->leadTexy = 'Excerpt something';
		$post->textTexy = 'Text **something**';
		$post->originallyTexy = null;
		$post->omitExports = false;
		$post->ogImage = null;
		$post->twitterCard = null;
		$post->recommended = [];

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

$runner->run(BlogPostPreviewTest::class);
