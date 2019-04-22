<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;

trait TrainingNote
{

	/**
	 * Add note input.
	 *
	 * @param Container $container
	 */
	protected function addNote(Container $container): void
	{
		$container->addText('note', 'Poznámka:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}

}
