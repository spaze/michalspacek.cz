<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use MichalSpacekCz\Form\Controls\Date;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Post;
use MichalSpacekCz\Post\Data;
use MichalSpacekCz\Tags;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Json;
use stdClass;

class PostFormFactory
{

	use Date;

	private FormFactory $factory;

	private Post $blogPost;

	private Tags $tags;

	private Texy $texyFormatter;


	public function __construct(FormFactory $factory, Post $blogPost, Tags $tags, Texy $texyFormatter)
	{
		$this->factory = $factory;
		$this->blogPost = $blogPost;
		$this->tags = $tags;
		$this->texyFormatter = $texyFormatter;
	}


	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addText('translationGroup', 'Skupina překladů:')
			->setRequired(false)
			->setHtmlType('number');
		$form->addSelect('locale', 'Jazyk:', $this->blogPost->getAllLocales())
			->setRequired('Zadejte prosím jazyk')
			->setPrompt('- vyberte -');
		$form->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek')
			->addRule(Form::MIN_LENGTH, 'Titulek musí mít alespoň %d znaky', 3);
		$form->addText('slug', 'Slug:')
			->setRequired('Zadejte prosím slug')
			->addRule(Form::MIN_LENGTH, 'Slug musí mít alespoň %d znaky', 3);
		$this->addPublishedDate($form, 'published', 'Vydáno:', true)
			->setDefaultValue(date('Y-m-d') . ' HH:MM');
		$form->addText('previewKey', 'Klíč pro náhled:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Klíč pro náhled musí mít alespoň %d znaky', 3);
		$form->addTextArea('lead', 'Perex:')
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Perex musí mít alespoň %d znaky', 3);
		$form->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule(Form::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);
		$form->addTextArea('originally', 'Původně vydáno:')
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Původně vydáno musí mít alespoň %d znaky', 3);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);

		$cards = ['' => 'Žádná karta'];
		foreach ($this->blogPost->getAllTwitterCards() as $card) {
			$cards[$card->card] = $card->title;
		}
		$form->addSelect('twitterCard', 'Twitter card', $cards);

		$form->addText('tags', 'Tagy:')
			->setRequired(false);
		$form->addText('recommended', 'Doporučené:')
			->setRequired(false);
		$form->addText('editSummary', 'Shrnutí editace:')
			->setRequired(false)
			->setDisabled(true)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Shrnutí editace musí mít alespoň %d znaky', 3)
			->endCondition()
			->addRule(Form::MAX_LENGTH, 'Maximální délka shrnutí editace je %d znaků', 200);

		$form->addSubmit('submit', 'Přidat');
		$form->addButton('preview', 'Náhled')
			->setHtmlAttribute('data-alt', 'Moment…');;

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
			$post = new Data();
			$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
			$post->localeId = $values->locale;
			$post->locale = $this->blogPost->getLocaleById($values->locale);
			$post->slug = $values->slug;
			$post->titleTexy = $values->title;
			$post->leadTexy = (empty($values->lead) ? null : $values->lead);
			$post->textTexy = $values->text;
			$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
			$post->published = new DateTime($values->published);
			$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
			$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
			$post->tags = (empty($values->tags) ? [] : $this->tags->toArray($values->tags));
			$post->slugTags = (empty($values->tags) ? [] : $this->tags->toSlugArray($values->tags));
			$post->recommended = (empty($values->recommended) ? null : Json::decode($values->recommended));
			$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
			$post->editSummary = (empty($values->editSummary) ? null : $values->editSummary);
			$this->blogPost->enrich($post);
			try {
				$onSuccess($post);
			} catch (UniqueConstraintViolationException $e) {
				/** @var TextInput $slug */
				$slug = $form->getComponent('slug');
				$slug->addError($this->texyFormatter->translate('messages.blog.admin.duplicateslug'));
			}
		};
		return $form;
	}


	private function addPublishedDate(Form $form, string $name, string $label, bool $required = false): TextInput
	{
		return $this->addDate(
			$form,
			$name,
			$label,
			$required,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})'
		);
	}

}
