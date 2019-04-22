<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;

trait TrainingCountry
{

	/**
	 * Add country input.
	 *
	 * @param Container $container
	 */
	protected function addCountry(Container $container): void
	{
		$container->addSelect('country', 'Země:', ['cz' => 'Česká republika', 'sk' => 'Slovensko'])
			->setRequired('Vyberte prosím zemi');
	}

}
