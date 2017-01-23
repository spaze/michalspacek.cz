<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

use Nette\Utils\Json;

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

	/** @var Post\Loader */
	protected $loader;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param \Nette\Database\Context $context
	 * @param Post\Loader $loader
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\Nette\Database\Context $context, Post\Loader $loader, \MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->loader = $loader;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get post.
	 *
	 * @param string $post
	 * @return \MichalSpacekCz\Blog\Post\Data|null
	 */
	public function get(string $post): ?\MichalSpacekCz\Blog\Post\Data
	{
		$result = $this->loader->fetch($post);
		if ($result) {
			$result = $this->format($this->build($result));
		}
		return $result;
	}


	/**
	 * Get post by id.
	 *
	 * @return MichalSpacekCz\Blog\Post\Data|null
	 */
	public function getById($id): ?\MichalSpacekCz\Blog\Post\Data
	{
		$result = $this->database->fetch(
			'SELECT
				bp.id_blog_post AS postId,
				bp.slug,
				bp.title,
				bp.lead,
				bp.text,
				bp.published,
				bp.originally,
				bp.og_image AS ogImage,
				bp.tags,
				bp.recommended,
				tct.card AS twitterCard
			FROM blog_posts bp
			LEFT JOIN twitter_card_types tct
				ON tct.id_twitter_card_type = bp.key_twitter_card_type
			WHERE bp.id_blog_post = ?',
			$id
		) ?: null;
		if ($result) {
			$result = $this->format($this->build($result));
		}
		return $result;
	}


	/**
	 * Get all posts.
	 *
	 * @return \MichalSpacekCz\Blog\Post\Data[]
	 */
	public function getAll(): array
	{
		$posts = [];
		$sql = 'SELECT
				id_blog_post AS postId,
				slug,
				title,
				lead,
				text,
				published,
				originally,
				tags
			FROM
				blog_posts
			ORDER BY
				published, slug';
		foreach ($this->database->fetchAll($sql) as $post) {
			$posts[] = $this->format($this->build($post));
		}
		return $posts;
	}


	/**
	 * Build post data object from database row object.
	 *
	 * @param \Nette\Database\Row $row
	 * @return \MichalSpacekCz\Blog\Post\Data
	 */
	private function build(\Nette\Database\Row $row): \MichalSpacekCz\Blog\Post\Data
	{
		$post = new \MichalSpacekCz\Blog\Post\Data();
		$post->postId = $row->postId;
		$post->slug = $row->slug;
		$post->title = $row->title;
		$post->lead = $row->lead;
		$post->text = $row->text;
		$post->published = $row->published;
		$post->originally = $row->originally;
		$post->ogImage = (isset($row->ogImage) ? $row->ogImage : null);  // Can't use ??, throws Nette\MemberAccessException
		$post->tags = (isset($row->tags) ? Json::decode($row->tags) : null);  // Can't use ??, throws Nette\MemberAccessException
		$post->recommended = (isset($row->recommended) ? $row->recommended : null);  // Can't use ??, throws Nette\MemberAccessException
		$post->twitterCard = (isset($row->twitterCard) ? $row->twitterCard : null);  // Can't use ??, throws Nette\MemberAccessException
		return $post;
	}


	/**
	 * Format post data.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 * @return \MichalSpacekCz\Blog\Post\Data
	 */
	public function format(\MichalSpacekCz\Blog\Post\Data $post): \MichalSpacekCz\Blog\Post\Data
	{
		$post->recommended = (empty($post->recommended) ? null : Json::decode($post->recommended));
		foreach(['title'] as $item) {
			$post->{$item . 'Texy'} = $post->$item;
			$post->$item = $this->texyFormatter->format($post->$item);
		}
		foreach(['lead', 'text', 'originally'] as $item) {
			$post->{$item . 'Texy'} = $post->$item;
			$post->$item = $this->texyFormatter->formatBlock($post->$item);
		}
		return $post;
	}


	/**
	 * Add a post.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 */
	public function add(\MichalSpacekCz\Blog\Post\Data $post): void
	{
		$this->database->query(
			'INSERT INTO blog_posts',
			array(
				'title' => $post->title,
				'slug' => $post->slug,
				'lead' => $post->lead,
				'text' => $post->text,
				'published' => $post->published,
				'originally' => $post->originally,
				'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
				'og_image' => $post->ogImage,
				'tags' => Json::encode($post->tags),
				'recommended' => $post->recommended,
			)
		);
	}


	/**
	 * Update a post.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 */
	public function update(\MichalSpacekCz\Blog\Post\Data $post): void
	{
		$this->database->query(
			'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
			array(
				'title' => $post->title,
				'slug' => $post->slug,
				'lead' => $post->lead,
				'text' => $post->text,
				'published' => $post->published,
				'originally' => $post->originally,
				'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
				'og_image' => $post->ogImage,
				'tags' => Json::encode($post->tags),
				'recommended' => $post->recommended,
			),
			$post->postId
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
