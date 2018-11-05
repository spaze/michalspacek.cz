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

	/** @var Constructs\Text */
	protected $summary;

	/** @var Link[] */
	protected $links = [];


	/**
	 * Entry constructor.
	 *
	 * @param string $id
	 * @param Constructs\Text $title
	 * @param \DateTimeInterface $updated
	 * @param \DateTimeInterface|null $published
	 */
	public function __construct(string $id, Constructs\Text $title, \DateTimeInterface $updated, ?\DateTimeInterface $published = null)
	{
		$this->id = $id;
		$this->title = $title;
		$this->updated = $updated;
		$this->published = $published;
	}


	/**
	 * Set summary.
	 *
	 * @param Constructs\Text $summary
	 */
	public function setSummary(Constructs\Text $summary)
	{
		$this->summary = $summary;
	}


	/**
	 * Get summary.
	 *
	 * @return Constructs\Text|null
	 */
	public function getSummary(): ?Constructs\Text
	{
		return $this->summary;
	}


	/**
	 * Set content.
	 *
	 * @param Constructs\Text $content
	 */
	public function setContent(Constructs\Text $content)
	{
		$this->content = $content;
	}


	/**
	 * Get content.
	 *
	 * @return Constructs\Text|null
	 */
	public function getContent(): ?Constructs\Text
	{
		return $this->content;
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
