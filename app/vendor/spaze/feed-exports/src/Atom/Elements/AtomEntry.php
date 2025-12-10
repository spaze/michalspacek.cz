<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Elements;

use DateTimeInterface;
use Spaze\Exports\Atom\Constructs\AtomText;

class AtomEntry
{

	private ?AtomText $content = null;

	private ?AtomText $summary = null;

	/** @var array<string, list<AtomLink>> */
	private array $links = [];


	public function __construct(
		private string $id,
		private AtomText $title,
		private DateTimeInterface $updated,
		private ?DateTimeInterface $published = null,
	) {
	}


	public function setSummary(AtomText $summary): void
	{
		$this->summary = $summary;
	}


	public function getSummary(): ?AtomText
	{
		return $this->summary;
	}


	public function setContent(AtomText $content): void
	{
		$this->content = $content;
	}


	public function getContent(): ?AtomText
	{
		return $this->content;
	}


	public function addLink(AtomLink $link): void
	{
		$this->links[$link->getRel()->value ?? ''][] = $link;
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getTitle(): AtomText
	{
		return $this->title;
	}


	public function getPublished(): ?DateTimeInterface
	{
		return $this->published;
	}


	public function getUpdated(): DateTimeInterface
	{
		return $this->updated;
	}


	/**
	 * @return array<string, list<AtomLink>>
	 */
	public function getLinks(): array
	{
		return $this->links;
	}

}
