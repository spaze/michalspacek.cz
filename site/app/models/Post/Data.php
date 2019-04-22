<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use DateTimeInterface;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use stdClass;

class Data
{

	/** @var integer */
	public $postId;

	/** @var string */
	public $slug;

	/** @var integer */
	public $localeId;

	/** @var integer */
	public $translationGroupId;

	/** @var string */
	public $locale;

	/** @var Html */
	public $title;

	/** @var string */
	public $titleTexy;

	/** @var Html */
	public $lead;

	/** @var string */
	public $leadTexy;

	/** @var Html */
	public $text;

	/** @var string */
	public $textTexy;

	/** @var DateTimeInterface */
	public $published;

	/** @var string */
	public $previewKey;

	/** @var Html */
	public $originally;

	/** @var string */
	public $originallyTexy;

	/** @var string */
	public $ogImage;

	/** @var string[] */
	public $tags = [];

	/** @var string[] */
	public $slugTags = [];

	/** @var string[] */
	public $previousSlugTags = [];

	/** @var stdClass[] */
	public $recommended;

	/** @var string */
	public $twitterCard;

	/** @var string */
	public $href;

	/** @var string|null */
	public $editSummary;

	/** @var Edit[] */
	public $edits;


	public function needsPreviewKey(): bool
	{
		return $this->published > new DateTime();
	}

}
