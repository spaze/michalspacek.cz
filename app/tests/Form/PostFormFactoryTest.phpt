<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class PostFormFactoryTest extends TestCase
{

	private const int LOCALE_ID = 47;
	private const string EDIT_SUMMARY = 'Edit yo';
	private const int MAX_TRANSLATION_ID = 1337;

	private ?BlogPost $blogPostAdd = null;
	private ?BlogPost $blogPostEdit = null;
	private ?DefaultTemplate $templateSent = null;
	private DefaultTemplate $template;


	public function __construct(
		private readonly Database $database,
		private readonly PostFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly DateTimeMachineFactory $dateTimeFactory,
		TemplateFactory $templateFactory,
	) {
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Blog', 'Admin:Blog', 'default');
		$this->template = $templateFactory->createTemplate($presenter);
	}


	#[Override]
	protected function setUp(): void
	{
		// Data needed to create the form
		$twitterCardResult = [
			'cardId' => 46,
			'card' => 'summary',
			'title' => 'Summary Card',
		];
		$this->database->addFetchAllResult([$twitterCardResult]);
		$this->database->addFetchPairsResult([self::LOCALE_ID => 'en_US']);
		// For onSuccess queries
		$this->database->setFetchDefaultResult($twitterCardResult);
		// Upcoming trainings
		$this->database->addFetchAllResult([]);
		// Blog post edits
		$this->database->addFetchAllResult([]);
		// Max translation group id
		$this->database->setFetchFieldDefaultResult(self::MAX_TRANSLATION_ID);

		$this->database->setDefaultInsertId('48');
		$this->localeLinkGenerator->setLinks(['en_US' => 'https://com.example/']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->blogPostAdd = null;
		$this->blogPostEdit = null;
		$this->templateSent = null;
		$this->database->reset();
	}


	public function testDefaultValuesAdd(): void
	{
		$form = $this->buildFormAdd();
		$translationGroup = $form->getComponent('translationGroup');
		assert($translationGroup instanceof TextInput);
		Assert::same(self::MAX_TRANSLATION_ID + 1, $translationGroup->getValue());
	}


	public function testDefaultValuesEdit(): void
	{
		$form = $this->buildFormEdit();
		$translationGroup = $form->getComponent('translationGroup');
		assert($translationGroup instanceof TextInput);
		Assert::same(null, $translationGroup->getValue());
	}


	public function testCreateOnSuccessAdd(): void
	{
		$form = $this->buildFormAdd();
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(48, $this->blogPostAdd?->getId());
		Assert::null($this->blogPostEdit);
		Assert::null($this->templateSent);
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO blog_post_edits'));
	}


	public function testCreateOnClickPreviewAdd(): void
	{
		$form = $this->buildFormAdd();
		$submit = $form->getComponent('preview');
		assert($submit instanceof SubmitButton);
		Arrays::invoke($submit->onClick, $form);
		if ($this->templateSent instanceof DefaultTemplate) {
			Assert::contains('<strong>Title</strong>', $this->templateSent->renderToString());
		} else {
			Assert::fail('A template should be sent on preview');
		}
	}


	public function testCreateOnSuccessEdit(): void
	{
		$form = $this->buildFormEdit();
		Arrays::invoke($form->onSuccess, $form);
		Assert::null($this->blogPostAdd);
		Assert::same(49, $this->blogPostEdit?->getId());
		Assert::null($this->templateSent);
		$queryParams = [
			[
				'key_blog_post' => 49,
				'edited_at' => '2024-12-15 00:33:08',
				'edited_at_timezone' => 'Europe/Prague',
				'summary' => self::EDIT_SUMMARY,
			],
		];
		Assert::same($queryParams, $this->database->getParamsArrayForQuery('INSERT INTO blog_post_edits'));
	}


	public function testCreateOnClickPreviewEdit(): void
	{
		$form = $this->buildFormEdit();
		$submit = $form->getComponent('preview');
		assert($submit instanceof SubmitButton);
		Arrays::invoke($submit->onClick, $form);
		if ($this->templateSent instanceof DefaultTemplate) {
			Assert::contains('<strong>Title</strong>', $this->templateSent->renderToString());
		} else {
			Assert::fail('A template should be sent on preview');
		}
	}


	private function setFormDefaults(UiForm $form): void
	{
		$form->setDefaults([
			'locale' => self::LOCALE_ID,
			'published' => '2024-12-14 15:33:08',
			'title' => '**Title**',
			'twitterCard' => 'summary',
			'editSummary' => self::EDIT_SUMMARY,
		]);
	}


	private function buildFormAdd(): UiForm
	{
		$form = $this->formFactory->create(
			function (BlogPost $post): void {
				$this->blogPostAdd = $post;
			},
			function (BlogPost $post): void {
				$this->blogPostEdit = $post;
			},
			$this->template,
			function (?DefaultTemplate $template): void {
				$this->templateSent = $template;
			},
			null,
		);
		$this->setFormDefaults($form);
		$this->applicationPresenter->anchorForm($form);
		return $form;
	}


	/**
	 * @return UiForm
	 */
	private function buildFormEdit(): UiForm
	{
		$post = new BlogPost(
			49,
			'',
			self::LOCALE_ID,
			'en_US',
			null,
			Html::fromText('title'),
			'title',
			Html::fromText('lead'),
			'lead',
			Html::fromText('text'),
			'text',
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
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable('2024-12-15 00:33:08'));

		$form = $this->formFactory->create(
			function (BlogPost $post): void {
				$this->blogPostAdd = $post;
			},
			function (BlogPost $post): void {
				$this->blogPostEdit = $post;
			},
			$this->template,
			function (?DefaultTemplate $template): void {
				$this->templateSent = $template;
			},
			$post,
		);
		$this->setFormDefaults($form);
		$this->applicationPresenter->anchorForm($form);
		return $form;
	}

}

TestCaseRunner::run(PostFormFactoryTest::class);
