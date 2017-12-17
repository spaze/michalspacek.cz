<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Training review form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingReview extends ProtectedForm
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, array $applications, \Nette\Database\Row $date)
	{
		parent::__construct($parent, $name);

		$select = $this->addSelect('application', 'Účastník:', ['' => ($date->public ? '- vyberte účastníka -' : '- firemní ohlas -')] + $applications);
		if ($date->public) {
			$select->setRequired('Vyberte účastníka');
		}
		$checkbox = $this->addCheckbox('overwriteName', 'Přepsat jméno:');
		if (!$date->public) {
			$checkbox->addConditionOn($this['application'], self::EQUAL, '')
				->setRequired('Pro firemní ohlas je potřeba přepsat jméno');
		}
		$this->addText('name', 'Jméno:')
			->setRequired(false)
			->addConditionOn($this['overwriteName'], self::EQUAL, true)
				->setRequired('Zadejte prosím jméno')
				->addRule(self::MIN_LENGTH, 'Minimální délka jména je %d znaky', 3)
				->addRule(self::MAX_LENGTH, 'Maximální délka jména je %d znaků', 200);
		$checkbox = $this->addCheckbox('overwriteCompany', 'Přepsat firmu:');
		if (!$date->public) {
			$checkbox->addConditionOn($this['application'], self::EQUAL, '')
				->setRequired('Pro firemní ohlas je potřeba přepsat firmu');
		}
		$this->addText('company', 'Firma:')
			->setRequired(false)
			->addConditionOn($this['overwriteCompany'], self::EQUAL, true)
				->addRule(self::MAX_LENGTH, 'Maximální délka firmy je %d znaků', 200);  // No min length to allow _removal_ of company name from a review by using an empty string
		$this->addText('jobTitle', 'Pozice:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka pozice je %d znaků', 200);
		$this->addTextArea('review', 'Ohlas:')
			->setRequired('Zadejte prosím ohlas')
			->addRule(self::MIN_LENGTH, 'Minimální délka ohlasu je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka ohlasu je %d znaků', 2000);
		$this->addText('href', 'Odkaz:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu je %d znaků', 200);
		$this->addCheckbox('hidden', 'Skrýt:');
		$this->addSubmit('submit', 'Přidat');
	}


	public function setReview(\Nette\Database\Row $review): self
	{
		$values = array(
			'application' => $review->applicationId,
			'overwriteName' => ($review->name !== null || $review->applicationId === null),
			'name' => $review->name,
			'overwriteCompany' => ($review->company !== null || $review->applicationId === null),
			'company' => $review->company,
			'jobTitle' => $review->jobTitle,
			'review' => $review->review,
			'href' => $review->href,
			'hidden' => $review->hidden,
		);
		$this->setDefaults($values);
		$this->getComponent('submit')->caption = 'Upravit';
		return $this;
	}

}
