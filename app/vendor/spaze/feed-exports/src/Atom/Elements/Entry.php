<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Elements;

use DateTimeInterface;
use Spaze\Exports\Atom\Constructs\Text;

/**
 * Atom entry element.
 *
 * @author Michal Špaček
 */
class Entry
{

	private ?Text $content = null;

	private ?Text $summary = null;

	/** @var array<string, array<int, Link>> */
	private array $links = [];


	public function __construct(
		private string $id,
		private Text $title,
		private DateTimeInterface $updated,
		private ?DateTimeInterface $published = null,
	) {
	}


	public function setSummary(Text $summary): void
	{
		$this->summary = $summary;
	}


	public function getSummary(): ?Text
	{
		return $this->summary;
	}


	public function setContent(Text $content): void
	{
		$this->content = $content;
	}


	public function getContent(): ?Text
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


	public function getTitle(): Text
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
	 * @return array<string, array<int, Link>>
	 */
	public function getLinks(): array
	{
		return $this->links;
	}

}
