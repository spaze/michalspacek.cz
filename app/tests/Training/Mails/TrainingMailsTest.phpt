<?php
declare(strict_types = 1);

namespace Training\Mails;

use DateTime;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Mails\TrainingMails;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingMailsTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly TemplateFactory $templateFactory,
		private readonly TrainingMails $trainingMails,
		private readonly NullMailer $mailer,
		private readonly NullLogger $logger,
	) {
	}


	public function testSendSignUpMail(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Homepage', 'Foo', 'bar');
		$template = $this->templateFactory->createTemplate($presenter);
		$this->trainingMails->sendSignUpMail(
			123,
			$template,
			'to@example.com',
			'To Bias',
			new DateTime(),
			new DateTime(),
			'Ing. Train',
			Html::fromText('Ing. Train'),
			true,
			'Venue',
			null,
			null,
			null,
		);
		Assert::same(['Sending sign-up email to application id: 123, training: Ing. Train'], $this->logger->getLogged());
		Assert::same(['to@example.com' => 'To Bias'], $this->mailer->getMail()->getHeader('To'));
		Assert::contains('potvrzuji Vaši registraci na školení Ing. Train', $this->mailer->getMail()->getBody());
	}

}

TestCaseRunner::run(TrainingMailsTest::class);
