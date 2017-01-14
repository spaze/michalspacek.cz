<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Blog;

/**
 * Blog post form.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Post extends \MichalSpacekCz\Form\ProtectedForm
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);

		$this->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek')
			->addRule(self::MIN_LENGTH, 'Titulek musí mít alespoň %d znaky', 3);
		$this->addText('slug', 'Slug:')
			->setRequired('Zadejte prosím slug')
			->addRule(self::MIN_LENGTH, 'Slug musí mít alespoň %d znaky', 3);
		$this->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule(self::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);

		$this->addSubmit('submit', 'Přidat');
	}

}
