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

	/** @var \DateTimeInterface|null */
	protected $published;

	/** @var \DateTimeInterface */
	protected $updated;

	/** @var Constructs\Text */
	protected $summary;

	/** @var array<string, array<integer, Link>> */
	protected $links = [];


	public function __construct(string $id, Constructs\Text $title, \DateTimeInterface $updated, ?\DateTimeInterface $published = null)
	{
		$this->id = $id;
		$this->title = $title;
		$this->updated = $updated;
		$this->published = $published;
	}


	public function setSummary(Constructs\Text $summary): void
	{
		$this->summary = $summary;
	}


	public function getSummary(): ?Constructs\Text
	{
		return $this->summary;
	}


	public function setContent(Constructs\Text $content): void
	{
		$this->content = $content;
	}


	public function getContent(): ?Constructs\Text
	{
		return $this->content;
	}


	public function addLink(Link $link): void
	{
		$this->links[$link->getRel()][] = $link;
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getTitle(): Constructs\Text
	{
		return $this->title;
	}


	public function getPublished(): ?\DateTimeInterface
	{
		return $this->published;
	}


	public function getUpdated(): \DateTimeInterface
	{
		return $this->updated;
	}


	/**
	 * @return array<string, array<integer, Link>>
	 */
	public function getLinks(): array
	{
		return $this->links;
	}

}
