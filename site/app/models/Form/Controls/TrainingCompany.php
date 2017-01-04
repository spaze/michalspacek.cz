<?php
namespace MichalSpacekCz\Form\Controls;

/**
 * Training company trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait TrainingCompany
{

	/**
	 * Add company inputs.
	 *
	 * @param \Nette\Forms\Container $container
	 */
	protected function addCompany(\Nette\Forms\Container $container)
	{
		$container->addText('companyId', 'IČO:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Minimální délka IČO je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka IČO je %d znaků', 200);
		$container->addText('companyTaxId', 'DIČ:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200);
		$container->addText('company', 'Obchodní jméno:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 200);
		$container->addText('street', 'Ulice a číslo:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 200);
		$container->addText('city', 'Město:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
			->addRule(self::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
		$container->addText('zip', 'PSČ:')
			->setRequired(false)
			->addRule(self::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
			->addRule(self::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
	}

}
