<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Elements;

use Spaze\Exports\Atom\Constructs;

/**
 * Atom entry element.
 *
 * @author Michal Špaček
 */
class Entry
{

	/** @var string */
	protected $id;

	/** @var Constructs\Text */
	protected $title;

	/** @var Constructs\Text */
	protected $content;

	/** @var \DateTimeInterface */
	protected $published;

	/** @var \DateTimeInterface */
	protected $updated;

	/** @var Link[] */
	protected $links = [];


	/**
	 * Entry constructor.
	 *
	 * @param string $id
	 * @param Constructs\Text $title
	 * @param Constructs\Text $content
	 * @param \DateTimeInterface $updated
	 * @param \DateTimeInterface|null $published
	 */
	public function __construct(string $id, Constructs\Text $title, Constructs\Text $content, \DateTimeInterface $updated, ?\DateTimeInterface $published = null)
	{
		$this->id = $id;
		$this->title = $title;
		$this->content = $content;
		$this->updated = $updated;
		$this->published = $published;
	}


	/**
	 * Add link.
	 *
	 * @param Link $link
	 */
	public function addLink(Link $link)
	{
		$this->links[$link->getRel()][] = $link;
	}


	/**
	 * Get entry id.
	 *
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}


	/**
	 * Get entry title.
	 *
	 * @return Constructs\Text
	 */
	public function getTitle(): Constructs\Text
	{
		return $this->title;
	}


	/**
	 * Get entry content.
	 *
	 * @return Constructs\Text
	 */
	public function getContent(): Constructs\Text
	{
		return $this->content;
	}


	/**
	 * Get published date.
	 *
	 * @return \DateTimeInterface
	 */
	public function getPublished(): \DateTimeInterface
	{
		return $this->published;
	}


	/**
	 * Get updated date.
	 *
	 * @return \DateTimeInterface
	 */
	public function getUpdated(): \DateTimeInterface
	{
		return $this->updated;
	}


	/**
	 * Get updated date.
	 *
	 * @return Link[]
	 */
	public function getLinks(): array
	{
		return $this->links;
	}

}
