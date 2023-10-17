<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use MichalSpacekCz\Pulse\SpecificSite;
use MichalSpacekCz\Pulse\WildcardSite;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class PasswordsTest extends TestCase
{

	public function __construct(
		private readonly Passwords $passwords,
		private readonly Database $database,
	) {
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => new DateTime('2014-06-23 00:00:00'),
				'fromConfirmed' => true,
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
				'algoSalted' => false,
				'algoStretched' => false,
				'from' => null,
				'fromConfirmed' => false,
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => new DateTime('2018-02-05 00:00:00'),
				'fromConfirmed' => true,
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => true,
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => true,
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => new DateTime('2014-05-01 00:00:00'),
				'fromConfirmed' => true,
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
				'algoSalted' => false,
				'algoStretched' => false,
				'from' => null,
				'fromConfirmed' => false,
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
				'algoSalted' => false,
				'algoStretched' => false,
				'from' => null,
				'fromConfirmed' => false,
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
				'algoSalted' => true,
				'algoStretched' => true,
				'from' => null,
				'fromConfirmed' => false,
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
		if (!$site instanceof SpecificSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', SpecificSite::class, get_debug_type($site)));
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
		if (!$site instanceof SpecificSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', SpecificSite::class, get_debug_type($site)));
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
		if (!$site instanceof WildcardSite) {
			Assert::fail(sprintf('The site should be a %s instance, but it is a %s', WildcardSite::class, get_debug_type($site)));
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

}

TestCaseRunner::run(PasswordsTest::class);
