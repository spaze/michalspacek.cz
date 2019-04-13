<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * Strings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Strings
{

	/** @var \Nette\Localization\ITranslator */
	private $translator;


	/**
	 * Strings constructor.
	 * @param \Contributte\Translation\Translator|\Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}


	/**
	 * Get initial letter because sometimes it might be two letters.
	 *
	 * @param string $string
	 * @return string
	 */
	public function getInitialLetterUppercase(string $string): string
	{
		$length = ($this->translator->getDefaultLocale() === 'cs_CZ' && mb_strtolower(mb_substr($string, 0, 2)) === 'ch' ? 2 : 1);
		$initial = mb_substr($string, 0, $length);
		return mb_strtoupper(mb_substr($initial, 0, 1)) . mb_strtolower(mb_substr($initial, 1));
	}

}
