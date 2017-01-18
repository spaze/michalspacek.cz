<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

/**
 * Blog post loader service.
 *
 * Fast loader, no extra work, no formatting, no circular references.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class PostLoader
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Database\Row */
	protected $post;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Check whether the post exists.
	 *
	 * @param string $post
	 * @return boolean
	 */
	public function exists(string $post): bool
	{
		return (bool)$this->fetch($post);
	}


	/**
	 * Fetch post.
	 *
	 * @param string $post
	 * @return \Nette\Database\Row|null
	 */
	public function fetch(string $post): ?\Nette\Database\Row
	{
		if ($this->post === null) {
			$this->post = $this->database->fetch(
				'SELECT
					bp.id_blog_post AS postId,
					bp.slug,
					bp.title,
					bp.lead,
					bp.text,
					bp.published,
					bp.originally,
					bp.og_image AS ogImage,
					tct.card AS twitterCard,
					bp.tags
				FROM blog_posts bp
				LEFT JOIN twitter_card_types tct
					ON tct.id_twitter_card_type = bp.key_twitter_card_type
				WHERE bp.slug = ?',
				$post
			) ?: null;
		}
		return $this->post;
	}

}
