<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

trait TrainingSource
{

	/**
	 * Add source input.
	 *
	 * @param \Nette\Forms\Container $container
	 * @return \Nette\Forms\Controls\SelectBox
	 */
	protected function addSource(\Nette\Forms\Container $container): \Nette\Forms\Controls\SelectBox
	{
		$sources = array();
		foreach ($this->trainingApplications->getTrainingApplicationSources() as $source) {
			$sources[$source->alias] = $source->name;
		}

		return $container->addSelect('source', 'Zdroj:', $sources)
			->setRequired('Vyberte zdroj');
	}

}
