<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

/**
 * Blog post service.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Post
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var PostLoader */
	protected $loader;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param \Nette\Database\Context $context
	 * @param PostLoader $loader
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\Nette\Database\Context $context, PostLoader $loader, \MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->loader = $loader;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get post.
	 *
	 * @param string $post
	 * @return \Nette\Database\Row|null
	 */
	public function get(string $post): ?\Nette\Database\Row
	{
		$result = $this->loader->fetch($post);
		if ($result) {
			$this->format($result);
		}
		return $result;
	}


	/**
	 * Get post by id.
	 *
	 * @return \Nette\Database\Row|null
	 */
	public function getById($id): ?\Nette\Database\Row
	{
		$result = $this->database->fetch(
			'SELECT
				bp.id_blog_post AS postId,
				bp.slug,
				bp.title,
				bp.title AS titleTexy,
				bp.text,
				bp.text AS textTexy,
				bp.published,
				bp.originally,
				bp.originally AS originallyTexy,
				bp.og_image AS ogImage,
				tct.card AS twitterCard
			FROM blog_posts bp
			LEFT JOIN twitter_card_types tct
				ON tct.id_twitter_card_type = bp.key_twitter_card_type
			WHERE bp.id_blog_post = ?',
			$id
		) ?: null;
		if ($result) {
			$this->format($result);
		}
		return $result;
	}


	/**
	 * Get all posts.
	 *
	 * @return \Nette\Database\Row[]
	 */
	public function getAll(): array
	{
		$posts = $this->database->fetchAll(
			'SELECT
				id_blog_post AS postId,
				slug,
				title,
				text,
				published,
				originally
			FROM
				blog_posts
			ORDER BY
				published, slug'
		);
		foreach ($posts as $post) {
			$this->format($post);
		}
		return $posts;
	}


	private function format(\Nette\Database\Row $row)
	{
		foreach(['title'] as $item) {
			$row->$item = $this->texyFormatter->format($row->$item);
		}
		foreach(['text', 'originally'] as $item) {
			$row->$item = $this->texyFormatter->formatBlock($row->$item);
		}
	}


	/**
	 * Add a post.
	 *
	 * @param string $title
	 * @param string $slug
	 * @param string $text
	 * @param string $published
	 * @param string $originally
	 * @param string $twitterCard
	 * @param string $ogImage
	 */
	public function add(string $title, string $slug, string $text, string $published, string $originally, string $twitterCard, string $ogImage): void
	{
		$this->database->query(
			'INSERT INTO blog_posts',
			array(
				'title' => $title,
				'slug' => $slug,
				'text' => $text,
				'published' => new \DateTime($published),
				'originally' => (empty($originally) ? null : $originally),
				'key_twitter_card_type' => (empty($twitterCard) ? null : $this->getTwitterCardId($twitterCard)),
				'og_image' => (empty($ogImage) ? null : $ogImage),
			)
		);
	}


	/**
	 * Update a post.
	 *
	 * @param integer $id
	 * @param string $title
	 * @param string $slug
	 * @param string $text
	 * @param string $published
	 * @param string $originally
	 * @param string $twitterCard
	 * @param string $ogImage
	 */
	public function update(int $id, string $title, string $slug, string $text, string $published, string $originally, string $twitterCard, string $ogImage): void
	{
		$this->database->query(
			'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
			array(
				'title' => $title,
				'slug' => $slug,
				'text' => $text,
				'published' => new \DateTime($published),
				'originally' => (empty($originally) ? null : $originally),
				'key_twitter_card_type' => (empty($twitterCard) ? null : $this->getTwitterCardId($twitterCard)),
				'og_image' => (empty($ogImage) ? null : $ogImage),
			),
			$id
		);
	}


	/**
	 * Get all Twitter card types.
	 *
	 * @return \Nette\Database\Row[]
	 */
	public function getAllTwitterCards(): array
	{
		return $this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
	}


	/**
	 * @param string $card
	 * @return integer
	 */
	private function getTwitterCardId(string $card): int
	{
		return $this->database->fetchField('SELECT id_twitter_card_type FROM twitter_card_types WHERE card = ?', $card);
	}

}
