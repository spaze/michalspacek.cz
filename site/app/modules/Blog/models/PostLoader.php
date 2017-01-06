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
	public function fetch(string $post)
	{
		if ($this->post === null) {
			$this->post = $this->database->fetch('SELECT slug, title, text FROM blog_posts WHERE slug = ?', $post) ?: null;
		}
		return $this->post;
	}

}
