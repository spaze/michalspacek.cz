<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\UiPresenterMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\PrivateProperty;
use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\RedirectResponse;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingFilesDownloadTest extends TestCase
{

	private const APPLICATION_ID = 303;
	private const TOKEN = 's0m370k3n';

	private readonly UiPresenterMock $presenter;


	public function __construct(
		private readonly TrainingFilesDownload $trainingFilesDownload,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly NullSession $session,
		Application $application,
	) {
		$this->presenter = new UiPresenterMock();
		PrivateProperty::setValue($application, 'presenter', $this->presenter);
	}


	protected function tearDown(): void
	{
		$this->database->reset();
		$this->session->getSection('training')->remove();
	}


	public function testStartRedirect(): void
	{
		$this->database->setFetchFieldDefaultResult(123); // For Statuses::getStatusId()
		$this->setApplicationFetchResult(); // For TrainingApplications::getApplicationByToken()
		Assert::true($this->applicationPresenter->expectSendResponse(function (): void {
			$this->trainingFilesDownload->start('action', self::TOKEN);
		}));
		$sessionSection = $this->session->getSection('training');
		Assert::same(self::APPLICATION_ID, $sessionSection->get('applicationId'));
		Assert::same(self::TOKEN, $sessionSection->get('token'));
		$response = $this->presenter->getResponse();
		if (!$response instanceof RedirectResponse) {
			Assert::fail('Response is of a wrong type ' . get_debug_type($response));
		} else {
			Assert::same('files', $response->getUrl());
		}
	}


	public function testStartIncomplete(): void
	{
		Assert::exception(function (): void {
			$this->trainingFilesDownload->start('action', null);
		}, BadRequestException::class, 'Unknown application id, missing or invalid token');
	}


	public function testStart(): void
	{
		$sessionSection = $this->session->getSection('training');
		$sessionSection->set('applicationId', self::APPLICATION_ID);
		$sessionSection->set('token', self::TOKEN);
		$this->setApplicationFetchResult(); // For TrainingApplications::getApplicationById()
		Assert::same(self::APPLICATION_ID, $this->trainingFilesDownload->start('action', null)->getId());
	}


	private function setApplicationFetchResult(): void
	{
		$this->database->setFetchResult([
			'id' => self::APPLICATION_ID,
			'name' => null,
			'email' => null,
			'familiar' => 0,
			'company' => null,
			'street' => null,
			'city' => null,
			'zip' => null,
			'country' => null,
			'companyId' => null,
			'companyTaxId' => null,
			'note' => null,
			'status' => 'ATTENDED',
			'statusTime' => new DateTime(),
			'dateId' => null,
			'trainingId' => null,
			'trainingAction' => 'action',
			'trainingName' => 'Le //Name//',
			'trainingStart' => null,
			'trainingEnd' => null,
			'publicDate' => 1,
			'remote' => 1,
			'remoteUrl' => 'https://remote.example/',
			'remoteNotes' => null,
			'videoHref' => null,
			'feedbackHref' => null,
			'venueAction' => null,
			'venueName' => null,
			'venueNameExtended' => null,
			'venueAddress' => null,
			'venueCity' => null,
			'price' => null,
			'vatRate' => null,
			'priceVat' => null,
			'discount' => null,
			'invoiceId' => null,
			'paid' => null,
			'accessToken' => 'token',
			'sourceAlias' => 'michal-spacek',
			'sourceName' => 'Michal Špaček',
		]);
	}

}

$runner->run(TrainingFilesDownloadTest::class);
