<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

/**
 * Training note trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
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
