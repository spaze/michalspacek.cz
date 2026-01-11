<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Application\Locale\Locales;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPostFactory;
use MichalSpacekCz\Articles\Blog\BlogPostPreview;
use MichalSpacekCz\Articles\Blog\BlogPostRecommendedLinks;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Articles\Blog\BlogPostTranslation;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\Twitter\Exceptions\TwitterCardNotFoundException;
use MichalSpacekCz\Twitter\TwitterCards;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Spaze\ContentSecurityPolicy\CspConfig;
use stdClass;

final readonly class BlogPostFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private FormValidators $validators,
		private Translator $translator,
		private BlogPosts $blogPosts,
		private BlogPostFactory $blogPostFactory,
		private Tags $tags,
		private TexyFormatter $texyFormatter,
		private CspConfig $contentSecurityPolicy,
		private TrainingControlsFactory $trainingControlsFactory,
		private BlogPostPreview $blogPostPreview,
		private TwitterCards $twitterCards,
		private BlogPostRecommendedLinks $recommendedLinks,
		private BlogPostTranslation $blogPostTranslation,
		private Locales $locales,
	) {
	}


	public function create(callable $onSuccessAdd, callable $onSuccessEdit, DefaultTemplate $template, callable $sendTemplate, ?BlogPost $post): UiForm
	{
		$form = $this->factory->create();
		$form->addInteger('translationGroup', 'Skupina překladů:')
			->setRequired(false)
			->setDefaultValue($post === null ? $this->blogPostTranslation->getNextTranslationId() : null);
		$form->addSelect('locale', 'Jazyk:', $this->locales->getAllLocales())
			->setRequired('Zadejte prosím jazyk')
			->setPrompt('- vyberte -');
		$form->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek')
			->addRule(Form::MinLength, 'Titulek musí mít alespoň %d znaky', 3);
		$slugInput = $form->addText('slug', 'Slug:')
			->addRule(Form::MinLength, 'Slug musí mít alespoň %d znaky', 3);
		$this->validators->addValidateSlugRules($slugInput);
		$this->addPublishedDate($form->addText('published', 'Vydáno:'))
			->setDefaultValue(date('Y-m-d') . ' HH:MM');
		$previewKeyInput = $form->addText('previewKey', 'Klíč pro náhled:')
			->setRequired(false)
			->setDefaultValue(Random::generate(9, '0-9a-zA-Z'))
			->addRule(Form::MinLength, 'Klíč pro náhled musí mít alespoň %d znaky', 3);
		$form->addTextArea('lead', 'Perex:')
			->addCondition(Form::Filled)
			->addRule(Form::MinLength, 'Perex musí mít alespoň %d znaky', 3);
		$form->addTextArea('text', 'Text:')
			->setRequired('Zadejte prosím text')
			->addRule(Form::MinLength, 'Text musí mít alespoň %d znaky', 3);
		$form->addTextArea('originally', 'Původně vydáno:')
			->addCondition(Form::Filled)
			->addRule(Form::MinLength, 'Původně vydáno musí mít alespoň %d znaky', 3);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule(Form::MaxLength, 'Maximální délka odkazu na obrázek je %d znaků', 200);

		$cards = ['' => 'Žádná karta'];
		foreach ($this->twitterCards->getAll() as $card) {
			$cards[$card->getCard()] = $card->getTitle();
		}
		$form->addSelect('twitterCard', 'Twitter card', $cards);

		$form->addText('tags', 'Tagy:')
			->setRequired(false);
		$form->addText('recommended', 'Doporučené:')
			->setRequired(false);
		$editSummaryInput = $form->addText('editSummary', 'Shrnutí editace:');
		$editSummaryInput->setRequired(false)
			->setDisabled(true)
			->addCondition(Form::Filled)
			->addRule(Form::MinLength, 'Shrnutí editace musí mít alespoň %d znaky', 3)
			->endCondition()
			->addRule(Form::MaxLength, 'Maximální délka shrnutí editace je %d znaků', 200);

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
		foreach ($this->blogPostFactory->getAllowedTags() as $name => $tags) {
			$allowed = [];
			foreach ($tags as $tag => $attributes) {
				$allowed[] = trim('<' . trim($tag . ' ' . implode(' ', $attributes)) . '>');
			}
			$items[$name] = $name . ': ' . implode(', ', $allowed);
		}
		$form->addMultiSelect('allowedTags', 'Povolené tagy:', $items);

		$form->addCheckbox('omitExports', 'Vynechat z RSS');

		$submitButton = $form->addSubmit('submit', 'Přidat');
		$caption = $this->translator->translate('messages.label.preview');
		$previewButton = $form->addSubmit('preview', $caption);
		$previewButton->setHtmlAttribute('data-loading-value', 'Moment…')
			->setHtmlAttribute('data-original-value', $caption);
		$previewButton->onClick[] = function () use ($form, $post, $template, $sendTemplate): void {
			$this->blogPostPreview->sendPreview(
				function () use ($form, $post): BlogPost {
					return $this->buildPost($form->getFormValues(), $post?->getId());
				},
				$template,
				$sendTemplate,
			);
		};

		$form->onValidate[] = function (UiForm $form) use ($previewButton, $post, $previewKeyInput): void {
			if ($form->isSubmitted() !== $previewButton) {
				$newPost = $this->buildPost($form->getFormValues(), $post?->getId());
				if ($newPost->needsPreviewKey() && $newPost->getPreviewKey() === null) {
					$previewKeyInput->addError(sprintf('Tento %s příspěvek vyžaduje klíč pro náhled', $newPost->getPublishTime() === null ? 'nepublikovaný' : 'budoucí'));
				}
			}
		};
		$form->onSuccess[] = function (UiForm $form) use ($onSuccessAdd, $onSuccessEdit, $post): void {
			$values = $form->getFormValues();
			$newPost = $this->buildPost($values, $post?->getId());
			try {
				if ($post !== null) {
					$editSummary = $values->editSummary ?? null;
					assert($editSummary === null || is_string($editSummary));
					$this->blogPosts->update($newPost, $editSummary, $post->getSlugTags());
					$onSuccessEdit($newPost);
				} else {
					$onSuccessAdd($this->blogPosts->add($newPost));
				}
			} catch (UniqueConstraintViolationException) {
				$slug = $form->getComponent('slug');
				assert($slug instanceof TextInput);
				$slug->addError($this->texyFormatter->translate('messages.blog.admin.duplicateslug'));
			}
		};
		if ($post !== null) {
			$this->setDefaults($post, $form, $editSummaryInput, $submitButton);
		}
		return $form;
	}


	/**
	 * @throws TwitterCardNotFoundException
	 * @throws JsonException
	 * @throws InvalidTimezoneException
	 * @throws InvalidLinkException
	 */
	private function buildPost(stdClass $values, ?int $postId): BlogPost
	{
		assert(is_int($values->translationGroup) || $values->translationGroup === null);
		assert(is_string($values->title));
		assert(is_string($values->slug));
		assert(is_int($values->locale));
		assert(is_string($values->lead));
		assert(is_string($values->text));
		assert(is_string($values->published));
		assert(is_string($values->previewKey));
		assert(is_string($values->originally));
		assert(is_string($values->ogImage));
		assert(is_string($values->tags));
		assert(is_string($values->recommended));
		assert(is_string($values->twitterCard));
		assert(is_array($values->cspSnippets) && array_is_list($values->cspSnippets));
		assert(is_array($values->allowedTags) && array_is_list($values->allowedTags));
		assert(is_bool($values->omitExports));
		/** @var list<string> $cspSnippets */
		$cspSnippets = $values->cspSnippets;
		/** @var list<string> $allowedTagsGroups */
		$allowedTagsGroups = $values->allowedTags;
		return $this->blogPostFactory->create(
			$postId,
			$values->slug === '' ? Strings::webalize($values->title) : $values->slug,
			$values->locale,
			$this->locales->getLocaleById($values->locale),
			$values->translationGroup,
			$values->title,
			$values->lead === '' ? null : $values->lead,
			$values->text,
			$values->published === '' ? null : new DateTime($values->published),
			$values->previewKey === '' ? null : $values->previewKey,
			$values->originally === '' ? null : $values->originally,
			$values->ogImage === '' ? null : $values->ogImage,
			$values->tags === '' ? [] : $this->tags->toArray($values->tags),
			$values->tags === '' ? [] : $this->tags->toSlugArray($values->tags),
			$values->recommended === '' ? [] : $this->recommendedLinks->getFromJson($values->recommended),
			$values->twitterCard === '' ? null : $this->twitterCards->getCard($values->twitterCard),
			$cspSnippets,
			$allowedTagsGroups,
			$values->omitExports,
		);
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


	private function setDefaults(BlogPost $post, UiForm $form, TextInput $editSummaryInput, SubmitButton $submitButton): void
	{
		$values = [
			'translationGroup' => $post->getTranslationGroupId(),
			'locale' => $post->getLocaleId(),
			'title' => $post->getTitleTexy(),
			'slug' => $post->getSlug(),
			'published' => $post->getPublishTime()?->format('Y-m-d H:i'),
			'lead' => $post->getSummaryTexy(),
			'text' => $post->getTextTexy(),
			'originally' => $post->getOriginallyTexy(),
			'ogImage' => $post->getOgImage(),
			'twitterCard' => $post->getTwitterCard()?->getCard(),
			'tags' => $post->getTags() !== [] ? $this->tags->toString($post->getTags()) : null,
			'recommended' => $post->getRecommended() === [] ? null : Json::encode($post->getRecommended()),
			'cspSnippets' => $post->getCspSnippets(),
			'allowedTags' => $post->getAllowedTagsGroups(),
			'omitExports' => $post->omitExports(),
		];
		if ($post->getPreviewKey() !== null) {
			$values['previewKey'] = $post->getPreviewKey();
		}
		$form->setDefaults($values);
		$editSummaryInput->setDisabled($post->needsPreviewKey());
		$submitButton->caption = 'Upravit';
	}

}
