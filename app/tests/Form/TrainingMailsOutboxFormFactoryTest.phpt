<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use Nette\Application\Application;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TrainingMailsOutboxFormFactoryTest extends TestCase
{

	private const string FEEDBACK_URL = 'https://feedback.example/';

	private ?int $sent = null;
	private readonly DateTime $trainingStart;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingMailsOutboxFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Application $application,
		private readonly TrainingApplicationStatuses $applicationStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
		private readonly NullMailer $mailer,
	) {
		$this->trainingStart = new DateTime('2024-04-03 02:01:00');
	}


	public function testCreateOnSuccess(): void
	{
		// For TrainingFiles::getFiles() => TrainingApplicationStatuses::getAllowFilesStatuses()
		$this->database->setFetchFieldDefaultResult(26);
		// For TrainingFiles::getFiles()
		$this->database->addFetchAllResult([
			[
				'fileId' => 52,
				'fileName' => 'filename.pdf',
				'start' => $this->trainingStart,
				'added' => (clone $this->trainingStart)->modify('+1 week'),
			],
		]);

		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Emails', 'Admin:Emails', 'default');
		PrivateProperty::setValue($this->application, 'presenter', $presenter);
		$application = $this->buildApplication();

		$application->setNextStatus(TrainingApplicationStatus::Reminded);
		$form = $this->formFactory->create(
			function (int $sent): void {
				$this->sent = $sent;
			},
			[$application->getId() => $application],
		);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(1, $this->sent);
		Assert::same('Připomenutí školení Training Name 3.–5. dubna 2024', $this->mailer->getMail()->getSubject());

		$application->setNextStatus(TrainingApplicationStatus::MaterialsSent);
		$form = $this->formFactory->create(
			function (int $sent): void {
				$this->sent = $sent;
			},
			[$application->getId() => $application],
		);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(1, $this->sent);
		Assert::same('Materiály ze školení Training Name', $this->mailer->getMail()->getSubject());
		Assert::contains(self::FEEDBACK_URL, $this->mailer->getMail()->getBody());

		$form->setDefaults([
			'applications' => [
				$application->getId() => [
					'feedbackRequest' => false,
				],
			],
		]);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(1, $this->sent);
		Assert::same('Materiály ze školení Training Name', $this->mailer->getMail()->getSubject());
		Assert::notContains(self::FEEDBACK_URL, $this->mailer->getMail()->getBody());
	}


	private function buildApplication(): TrainingApplication
	{
		return new TrainingApplication(
			$this->applicationStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			3212,
			'John Doe',
			'email@example.com',
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			TrainingApplicationStatus::Attended,
			new DateTime(),
			true,
			false,
			false,
			null,
			null,
			'action',
			Html::fromText('Training Name'),
			$this->trainingStart,
			new DateTime('2024-04-05 06:07:08'),
			false,
			true,
			'https://zoom.example/',
			null,
			null,
			self::FEEDBACK_URL,
			null,
			null,
			null,
			null,
			null,
			150,
			10,
			165,
			'',
			'',
			null,
			null,
			null,
			'accessToken',
			'michal-spacek',
			'Michal Špaček',
			'MŠ',
		);
	}

}

TestCaseRunner::run(TrainingMailsOutboxFormFactoryTest::class);
