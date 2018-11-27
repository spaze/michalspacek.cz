<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

/**
 * Training attendee trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait TrainingAttendee
{

	/**
	 * Add attendee inputs.
	 *
	 * @param \Nette\Forms\Container $container
	 */
	protected function addAttendee(\Nette\Forms\Container $container): void
	{
		$container->addText('name', 'Jméno a příjmení:')
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(self::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 200);
		$container->addText('email', 'E-mail:')
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(self::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(self::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200);
	}

}
