<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Pulse;

use DateTime;
use MichalSpacekCz\Pulse\Sites;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\Arrays;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasswordsStorageAlgorithmFormFactoryTest extends TestCase
{

	private Form $form;


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


	/**
	 * Neither a company chosen nor a new one, which the select's own rule reports.
	 */
	public function testCreateOnValidateNoCompany(): void
	{
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same([], $this->form->getErrors());
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


	public function testCreateOnValidateDuplicateCompanyAlias(): void
	{
		// For Companies::getByName()
		$this->database->addFetchResult([]);
		// For Companies::getByAlias()
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
					'alias' => 'nouveau',
				],
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Can't add new company, duplicated alias"], $this->form->getErrors());
		Assert::same(['name', 'Nouveau', 'alias', 'nouveau'], $this->database->getParamsForQueryContaining('FROM companies'));
	}


	public function testCreateOnValidateDuplicateSiteAlias(): void
	{
		// For Companies::getByName()
		$this->database->addFetchResult([]);
		// For Sites::getByUrl()
		$this->database->addFetchResult([]);
		// For Sites::getByAlias()
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
					'alias' => 'www.example',
				],
			],
		]);
		Arrays::invoke($this->form->onValidate, $this->form);
		Assert::same(["Can't add new site, duplicated alias"], $this->form->getErrors());
		Assert::same(['url', 'https://www.example/', 'alias', 'www.example'], $this->database->getParamsForQueryContaining('FROM sites WHERE'));
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


	/**
	 * Empty when an existing company is chosen. Without a comma, which separates the aliases of a comparison in the URL.
	 */
	public function testCreateCompanyAlias(): void
	{
		$error = ['messages.forms.validateSlugParamsError'];
		Assert::same([
			'' => [],
			'slevomat.cz' => [],
			'Slevomat.cz' => $error,
			'slevomat,zlavomat' => $error,
			'slevomat.cz/zlavomat' => $error,
		], $this->getNewAliasErrors('company', ['', 'slevomat.cz', 'Slevomat.cz', 'slevomat,zlavomat', 'slevomat.cz/zlavomat']));
	}


	/**
	 * Empty when an existing site is chosen. A site's alias can have a path, unlike a company's.
	 */
	public function testCreateSiteAlias(): void
	{
		$error = ['messages.forms.validateSlugParamsError'];
		Assert::same([
			'' => [],
			'www.zlavomat.sk' => [],
			'my.teamio.com/recruit' => [],
			'www.Zlavomat.sk' => $error,
			'www.zlavomat.sk,www.slevomat.cz' => $error,
		], $this->getNewAliasErrors('site', ['', 'www.zlavomat.sk', 'my.teamio.com/recruit', 'www.Zlavomat.sk', 'www.zlavomat.sk,www.slevomat.cz']));
	}


	/**
	 * @param list<string> $aliases
	 * @return array<string, list<string|Stringable>>
	 */
	private function getNewAliasErrors(string $container, array $aliases): array
	{
		$input = $this->form->getComponent("{$container}-new-alias");
		assert($input instanceof TextInput);
		$results = [];
		foreach ($aliases as $alias) {
			$this->form->setDefaults([$container => ['new' => ['alias' => $alias]]]);
			$input->validate();
			$results[$alias] = $input->getErrors();
		}
		return $results;
	}

}

TestCaseRunner::run(PasswordsStorageAlgorithmFormFactoryTest::class);
