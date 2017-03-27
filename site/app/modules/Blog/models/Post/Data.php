<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog\Post;

/**
 * Blog post data.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Data
{
	/** @var integer */
	public $postId;

	/** @var string */
	public $slug;

	/** @var integer */
	public $localeId;

	/** @var string */
	public $locale;

	/** @var \Nette\Utils\Html */
	public $title;

	/** @var string */
	public $titleTexy;

	/** @var \Nette\Utils\Html */
	public $lead;

	/** @var string */
	public $leadTexy;

	/** @var \Nette\Utils\Html */
	public $text;

	/** @var string */
	public $textTexy;

	/** @var \DateTimeInterface */
	public $published;

	/** @var string */
	public $previewKey;

	/** @var \Nette\Utils\Html */
	public $originally;

	/** @var string */
	public $originallyTexy;

	/** @var string */
	public $ogImage;

	/** @var string[]|null */
	public $tags;

	/** @var \stdClass[] */
	public $recommended;

	/** @var string */
	public $twitterCard;

	/** @var string */
	public $href;


	/**
	 * Returns true when the post needs preview key to display.
	 *
	 * @return bool
	 */
	public function needsPreviewKey(): bool
	{
		return $this->published > new \Nette\Utils\DateTime();
	}

}
