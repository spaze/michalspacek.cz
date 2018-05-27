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

	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, ?array $applications = null)
	{
		parent::__construct($parent, $name);

		if ($applications !== null) {
			$this->addSelect('application', 'Šablona:', $applications)
				->setRequired(false)
				->setPrompt('- vyberte účastníka -');
		}
		$this->addText('name', 'Jméno:')
			->setRequired('Zadejte prosím jméno')
			->addRule(self::MIN_LENGTH, 'Minimální délka jména je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka jména je %d znaků', 200);
		$this->addText('company', 'Firma:')
			->setRequired(false)
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
			'name' => $review->name,
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
