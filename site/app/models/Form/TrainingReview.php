<?php
namespace MichalSpacekCz\Form;

/**
 * Training review form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingReview extends \Nette\Application\UI\Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		\Nette\Application\UI\Form::__construct($parent, $name);

		$this->addCheckbox('overwriteName', 'Přepsat jméno:');
		$this->addText('name', 'Jméno:');
		$this->addCheckbox('overwriteCompany', 'Přepsat firmu:');
		$this->addText('company', 'Firma:');
		$this->addTextArea('review', 'Ohlas:');
		$this->addText('href', 'Odkaz:');
		$this->addCheckbox('hidden', 'Skrýt:');
		$this->addSubmit('save', 'Uložit');
	}


	public function setReview(\Nette\Database\Row $review)
	{
		$values = array(
			'overwriteName' => ($review->name !== null),
			'name' => $review->name,
			'overwriteCompany' => ($review->company !== null),
			'company' => $review->company,
			'review' => $review->review,
			'href' => $review->href,
			'hidden' => $review->hidden,
		);
		$this->setDefaults($values);
		return $this;
	}

}
