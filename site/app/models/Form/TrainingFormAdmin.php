<?php
namespace MichalSpacekCz\Form;

/**
 * Abstract admin training form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class TrainingFormAdmin extends TrainingForm
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		\MichalSpacekCz\Training\Applications $trainingApplications,
		\Nette\Localization\ITranslator $translator
	)
	{
		parent::__construct($parent, $name, $translator);
		$this->trainingApplications = $trainingApplications;
	}


	/**
	 * Add source input.
	 *
	 * @param \Nette\Forms\Container $container
	 * @return \Nette\Forms\Controls\SelectBox
	 */
	protected function addSource(\Nette\Forms\Container $container)
	{
		$sources = array();
		foreach ($this->trainingApplications->getTrainingApplicationSources() as $source) {
			$sources[$source->alias] = $source->name;
		}

		return $container->addSelect('source', 'Zdroj:', $sources)
			->setRequired('Vyberte zdroj');
	}

}
