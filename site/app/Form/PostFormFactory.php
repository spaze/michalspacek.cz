<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Application\Locales;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPostPreview;
use MichalSpacekCz\Articles\Blog\BlogPostRecommendedLinks;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Twitter\TwitterCards;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Spaze\ContentSecurityPolicy\CspConfig;
use stdClass;

class PostFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Translator $translator,
		private readonly BlogPosts $blogPosts,
		private readonly Tags $tags,
		private readonly TexyFormatter $texyFormatter,
		private readonly CspConfig $contentSecurityPolicy,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly BlogPostPreview $blogPostPreview,
		private readonly FormValues $formValues,
		private readonly TwitterCards $twitterCards,
		private readonly BlogPostRecommendedLinks $recommendedLinks,
		private readonly Locales $locales,
	) {
	}


	public function create(callable $onSuccessAdd, callable $onSuccessEdit, DefaultTemplate $template, callable $sendTemplate, ?BlogPost $post): Form
	{
		$form = $this->factory->create();
		$form->addInteger('translationGroup', 'Skupina překladů:')
			->setRequired(false);
		$form->addSelect('locale', 'Jazyk:', $this->locales->getAllLocales())
			->setRequired('Zadejte prosím jazyk')
			->setPrompt('- vyberte -');
		$form->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek')
			->addRule($form::MIN_LENGTH, 'Titulek musí mít alespoň %d znaky', 3);
		$form->addText('slug', 'Slug:')
			->setRequired('Zadejte prosím slug')
			->addRule($form::MIN_LENGTH, 'Slug musí mít alespoň %d znaky', 3);
		$this->addPublishedDate($form->addText('published', 'Vydáno:'))
			->setDefaultValue(date('Y-m-d') . ' HH:MM');
		$form->addText('previewKey', 'Klíč pro náhled:')
			->setRequired(false)
			->addRule($form::MIN_LENGTH, 'Klíč pro náhled musí mít alespoň %d znaky', 3);
		$form->addTextArea('lead', 'Perex:')
			->addCondition($form::FILLED)
			->addRule($form::MIN_LENGTH, 'Perex musí mít alespoň %d znaky', 3);
		$form->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule($form::MIN_LENGTH, 'Text musí mít alespoň %d znaky', 3);
		$form->addTextArea('originally', 'Původně vydáno:')
			->addCondition($form::FILLED)
			->addRule($form::MIN_LENGTH, 'Původně vydáno musí mít alespoň %d znaky', 3);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);

		$cards = ['' => 'Žádná karta'];
		foreach ($this->twitterCards->getAll() as $card) {
			$cards[$card->getCard()] = $card->getTitle();
		}
		$form->addSelect('twitterCard', 'Twitter card', $cards);

		$form->addText('tags', 'Tagy:')
			->setRequired(false);
		$form->addText('recommended', 'Doporučené:')
			->setRequired(false);
		$form->addText('editSummary', 'Shrnutí editace:')
			->setRequired(false)
			->setDisabled(true)
			->addCondition($form::FILLED)
			->addRule($form::MIN_LENGTH, 'Shrnutí editace musí mít alespoň %d znaky', 3)
			->endCondition()
			->addRule($form::MAX_LENGTH, 'Maximální délka shrnutí editace je %d znaků', 200);

		$label = Html::el()->addText(Html::el('span', ['title' => 'Content Security Policy'])->setText('CSP'))->addText(' snippety:');
		$items = [];
		foreach ($this->contentSecurityPolicy->getSnippets() as $name => $snippet) {
			$allowed = [];
			foreach ($snippet as $directive => $values) {
				$allowed[] = trim($directive . ' ' . implode(' ', $values));
			}
			$items[$name] = $name . ': ' . implode('; ', $allowed);
		}
		$form->addMultiSelect('cspSnippets', $label, $items);

		$items = [];
		foreach ($this->blogPosts->getAllowedTags() as $name => $tags) {
			$allowed = [];
			foreach ($tags as $tag => $attributes) {
				$allowed[] = trim('<' . trim($tag . ' ' . implode(' ', $attributes)) . '>');
			}
			$items[$name] = $name . ': ' . implode(', ', $allowed);
		}
		$form->addMultiSelect('allowedTags', 'Povolené tagy:', $items);

		$form->addCheckbox('omitExports', 'Vynechat z RSS');

		$form->addSubmit('submit', 'Přidat');
		$form->addSubmit('preview', $this->translator->translate('messages.label.preview'))
			->setHtmlAttribute('data-loading-value', 'Moment…')
			->onClick[] = function (SubmitButton $button) use ($post, $template, $sendTemplate): void {
				$newPost = $this->buildPost($this->formValues->getValues($button), $post?->postId);
				$this->blogPostPreview->sendPreview($newPost, $template, $sendTemplate);
			};

		$form->onValidate[] = function (Form $form) use ($post): void {
			$newPost = $this->buildPost($form->getValues(), $post?->postId);
			if ($newPost->needsPreviewKey() && $newPost->previewKey === null) {
				$input = $form->getComponent('previewKey');
				if (!$input instanceof TextInput) {
					throw new ShouldNotHappenException(sprintf("The 'previewKey' component should be '%s' but it's a %s", TextInput::class, get_debug_type($input)));
				}
				$input->addError(sprintf('Tento %s příspěvek vyžaduje klíč pro náhled', $newPost->published === null ? 'nepublikovaný' : 'budoucí'));
			}
		};
		$form->onSuccess[] = function (Form $form) use ($onSuccessAdd, $onSuccessEdit, $post): void {
			$values = $form->getValues();
			$newPost = $this->buildPost($values, $post?->postId);
			$this->blogPosts->enrich($newPost);
			try {
				if ($post) {
					$onSuccessEdit($newPost);
				} else {
					$onSuccessAdd($newPost);
				}
			} catch (UniqueConstraintViolationException) {
				$slug = $form->getComponent('slug');
				if (!$slug instanceof TextInput) {
					throw new ShouldNotHappenException(sprintf("The 'slug' component should be '%s' but it's a %s", TextInput::class, get_debug_type($slug)));
				}
				$slug->addError($this->texyFormatter->translate('messages.blog.admin.duplicateslug'));
			}
		};
		if ($post) {
			$this->setDefaults($post, $form);
		}
		return $form;
	}


	private function buildPost(stdClass $values, ?int $postId): BlogPost
	{
		$post = new BlogPost();
		$post->postId = $postId;
		$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
		$post->localeId = $values->locale;
		$post->locale = $this->locales->getLocaleById($values->locale);
		$post->slug = $values->slug;
		$post->titleTexy = $values->title;
		$post->leadTexy = (empty($values->lead) ? null : $values->lead);
		$post->textTexy = $values->text;
		$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
		$post->published = (empty($values->published) ? null : new DateTime($values->published));
		$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
		$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
		$post->tags = (empty($values->tags) ? [] : $this->tags->toArray($values->tags));
		$post->slugTags = (empty($values->tags) ? [] : $this->tags->toSlugArray($values->tags));
		$post->recommended = $values->recommended ? $this->recommendedLinks->getFromJson($values->recommended) : [];
		$post->twitterCard = (empty($values->twitterCard) ? null : $this->twitterCards->getCard($values->twitterCard));
		$post->editSummary = (empty($values->editSummary) ? null : $values->editSummary);
		$post->cspSnippets = (empty($values->cspSnippets) ? [] : $values->cspSnippets);
		$post->allowedTags = (empty($values->allowedTags) ? [] : $values->allowedTags);
		$post->omitExports = !empty($values->omitExports);
		return $post;
	}


	private function addPublishedDate(TextInput $field, bool $required = false): TextInput
	{
		return $this->trainingControlsFactory->addDate(
			$field,
			$required,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
	}


	private function setDefaults(BlogPost $post, Form $form): void
	{
		$values = [
			'translationGroup' => $post->translationGroupId,
			'locale' => $post->localeId,
			'title' => $post->titleTexy,
			'slug' => $post->slug,
			'published' => $post->published?->format('Y-m-d H:i'),
			'previewKey' => $post->previewKey,
			'lead' => $post->leadTexy,
			'text' => $post->textTexy,
			'originally' => $post->originallyTexy,
			'ogImage' => $post->ogImage,
			'twitterCard' => $post->twitterCard?->getCard(),
			'tags' => ($post->tags ? $this->tags->toString($post->tags) : null),
			'recommended' => (empty($post->recommended) ? null : Json::encode($post->recommended)),
			'cspSnippets' => $post->cspSnippets,
			'allowedTags' => $post->allowedTags,
			'omitExports' => $post->omitExports,
		];
		$form->setDefaults($values);
		$form->getComponent('editSummary')
			->setDisabled($post->needsPreviewKey());
		$form->getComponent('submit')->caption = 'Upravit';
	}

}
