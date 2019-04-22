<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;
use Nette\Forms\Controls\SelectBox;

trait TrainingSource
{

	/**
	 * Add source input.
	 *
	 * @param Container $container
	 * @return SelectBox
	 */
	protected function addSource(Container $container): SelectBox
	{
		$sources = array();
		foreach ($this->trainingApplications->getTrainingApplicationSources() as $source) {
			$sources[$source->alias] = $source->name;
		}

		return $container->addSelect('source', 'Zdroj:', $sources)
			->setRequired('Vyberte zdroj');
	}

}
