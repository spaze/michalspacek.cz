<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Form\Pulse\PasswordsStorageAlgorithmFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageWildcardSite;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasswordsTest extends TestCase
{

	public function __construct(
		private readonly Passwords $passwords,
		private readonly Database $database,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasswordsStorageAlgorithmFormFactory $formFactory,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetAllStorages(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'companyId' => 3,
				'companyName' => 'Simplia, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Simplia, s.r.o.',
				'companyAlias' => 'simplia',
				'siteId' => null,
				'siteUrl' => null,
				'siteAlias' => null,
				'sharedWith' => null,
				'algoId' => 1,
				'algoAlias' => 'bcrypt',
				'algoName' => 'bcrypt',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime('2014-06-23 00:00:00'),
				'fromConfirmed' => 1,
				'disclosureId' => 6,
				'disclosureUrl' => 'https://twitter.com/petrsoukup/status/481077407722774528',
				'disclosureArchive' => 'https://web.archive.org/web/20160721160917/https://twitter.com/petrsoukup/status/481077407722774528',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2014-06-23 16:12:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'twitter-private',
				'disclosureType' => 'Twitter (private account)',
				'attributes' => '{"outer": ["AES"]}',
				'note' => null,
			],
			[
				'companyId' => 3,
				'companyName' => 'Simplia, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Simplia, s.r.o.',
				'companyAlias' => 'simplia',
				'siteId' => null,
				'siteUrl' => null,
				'siteAlias' => null,
				'sharedWith' => null,
				'algoId' => 3,
				'algoAlias' => 'sha256',
				'algoName' => 'SHA-256',
				'algoSalted' => 0,
				'algoStretched' => 0,
				'from' => null,
				'fromConfirmed' => 0,
				'disclosureId' => 6,
				'disclosureUrl' => 'https://twitter.com/petrsoukup/status/481077407722774528',
				'disclosureArchive' => 'https://web.archive.org/web/20160721160917/https://twitter.com/petrsoukup/status/481077407722774528',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2014-06-23 16:12:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'twitter-private',
				'disclosureType' => 'Twitter (private account)',
				'attributes' => '',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 10,
				'algoAlias' => 'argon2',
				'algoName' => 'Argon2',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime('2018-02-05 00:00:00'),
				'fromConfirmed' => 1,
				'disclosureId' => 121,
				'disclosureUrl' => 'https://www.zlavomat.sk/kontakt#je-moje-heslo-u-vas-ulozene-bezpecne',
				'disclosureArchive' => 'https://web.archive.org/web/20180205182943/https://www.zlavomat.sk/kontakt#je-moje-heslo-u-vas-ulozene-bezpecne',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2018-02-05 00:00:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'faq',
				'disclosureType' => 'FAQ',
				'attributes' => '{"outer": ["AES-256"], "params": {"variant": "Argon2i"}}',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 1,
				'algoAlias' => 'bcrypt',
				'algoName' => 'bcrypt',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => 1,
				'disclosureId' => 2,
				'disclosureUrl' => 'https://twitter.com/spazef0rze/status/476468449196404738',
				'disclosureArchive' => 'https://web.archive.org/web/20160721160007/https:/twitter.com/spazef0rze/status/476468449196404738',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2014-06-10 22:58:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'twitter-private',
				'disclosureType' => 'Twitter (private account)',
				'attributes' => '{"outer": ["AES-256-CBC"], "params": {"cost": 10}}',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 1,
				'algoAlias' => 'bcrypt',
				'algoName' => 'bcrypt',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => 1,
				'disclosureId' => 1,
				'disclosureUrl' => 'https://www.michalspacek.cz/prednasky/jak-jsme-zlepsili-zabezpeceni-slevomatu-pixdevday/bcrypt-aes',
				'disclosureArchive' => 'https://archive.is/A5bfH',
				'disclosureNote' => 'slide 10',
				'disclosurePublished' => new DateTime('2014-06-14 17:50:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'talk',
				'disclosureType' => 'talk',
				'attributes' => '{"outer": ["AES-256-CBC"], "params": {"cost": 10}}',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 1,
				'algoAlias' => 'bcrypt',
				'algoName' => 'bcrypt',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => 1,
				'disclosureId' => 75,
				'disclosureUrl' => 'https://www.zlavomat.sk/kontakt#je-moje-heslo-u-vas-ulozene-bezpecne',
				'disclosureArchive' => 'https://archive.is/RbQtI',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2016-12-16 13:36:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'faq',
				'disclosureType' => 'FAQ',
				'attributes' => '{"outer": ["AES-256-CBC"], "params": {"cost": 10}}',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 2,
				'algoAlias' => 'sha1',
				'algoName' => 'SHA-1',
				'algoSalted' => 0,
				'algoStretched' => 0,
				'from' => null,
				'fromConfirmed' => 0,
				'disclosureId' => 2,
				'disclosureUrl' => 'https://twitter.com/spazef0rze/status/476468449196404738',
				'disclosureArchive' => 'https://web.archive.org/web/20160721160007/https:/twitter.com/spazef0rze/status/476468449196404738',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2014-06-10 22:58:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'twitter-private',
				'disclosureType' => 'Twitter (private account)',
				'attributes' => '',
				'note' => null,
			],
			[
				'companyId' => 1,
				'companyName' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'sortName' => 'Slevomat.cz, s.r.o.',
				'companyAlias' => 'slevomat.cz',
				'siteId' => 2,
				'siteUrl' => 'https://www.zlavomat.sk/',
				'siteAlias' => 'www.zlavomat.sk',
				'sharedWith' => null,
				'algoId' => 2,
				'algoAlias' => 'sha1',
				'algoName' => 'SHA-1',
				'algoSalted' => 0,
				'algoStretched' => 0,
				'from' => null,
				'fromConfirmed' => 0,
				'disclosureId' => 3,
				'disclosureUrl' => 'https://www.michalspacek.cz/prednasky/jak-jsme-zlepsili-zabezpeceni-slevomatu-pixdevday/sha1',
				'disclosureArchive' => 'https://archive.is/FfPit',
				'disclosureNote' => 'slide 9',
				'disclosurePublished' => new DateTime('2014-06-14 17:50:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'talk',
				'disclosureType' => 'talk',
				'attributes' => '',
				'note' => null,
			],
			[
				'companyId' => 112,
				'companyName' => 'SYSYstems, s.r.o.',
				'tradeName' => null,
				'sortName' => 'SYSYstems, s.r.o.',
				'companyAlias' => 'sysystems',
				'siteId' => 111,
				'siteUrl' => 'https://www.webalert.cz/',
				'siteAlias' => 'www.webalert.cz',
				'sharedWith' => null,
				'algoId' => 1,
				'algoAlias' => 'bcrypt',
				'algoName' => 'bcrypt',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => null,
				'fromConfirmed' => 0,
				'disclosureId' => 158,
				'disclosureUrl' => 'https://blog.webalert.cz/2018/07/jak-ukladame-vase-hesla.html',
				'disclosureArchive' => 'https://web.archive.org/web/20180719115018/https://blog.webalert.cz/2018/07/jak-ukladame-vase-hesla.html',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime('2018-07-17 21:45:00'),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'blog',
				'disclosureType' => 'blog',
				'attributes' => '{"params": {"cost": 13}}',
				'note' => null,
			],
		]);
		$storageRegistry = $this->passwords->getAllStorages(null, 'a-z', null);
		$site = $storageRegistry->getSite('2');
		if (!$site instanceof StorageSpecificSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', StorageSpecificSite::class, get_debug_type($site)));
		} else {
			Assert::same('https://www.zlavomat.sk/', $site->getUrl());
			$latestAlgorithm = $site->getLatestAlgorithm();
			Assert::same('AES-256(Argon2(password))', $latestAlgorithm->getFullAlgo());
			Assert::same('Argon2', $latestAlgorithm->getName());
			Assert::same(121, $latestAlgorithm->getLatestDisclosure()->getId());
			Assert::count(3, $site->getAlgorithms());
			$argon2Key = '10-1517785200';
			$disclosures = $site->getAlgorithms()[$argon2Key]->getDisclosures();
			Assert::count(1, $disclosures);
			Assert::same(121, $disclosures[0]->getId());
			$bcryptKey = '1-1398895200';
			$disclosures = $site->getAlgorithms()[$bcryptKey]->getDisclosures();
			Assert::count(3, $disclosures);
			Assert::same(2, $disclosures[0]->getId());
			Assert::same(1, $disclosures[1]->getId());
			Assert::same(75, $disclosures[2]->getId());
			$sha1Key = '2-null';
			$disclosures = $site->getAlgorithms()[$sha1Key]->getDisclosures();
			Assert::count(2, $disclosures);
			Assert::same(2, $disclosures[0]->getId());
			Assert::same(3, $disclosures[1]->getId());
			Assert::same([$bcryptKey, $sha1Key], array_keys($site->getHistoricalAlgorithms()));
		}

		$site = $storageRegistry->getSite('111');
		if (!$site instanceof StorageSpecificSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', StorageSpecificSite::class, get_debug_type($site)));
		} else {
			Assert::same('SYSYstems, s.r.o.', $site->getCompany()->getCompanyName());
			$latestAlgorithm = $site->getLatestAlgorithm();
			Assert::null($latestAlgorithm->getFullAlgo());
			Assert::same('bcrypt', $latestAlgorithm->getName());
			Assert::same(158, $latestAlgorithm->getLatestDisclosure()->getId());
			Assert::same([], array_keys($site->getHistoricalAlgorithms()));
		}

		$company = $storageRegistry->getCompany(3);
		Assert::same('Simplia, s.r.o.', $company->getCompanyName());
		$site = $storageRegistry->getSite('all-3');
		if (!$site instanceof StorageWildcardSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', StorageWildcardSite::class, get_debug_type($site)));
		} else {
			Assert::same('Simplia, s.r.o.', $site->getCompany()->getCompanyName());
			$latestAlgorithm = $site->getLatestAlgorithm();
			Assert::same('AES(bcrypt(password))', $latestAlgorithm->getFullAlgo());
			Assert::same('bcrypt', $latestAlgorithm->getName());
			Assert::same(6, $latestAlgorithm->getLatestDisclosure()->getId());
			Assert::count(2, $site->getAlgorithms());
			$bcryptKey = '1-1403474400';
			$disclosures = $site->getAlgorithms()[$bcryptKey]->getDisclosures();
			Assert::count(1, $disclosures);
			Assert::same(6, $disclosures[0]->getId());
			$sha256Key = '3-null';
			$disclosures = $site->getAlgorithms()[$sha256Key]->getDisclosures();
			Assert::count(1, $disclosures);
			Assert::same(6, $site->getAlgorithms()[$sha256Key]->getDisclosures()[0]->getId());
			Assert::same([$sha256Key], array_keys($site->getHistoricalAlgorithms()));
		}
	}


	public function testAddStorage(): void
	{
		// Company
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'name' => 'Slevomat.cz, s.r.o.',
				'tradeName' => null,
				'alias' => 'slevomat.cz',
				'sortName' => 'Slevomat.cz, s.r.o.',
			],
		]);
		// Site
		$this->database->addFetchAllResult([
			[
				'id' => 2,
				'url' => 'https://site.example',
				'alias' => 'site.example',
			],
		]);
		// Algorithm
		$this->database->addFetchAllResult([
			[
				'id' => 3,
				'algo' => 'bcrypt',
				'alias' => 'bcrypt',
				'salted' => 1,
				'stretched' => 1,
			],
		]);
		// Disclosure types
		$this->database->addFetchAllResult([
			[
				'id' => 4,
				'type' => 'Twitter',
				'alias' => 'twitter',
			],
		]);
		// Disclosure id
		$this->database->addFetchFieldResult(5);
		// Storage id
		$this->database->addFetchFieldResult(6);

		$form = $this->getForm();
		$form->setDefaults([
			'company' => [
				'id' => '1',
			],
			'site' => [
				'id' => '2',
			],
			'algo' => [
				'id' => '3',
			],
			'disclosure' => [
				'new' => [
					[
						'url' => 'https://disclosure.example/',
						'archive' => 'https://archive.disclosure.example/',
						'disclosureType' => '4',
						'note' => 'note',
						'published' => '2020-11-22',
					],
				],
			],
		]);

		$this->passwords->addStorage($form->getFormValues());
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO companies'));
		Assert::same(
			[['key_password_disclosures' => 5, 'key_password_storages' => 6]],
			$this->database->getParamsArrayForQuery('INSERT INTO password_disclosures_password_storages'),
			'Passwords::pairDisclosureStorage() result not as expected',
		);
	}


	public function testAddStorageNewItems(): void
	{
		// Company
		$this->database->addFetchAllResult([]);
		// Site
		$this->database->addFetchAllResult([]);
		// Algorithm
		$this->database->addFetchAllResult([]);
		// Disclosure types
		$this->database->addFetchAllResult([
			[
				'id' => 4,
				'type' => 'Twitter',
				'alias' => 'twitter',
			],
		]);
		// companies id
		$this->database->addInsertId('5');
		// sites id
		$this->database->addInsertId('6');
		// password_algos id
		$this->database->addInsertId('7');
		// password_disclosures id
		$this->database->addInsertId('8');
		// password_storages id
		$this->database->addInsertId('9');

		$form = $this->getForm();
		$form->setDefaults([
			'company' => [
				'new' => [
					'name' => 'Slevomat.cz, s.r.o.',
					'dba' => '',
					'alias' => 'slevomat.cz',
				],
				'id' => null,
			],
			'site' => [
				'new' => [
					'url' => 'https://sl.example',
					'alias' => 'sl.example',
					'sharedWith' => '',
				],
				'id' => null,
			],
			'algo' => [
				'new' => [
					'algoName' => 'JavasCrypt',
					'alias' => 'javascrypt',
					'salted' => true,
					'stretched' => true,
				],
				'id' => null,
				'from' => '2001-02-03 04:05:06',
				'fromConfirmed' => true,
				'attributes' => '{"foo":"bar"}',
				'note' => 'algo note',
			],
			'disclosure' => [
				'new' => [
					[
						'url' => 'https://di.example/',
						'archive' => 'https://ar.di.example/',
						'disclosureType' => '4',
						'note' => 'note',
						'published' => '2020-11-22',
					],
				],
			],
		]);
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable('2020-01-01 12:34:56'));

		$this->passwords->addStorage($form->getFormValues());
		Assert::same(
			[[
				'name' => 'Slevomat.cz, s.r.o.',
				'trade_name' => null,
				'alias' => 'slevomat.cz',
				'added' => '2020-01-01 12:34:56',
			]],
			$this->database->getParamsArrayForQuery('INSERT INTO companies'),
		);
		Assert::same(
			[[
				'url' => 'https://sl.example',
				'alias' => 'sl.example',
				'shared_with' => null,
				'key_companies' => 5,
				'added' => '2020-01-01 12:34:56',
			]],
			$this->database->getParamsArrayForQuery('INSERT INTO sites'),
		);
		Assert::same(
			[[
				'algo' => 'JavasCrypt',
				'alias' => 'javascrypt',
				'salted' => true,
				'stretched' => true,
			]],
			$this->database->getParamsArrayForQuery('INSERT INTO password_algos'),
		);
		Assert::same(
			[[
				'key_password_disclosure_types' => 4,
				'url' => 'https://di.example/',
				'archive' => 'https://ar.di.example/',
				'note' => 'note',
				'published' => '2020-01-01 12:34:56',
				'added' => '2020-01-01 12:34:56',
			]],
			$this->database->getParamsArrayForQuery('INSERT INTO password_disclosures'),
		);
		Assert::same(
			[[
				'key_companies' => null,
				'key_password_algos' => 7,
				'key_sites' => 6,
				'from' => '2001-02-03 04:05:06',
				'from_confirmed' => true,
				'attributes' => '{"foo":"bar"}',
				'note' => 'algo note',
			]],
			$this->database->getParamsArrayForQuery('INSERT INTO password_storages'),
		);
		Assert::same(
			[['key_password_disclosures' => 8, 'key_password_storages' => 9]],
			$this->database->getParamsArrayForQuery('INSERT INTO password_disclosures_password_storages'),
			'Passwords::pairDisclosureStorage() result not as expected',
		);
	}


	private function getForm(): UiForm
	{
		$form = $this->formFactory->create(
			function (): void {
				// This won't be called in this test anyway
			},
			1,
		);
		$this->applicationPresenter->anchorForm($form);
		return $form;
	}

}

TestCaseRunner::run(PasswordsTest::class);
