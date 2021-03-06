<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Pulse;

use MichalSpacekCz\Form\Controls\Date;
use MichalSpacekCz\Form\ProtectedForm;
use MichalSpacekCz\Pulse\Companies;
use MichalSpacekCz\Pulse\Passwords;
use MichalSpacekCz\Pulse\Sites;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;

class PasswordsStorages extends ProtectedForm
{

	use Date;


	public function __construct(IContainer $parent, string $name, int $newDisclosures, Companies $companies, Sites $sites, Passwords $passwords)
	{
		parent::__construct($parent, $name);

		// Company
		$companyContainer = $this->addContainer('company');
		$items = [];
		foreach ($companies->getAll() as $company) {
			$items[$company->id] = $company->name;
		}
		$selectCompany = $companyContainer->addSelect('id', 'Company:', $items)
			->setPrompt('- select company -');
		$newCompanyContainer = $companyContainer->addContainer('new');
		$inputName = $newCompanyContainer->addText('name', 'Name:');
		$newCompanyContainer->addText('dba', 'Trade name:')
			->setHtmlAttribute('title', '"doing business as"');
		$inputAlias = $newCompanyContainer->addText('alias', 'Alias:');
		$inputAlias->addConditionOn($inputName, self::FILLED)
			->setRequired('Enter new company alias');

		$selectCompany->addConditionOn($inputName, self::BLANK)
			->setRequired('Choose company or add a new one');
		$inputName->addConditionOn($selectCompany, self::FILLED)
			->addRule(self::BLANK, "Company already selected, can't add a new one");

		// Site
		$siteContainer = $this->addContainer('site');
		$items = [Sites::ALL => 'all sites'];
		foreach ($sites->getAll() as $site) {
			$items[$site->id] = "{$site->alias} ({$site->url})";
		}
		$selectSite = $siteContainer->addSelect('id', 'Site:', $items)
			->setPrompt('- select site -');
		$newSiteContainer = $siteContainer->addContainer('new');
		$inputUrl = $newSiteContainer->addText('url', 'URL:')
			->setHtmlType('url');
		$inputAlias = $newSiteContainer->addText('alias', 'Alias:');
		$newSiteContainer->addText('sharedWith', 'Storage shared with:');

		$selectSite->addConditionOn($inputUrl, self::BLANK)
			->setRequired('Choose site or add a new one');
		$inputUrl->addCondition(self::FILLED)  // intentionally addCondition(), there's a matching endCondition() below
			->addRule(self::URL, 'Incorrect site URL')
			->endCondition()
			->addConditionOn($selectSite, self::FILLED)
			->addRule(self::BLANK, $message = "Site already selected, can't add a new one")
			->endCondition()
			->addCondition(function ($item) use ($inputName, $selectSite) {
				return (!empty($inputName->getValue()) && $selectSite->getValue() !== Sites::ALL);
			})
			->setRequired('New site required when adding a new company');
		$inputAlias->addConditionOn($selectSite, self::FILLED)
			->addRule(self::BLANK, $message)
			->endCondition()
			->addConditionOn($inputUrl, self::FILLED)
			->setRequired('Enter new site alias');

		// Algo
		$algoContainer = $this->addContainer('algo');
		$items = [];
		foreach ($passwords->getAlgorithms() as $algo) {
			$items[$algo->id] = $algo->algo;
		}
		$selectAlgo = $algoContainer->addSelect('id', 'Algorithm:', $items)
			->setPrompt('- select algorithm -');
		$this->addFromDate($algoContainer, 'from', 'From:');
		$algoContainer->addCheckbox('fromConfirmed', 'From confirmed');
		$algoContainer->addText('attributes', 'Attributes:');
		$algoContainer->addText('note', 'Algorithm note:');
		$newAlgoContainer = $algoContainer->addContainer('new');
		$inputAlgo = $newAlgoContainer->addText('algo', 'Algorithm:');
		$inputAlias = $newAlgoContainer->addText('alias', 'Alias:');
		$newAlgoContainer->addCheckbox('salted', 'Salted:');
		$newAlgoContainer->addCheckbox('stretched', 'Stretched:');

		$selectAlgo->addConditionOn($inputAlgo, self::BLANK)
			->setRequired('Choose algorithm or add a new one');
		$inputAlgo->addConditionOn($selectAlgo, self::FILLED)
			->addRule(self::BLANK, $message = "Algorithm already selected, can't add a new one");
		$inputAlias->addConditionOn($selectAlgo, self::FILLED)
			->addRule(self::BLANK, $message)
			->endCondition()
			->addConditionOn($inputAlgo, self::FILLED)
			->setRequired('Enter new algorithm alias');

		// Disclosures
		$items = [];
		foreach ($passwords->getDisclosureTypes() as $disclosure) {
			$items[$disclosure->id] = $disclosure->type;
		}
		$disclosureContainer = $this->addContainer('disclosure');
		$disclosureNewContainer = $disclosureContainer->addContainer('new');
		for ($i = 0; $i < $newDisclosures; $i++) {
			$disclosureNewCountContainer = $disclosureNewContainer->addContainer($i);
			$selectDisclosure = $disclosureNewCountContainer->addSelect('disclosure', 'Disclosure:', $items)
				->setPrompt('- select disclosure type -');
			$inputUrl = $disclosureNewCountContainer->addText('url', 'URL:')
				->setHtmlType('url');
			$inputArchive = $disclosureNewCountContainer->addText('archive', 'Archive:');
			$disclosureNewCountContainer->addText('note', 'Note:');
			$inputPublished = $this->addPublishedDate($disclosureNewCountContainer, 'published', 'Published:');

			if ($i == 0) {
				$selectDisclosure->setRequired('Enter at least one disclosure type');
			} else {
				$selectDisclosure->addConditionOn($inputUrl, self::FILLED)
					->setRequired('Enter disclosure type');
			}
			$inputUrl->addCondition(self::FILLED)  // intentionally addCondition(), there's a matching endCondition() below
				->addRule(self::URL, 'Incorrect disclosure URL')
				->endCondition()
				->addConditionOn($selectDisclosure, self::FILLED)
				->setRequired('Enter disclosure URL');
			$inputArchive->addConditionOn($inputUrl, self::FILLED)
				->setRequired('Enter disclosure archive');
			$inputPublished->addConditionOn($selectDisclosure, self::FILLED)
				->setRequired('Enter disclosure publish date');
		}

		$this->addSubmit('submit', 'Add');
	}


	private function addFromDate(Container $container, string $name, ?string $label = null, ?bool $required = false): TextInput
	{
		return $this->addDate(
			$container,
			$name,
			$label,
			$required,
			'YYYY-MM(-DD (HH:MM(:SS)))',
			'(\d{4}-\d{1,2}(-\d{1,2}( \d{1,2}:\d{2}(:\d{2})?)?)?)',
		);
	}


	private function addPublishedDate(Container $container, string $name, ?string $label = null, ?bool $required = false): TextInput
	{
		return $this->addDate(
			$container,
			$name,
			$label,
			$required,
			'YYYY-MM-DD (HH:MM(:SS))',
			'(\d{4}-\d{1,2}-\d{1,2}( \d{1,2}:\d{2}(:\d{2})?)?)',
		);
	}

}
