<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\PasswordsStorages;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasswordsStoragesPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testSiteList(): void
	{
		$this->database->addFetchAllResult([
			$this->getStorageRow(1, 'Facebook', 'facebook', 10, 'www.facebook.com'),
			$this->getStorageRow(2, 'Twitter', 'twitter', 20, 'twitter.com'),
		]);
		Assert::contains('<title>www.facebook.com, twitter.com password storage disclosures', $this->render('site', 'www.facebook.com,twitter.com'));
	}


	/**
	 * The database finds only the aliases as stored, which in each of these lists is just www.facebook.com.
	 */
	public function testSiteListNotEveryAliasFound(): void
	{
		$lists = [
			'another case' => 'www.facebook.com,TWITTER.COM',
			'unknown' => 'www.facebook.com,example.com',
			'given twice' => 'www.facebook.com,www.facebook.com',
			'empty' => 'www.facebook.com,',
		];
		$results = [];
		foreach ($lists as $name => $list) {
			$this->database->addFetchAllResult([
				$this->getStorageRow(1, 'Facebook', 'facebook', 10, 'www.facebook.com'),
			]);
			$results[$name] = $this->getErrorCode('site', $list);
		}
		Assert::same(array_fill_keys(array_keys($lists), IResponse::S404_NotFound), $results);
	}


	public function testCompanyList(): void
	{
		$this->database->addFetchAllResult([
			$this->getStorageRow(1, 'Facebook', 'facebook', 10, 'www.facebook.com'),
			$this->getStorageRow(2, 'Twitter', 'twitter', 20, 'twitter.com'),
		]);
		Assert::contains('<title>Facebook, Twitter password storage disclosures', $this->render('company', 'facebook,twitter'));
	}


	public function testCompanyListNotEveryAliasFound(): void
	{
		$lists = [
			'another case' => 'facebook,TWITTER',
			'unknown' => 'facebook,example',
			'given twice' => 'facebook,facebook',
			'empty' => 'facebook,',
		];
		$results = [];
		foreach ($lists as $name => $list) {
			$this->database->addFetchAllResult([
				$this->getStorageRow(1, 'Facebook', 'facebook', 10, 'www.facebook.com'),
			]);
			$results[$name] = $this->getErrorCode('company', $list);
		}
		Assert::same(array_fill_keys(array_keys($lists), IResponse::S404_NotFound), $results);
	}


	private function render(string $action, string $param): string
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Pulse:PasswordsStorages', 'PasswordsStorages', $action);
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Pulse:PasswordsStorages', IRequest::Get, ['action' => $action, 'param' => $param]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		return (string)$html;
	}


	private function getErrorCode(string $action, string $param): ?int
	{
		try {
			$this->render($action, $param);
			return null;
		} catch (BadRequestException $e) {
			return $e->getHttpCode();
		}
	}


	/**
	 * @return array<string, int|string|DateTime|null>
	 */
	private function getStorageRow(int $companyId, string $companyName, string $companyAlias, int $siteId, string $siteAlias): array
	{
		return [
			'companyId' => $companyId,
			'companyName' => $companyName,
			'tradeName' => null,
			'sortName' => $companyName,
			'companyAlias' => $companyAlias,
			'siteId' => $siteId,
			'siteUrl' => "https://{$siteAlias}/",
			'siteAlias' => $siteAlias,
			'sharedWith' => null,
			'algoId' => 1,
			'algoAlias' => 'bcrypt',
			'algoName' => 'bcrypt',
			'algoSalted' => 1,
			'algoStretched' => 1,
			'from' => new DateTime('2020-01-01 00:00:00'),
			'fromConfirmed' => 1,
			'disclosureId' => $siteId,
			'disclosureUrl' => "https://{$siteAlias}/passwords",
			'disclosureArchive' => "https://web.archive.org/web/2020/https://{$siteAlias}/passwords",
			'disclosureNote' => null,
			'disclosurePublished' => new DateTime('2020-01-01 00:00:00'),
			'disclosureAdded' => null,
			'disclosureTypeAlias' => 'faq',
			'disclosureType' => 'FAQ',
			'attributes' => '',
			'note' => null,
		];
	}

}

TestCaseRunner::run(PasswordsStoragesPresenterTest::class);
