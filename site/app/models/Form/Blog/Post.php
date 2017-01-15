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

	use \MichalSpacekCz\Form\Controls\Date;


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
		$this->addPublishedDate('published', 'Vydáno:', true)
			->setDefaultValue(date('Y-m-d') . ' HH:MM');
		$this->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule(self::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);
		$this->addTextArea('originally', 'Původně vydáno:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);

		$this->addSubmit('submit', 'Přidat');
	}


	/**
	 * Set post.
	 * @param \Nette\Database\Row $post [description]
	 */
	public function setPost(\Nette\Database\Row $post)
	{
		$values = array(
			'title' => $post->titleTexy,
			'slug' => $post->slug,
			'published' => $post->published->format('Y-m-d H:i'),
			'text' => $post->textTexy,
			'originally' => $post->originallyTexy,
		);
		$this->setDefaults($values);
		$this->getComponent('submit')->caption = 'Upravit';

		return $this;
	}


	/**
	 * Adds published date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addPublishedDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			$cols,
			$maxLength,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})'
		);
	}

}
