<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Pulse;

use DateTime;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Pulse\Sites;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class PasswordsStorageAlgorithmFormFactoryTest extends TestCase
{

	private UiForm $form;


	public function __construct(
		private readonly Database $database,
		PasswordsStorageAlgorithmFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		// Companies
		$this->database->addFetchAllResult([
			[
				'id' => 5,
				'name' => '',
				'tradeName' => '',
				'alias' => '',
				'sortName' => '',
			],
		]);
		// Sites
		$this->database->addFetchAllResult([
			[
				'id' => 6,
				'url' => '',
				'alias' => '',
			],
		]);
		// Algorithms
		$this->database->addFetchAllResult([
			[
				'id' => 7,
				'algo' => 'bcrypt',
				'alias' => 'bcrypt',
				'salted' => 1,
				'stretched' => 1,
			],
		]);
		// Disclosures
		$this->database->addFetchAllResult([
			[
				'id' => 8,
				'alias' => '',
				'type' => '',
			],
		]);
		$this->form = $formFactory->create(
			function (): void {
			},
			1,
		);
		$applicationPresenter->anchorForm($this->form);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->form->cleanErrors();
	}


	public function testCreateOnValidateSpecificSitesExist(): void
	{
		// For Passwords::getStoragesByCompanyId()
		$this->database->addFetchAllResult([
			[
				'companyId' => 1,
				'companyName' => '',
				'tradeName' => null,
				'sortName' => '',
				'companyAlias' => '',
				'siteId' => 2,
				'siteUrl' => '',
				'siteAlias' => '',
				'sharedWith' => null,
				'algoId' => 10,
				'algoAlias' => 'argon2',
				'algoName' => 'Argon2',
				'algoSalted' => 1,
				'algoStretched' => 1,
				'from' => new DateTime(),
				'fromConfirmed' => 1,
				'disclosureId' => 121,
				'disclosureUrl' => '',
				'disclosureArchive' => '',
				'disclosureNote' => null,
				'disclosurePublished' => new DateTime(),
				'disclosureAdded' => null,
				'disclosureTypeAlias' => 'faq',
				'disclosureType' => 'FAQ',
				'attributes' => '',
				'note' => null,
			],
		]);
		$this->form->setDefaults([
			'company' => [
				'id' => 5,
			],
			'site' => [
				'id' => Sites::ALL,
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Invalid combination, can't add disclosure for all sites when sites already exist"], $this->form->getErrors());
	}


	public function testCreateOnValidateSiteAssigned(): void
	{
		// For Passwords::getStoragesByCompanyId()
		$this->database->addFetchAllResult([]);
		$this->form->setDefaults([
			'company' => [
				'id' => 5,
			],
			'site' => [
				'id' => 6,
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(['Invalid combination, the site is already assigned to different company'], $this->form->getErrors());
	}


	public function testCreateOnValidateDuplicateCompany(): void
	{
		// For Companies::getByName()
		$this->database->addFetchResult([
			'id' => 9,
			'name' => '',
			'tradeName' => '',
			'alias' => '',
			'sortName' => '',
		]);
		$this->form->setDefaults([
			'company' => [
				'new' => [
					'name' => 'Nouveau',
				],
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Can't add new company, duplicated name"], $this->form->getErrors());
	}


	public function testCreateOnValidateDuplicateUrl(): void
	{
		// For Companies::getByName()
		$this->database->addFetchResult([]);
		// For Sites::getByUrl()
		$this->database->addFetchResult([
			'id' => 10,
			'url' => '',
			'alias' => '',
		]);
		$this->form->setDefaults([
			'company' => [
				'new' => [
					'name' => 'Nouveau',
				],
			],
			'site' => [
				'new' => [
					'url' => 'https://www.example/',
				],
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Can't add new site, duplicated URL"], $this->form->getErrors());
	}


	public function testCreateOnValidateDuplicateAlgo(): void
	{
		// For Companies::getByName()
		$this->database->addFetchResult([]);
		// For PasswordHashingAlgorithms::getAlgorithmByName()
		$this->database->addFetchResult([
			'id' => 11,
			'algo' => '',
			'alias' => '',
			'salted' => 1,
			'stretched' => 1,
		]);
		$this->form->setDefaults([
			'company' => [
				'new' => [
					'name' => 'Nouveau Deux',
				],
			],
			'algo' => [
				'new' => [
					'algoName' => 'Algo Land',
				],
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Can't add new algorithm, duplicated name"], $this->form->getErrors());
	}

}

TestCaseRunner::run(PasswordsStorageAlgorithmFormFactoryTest::class);
