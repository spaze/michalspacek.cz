<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

class Post extends \MichalSpacekCz\Form\ProtectedForm
{

	use \MichalSpacekCz\Form\Controls\Date;

	/** @var \MichalSpacekCz\Post */
	protected $blogPost;


	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, \MichalSpacekCz\Post $blogPost)
	{
		parent::__construct($parent, $name);
		$this->blogPost = $blogPost;

		$this->addText('translationGroup', 'Skupina překladů:')
			->setRequired(false)
			->setType('number');
		$this->addSelect('locale', 'Jazyk:', $this->blogPost->getAllLocales())
			->setRequired('Zadejte prosím jazyk')
			->setPrompt('- vyberte -');
		$this->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek')
			->addRule(self::MIN_LENGTH, 'Titulek musí mít alespoň %d znaky', 3);
		$this->addText('slug', 'Slug:')
			->setRequired('Zadejte prosím slug')
			->addRule(self::MIN_LENGTH, 'Slug musí mít alespoň %d znaky', 3);
		$this->addPublishedDate('published', 'Vydáno:', true)
			->setDefaultValue(date('Y-m-d') . ' HH:MM');
		$this->addText('previewKey', 'Klíč pro náhled:')
			->setRequired(false)
			->addRule(self::MIN_LENGTH, 'Klíč pro náhled musí mít alespoň %d znaky', 3);
		$this->addTextArea('lead', 'Perex:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Perex musí mít alespoň %d znaky', 3);
		$this->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule(self::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);
		$this->addTextArea('originally', 'Původně vydáno:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Původně vydáno musí mít alespoň %d znaky', 3);
		$this->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);

		$cards = ['' => 'Žádná karta'];
		foreach ($this->blogPost->getAllTwitterCards() as $card) {
			$cards[$card->card] = $card->title;
		}
		$this->addSelect('twitterCard', 'Twitter card', $cards);

		$this->addText('tags', 'Tagy:')
			->setRequired(false);
		$this->addText('recommended', 'Doporučené:')
			->setRequired(false);
		$this->addText('editSummary', 'Shrnutí editace:')
			->addRule(self::MAX_LENGTH, 'Maximální délka shrnutí editace je %d znaků', 200)
			->setRequired(false)
			->setDisabled(true);

		$this->addSubmit('submit', 'Přidat');
		$this->addButton('preview', 'Náhled')
			->setAttribute('data-alt', 'Moment…');;
	}


	/**
	 * Set post.
	 * @param \MichalSpacekCz\Post\Data $post
	 * @return static
	 */
	public function setPost(\MichalSpacekCz\Post\Data $post): self
	{
		$values = array(
			'translationGroup' => $post->translationGroupId,
			'locale' => $post->localeId,
			'title' => $post->titleTexy,
			'slug' => $post->slug,
			'published' => $post->published->format('Y-m-d H:i'),
			'previewKey' => $post->previewKey,
			'lead' => $post->leadTexy,
			'text' => $post->textTexy,
			'originally' => $post->originallyTexy,
			'ogImage' => $post->ogImage,
			'twitterCard' => $post->twitterCard,
			'tags' => ($post->tags ? implode(', ', $post->tags) : null),
			'recommended' => (empty($post->recommended) ? null : \Nette\Utils\Json::encode($post->recommended)),
		);
		$this->setDefaults($values);
		$this->getComponent('editSummary')
			->setDisabled($post->published > new \DateTime())
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Shrnutí editace musí mít alespoň %d znaky', 3);
		$this->getComponent('submit')->caption = 'Upravit';

		return $this;
	}


	protected function addPublishedDate($name, $label = null, $required = false): \Nette\Forms\Controls\TextInput
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})'
		);
	}

}
