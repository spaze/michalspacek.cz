<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

trait TrainingNote
{

	/**
	 * Add note input.
	 *
	 * @param \Nette\Forms\Container $container
	 */
	protected function addNote(\Nette\Forms\Container $container): void
	{
		$container->addText('note', 'Poznámka:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}

}
