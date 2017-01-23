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

	/** @var string */
	public $title;

	/** @var string */
	public $titleTexy;

	/** @var string */
	public $lead;

	/** @var string */
	public $leadTexy;

	/** @var string */
	public $text;

	/** @var string */
	public $textTexy;

	/** @var \DateTimeInterface */
	public $published;

	/** @var string */
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
}
