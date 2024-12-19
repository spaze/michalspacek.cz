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
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Nette\Application\Application;
use Nette\Utils\Arrays;
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
		private readonly NullMailer $mailer,
		private readonly TrainingTestDataFactory $dataFactory,
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
		$application = $this->dataFactory->getTrainingApplication(
			3212,
			name: 'John Doe',
			email: 'email@example.com',
			trainingStart: $this->trainingStart,
			trainingEnd: new DateTime('2024-04-05 06:07:08'),
			remote: true,
			remoteUrl: 'https://zoom.example/',
			feedbackHref: self::FEEDBACK_URL,
		);

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

}

TestCaseRunner::run(TrainingMailsOutboxFormFactoryTest::class);
