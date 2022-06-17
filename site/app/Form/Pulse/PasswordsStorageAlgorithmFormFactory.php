<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Pulse;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Pulse\Companies;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Sites;
use MichalSpacekCz\Pulse\SpecificSite;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class PasswordsStorageAlgorithmFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Companies $companies,
		private readonly Sites $sites,
		private readonly Passwords $passwords,
	) {
	}


	/**
	 * @param callable(?string): void $onSuccess
	 * @param int $newDisclosures
	 * @return Form
	 */
	public function create(callable $onSuccess, int $newDisclosures): Form
	{
		$form = $this->factory->create();

		// Company
		$companyContainer = $form->addContainer('company');
		$items = [];
		foreach ($this->companies->getAll() as $company) {
			$items[$company->id] = $company->name;
		}
		$selectCompany = $companyContainer->addSelect('id', 'Company:', $items)
			->setPrompt('- select company -');
		$newCompanyContainer = $companyContainer->addContainer('new');
		$inputName = $newCompanyContainer->addText('name', 'Name:');
		$newCompanyContainer->addText('dba', 'Trade name:')
			->setHtmlAttribute('title', '"doing business as"');
		$inputAlias = $newCompanyContainer->addText('alias', 'Alias:');
		$inputAlias->addConditionOn($inputName, $form::FILLED)
			->setRequired('Enter new company alias');

		$selectCompany->addConditionOn($inputName, $form::BLANK)
			->setRequired('Choose company or add a new one');
		$inputName->addConditionOn($selectCompany, $form::FILLED)
			->addRule($form::BLANK, "Company already selected, can't add a new one");

		// Site
		$siteContainer = $form->addContainer('site');
		$items = [Sites::ALL => 'all sites'];
		foreach ($this->sites->getAll() as $site) {
			$items[$site->id] = "{$site->alias} ({$site->url})";
		}
		$selectSite = $siteContainer->addSelect('id', 'Site:', $items)
			->setPrompt('- select site -');
		$newSiteContainer = $siteContainer->addContainer('new');
		$inputUrl = $newSiteContainer->addText('url', 'URL:')
			->setHtmlType('url');
		$inputAlias = $newSiteContainer->addText('alias', 'Alias:');
		$newSiteContainer->addText('sharedWith', 'Storage shared with:');

		$selectSite->addConditionOn($inputUrl, $form::BLANK)
			->setRequired('Choose site or add a new one');
		$inputUrl->addCondition($form::FILLED)  // intentionally addCondition(), there's a matching endCondition() below
			->addRule($form::URL, 'Incorrect site URL')
			->endCondition()
			->addConditionOn($selectSite, $form::FILLED)
			->addRule($form::BLANK, $message = "Site already selected, can't add a new one")
			->endCondition()
			->addCondition(function () use ($inputName, $selectSite): bool {
				return (!empty($inputName->getValue()) && $selectSite->getValue() !== Sites::ALL);
			})
			->setRequired('New site required when adding a new company');
		$inputAlias->addConditionOn($selectSite, $form::FILLED)
			->addRule($form::BLANK, $message)
			->endCondition()
			->addConditionOn($inputUrl, $form::FILLED)
			->setRequired('Enter new site alias');

		// Algo
		$algoContainer = $form->addContainer('algo');
		$items = [];
		foreach ($this->passwords->getAlgorithms() as $algo) {
			$items[$algo->id] = $algo->algo;
		}
		$selectAlgo = $algoContainer->addSelect('id', 'Algorithm:', $items)
			->setPrompt('- select algorithm -');
		$this->trainingControlsFactory->addDate(
			$algoContainer,
			'from',
			'From:',
			false,
			'YYYY-MM(-DD (HH:MM(:SS)))',
			'(\d{4}-\d{1,2}(-\d{1,2}( \d{1,2}:\d{2}(:\d{2})?)?)?)',
		);
		$algoContainer->addCheckbox('fromConfirmed', 'From confirmed');
		$algoContainer->addText('attributes', 'Attributes:');
		$algoContainer->addText('note', 'Algorithm note:');
		$newAlgoContainer = $algoContainer->addContainer('new');
		$inputAlgo = $newAlgoContainer->addText('algo', 'Algorithm:');
		$inputAlias = $newAlgoContainer->addText('alias', 'Alias:');
		$newAlgoContainer->addCheckbox('salted', 'Salted:');
		$newAlgoContainer->addCheckbox('stretched', 'Stretched:');

		$selectAlgo->addConditionOn($inputAlgo, $form::BLANK)
			->setRequired('Choose algorithm or add a new one');
		$inputAlgo->addConditionOn($selectAlgo, $form::FILLED)
			->addRule($form::BLANK, $message = "Algorithm already selected, can't add a new one");
		$inputAlias->addConditionOn($selectAlgo, $form::FILLED)
			->addRule($form::BLANK, $message)
			->endCondition()
			->addConditionOn($inputAlgo, $form::FILLED)
			->setRequired('Enter new algorithm alias');

		// Disclosures
		$items = [];
		foreach ($this->passwords->getDisclosureTypes() as $disclosure) {
			$items[$disclosure->id] = $disclosure->type;
		}
		$disclosureContainer = $form->addContainer('disclosure');
		$disclosureNewContainer = $disclosureContainer->addContainer('new');
		for ($i = 0; $i < $newDisclosures; $i++) {
			$disclosureNewCountContainer = $disclosureNewContainer->addContainer($i);
			$selectDisclosure = $disclosureNewCountContainer->addSelect('disclosure', 'Disclosure:', $items)
				->setPrompt('- select disclosure type -');
			$inputUrl = $disclosureNewCountContainer->addText('url', 'URL:')
				->setHtmlType('url');
			$inputArchive = $disclosureNewCountContainer->addText('archive', 'Archive:');
			$disclosureNewCountContainer->addText('note', 'Note:');
			$inputPublished = $this->trainingControlsFactory->addDate(
				$disclosureNewCountContainer,
				'published',
				'Published:',
				false,
				'YYYY-MM-DD (HH:MM(:SS))',
				'(\d{4}-\d{1,2}-\d{1,2}( \d{1,2}:\d{2}(:\d{2})?)?)',
			);

			if ($i == 0) {
				$selectDisclosure->setRequired('Enter at least one disclosure type');
			} else {
				$selectDisclosure->addConditionOn($inputUrl, $form::FILLED)
					->setRequired('Enter disclosure type');
			}
			$inputUrl->addCondition($form::FILLED)  // intentionally addCondition(), there's a matching endCondition() below
				->addRule($form::URL, 'Incorrect disclosure URL')
				->endCondition()
				->addConditionOn($selectDisclosure, $form::FILLED)
				->setRequired('Enter disclosure URL');
			$inputArchive->addConditionOn($inputUrl, $form::FILLED)
				->setRequired('Enter disclosure archive');
			$inputPublished->addConditionOn($selectDisclosure, $form::FILLED)
				->setRequired('Enter disclosure publish date');
		}

		$form->addSubmit('submit', 'Add');
		$form->onValidate[] = function (Form $form, ArrayHash $values): void {
			$this->validatePasswordsStorages($form, $values);
		};
		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess($this->passwords->addStorage($values) ? 'Password storage added successfully' : null);
		};
		return $form;
	}


	/**
	 * Validate submitted data.
	 *
	 * The rules for validation are:
	 * - new company, new site => ok
	 * - new company, all sites => ok
	 * - new company, existing sites => ok
	 * - existing company, new site => ok
	 * - existing company, all sites when sites exist => nope
	 * - existing company, another algo without "from" when there's one already
	 * - existing company, existing site => check if the combination already exists
	 *
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
	private function validatePasswordsStorages(Form $form, ArrayHash $values): void
	{
		if (empty($values->company->new->name)) {
			$storages = $this->passwords->getStoragesByCompanyId($values->company->id);
			$specificSites = array_filter($storages->getSites(), function ($site) {
				return $site instanceof SpecificSite;
			});
			if ($values->site->id === Sites::ALL && !empty($specificSites)) {
				$form->addError('Invalid combination, can\'t add disclosure for all sites when sites already exist');
			}
			if ($values->site->id !== null && $values->site->id !== Sites::ALL && !$storages->hasSite((string)$values->site->id)) {
				$form->addError('Invalid combination, the site is already assigned to different company');
			}
		} elseif ($this->companies->getByName($values->company->new->name)) {
			$form->addError('Can\'t add new company, duplicated name');
		}
		if (!empty($values->site->new->url) && $this->sites->getByUrl($values->site->new->url)) {
			$form->addError('Can\'t add new site, duplicated URL');
		}
		if (!empty($values->algo->new->algo) && $this->passwords->getAlgorithmByName($values->algo->new->algo)) {
			$form->addError('Can\'t add new algorithm, duplicated name');
		}
	}

}
