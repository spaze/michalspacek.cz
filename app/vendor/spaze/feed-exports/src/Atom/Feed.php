<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom;

use DateTimeInterface;
use Spaze\Exports\Atom\Constructs\Person;
use Spaze\Exports\Atom\Elements\Entry;
use XMLWriter;

/**
 * Atom feed.
 *
 * @author Michal Špaček
 */
class Feed
{

	private ?string $xml = null;

	private XMLWriter $writer;

	/** @var array<string, array<int, Elements\Link>> */
	private array $links = [];

	private Person $author;

	/** @var Entry[] */
	private array $entries = [];


	public function __construct(
		private string $id,
		private string $title,
		private ?DateTimeInterface $updated = null,
	) {
	}


	/**
	 * Set link with rel="self".
	 */
	public function setLinkSelf(string $href): void
	{
		$this->addLink(new Elements\Link($href, Elements\Link::REL_SELF));
	}


	public function setAuthor(Constructs\Person $author): void
	{
		$this->author = $author;
	}


	public function setUpdated(DateTimeInterface $updated): void
	{
		$this->updated = $updated;
	}


	public function getUpdated(): ?DateTimeInterface
	{
		return $this->updated;
	}


	public function addLink(Elements\Link $link): void
	{
		$this->links[$link->getRel()][] = $link;
	}


	public function addEntry(Elements\Entry $entry): void
	{
		$this->entries[] = $entry;
	}


	private function addElementAuthor(): void
	{
		$this->writer->startElement('author');
		$this->writer->writeElement('name', $this->author->getName());
		if ($this->author->getEmail()) {
			$this->writer->writeElement('email', $this->author->getEmail());
		}
		if ($this->author->getUri()) {
			$this->writer->writeElement('uri', $this->author->getUri());
		}
		$this->writer->endElement();
	}


	private function addElementLink(Elements\Link $link): void
	{
		$this->writer->startElement('link');
		$this->writer->writeAttribute('href', $link->getHref());
		if ($link->getRel() !== null) {
			$this->writer->writeAttribute('rel', $link->getRel());
		}
		if ($link->getType() !== null) {
			$this->writer->writeAttribute('type', $link->getType());
		}
		if ($link->getHreflang() !== null) {
			$this->writer->writeAttribute('hreflang', $link->getHreflang());
		}
		if ($link->getTitle() !== null) {
			$this->writer->writeAttribute('title', $link->getTitle());
		}
		if ($link->getLength() !== null) {
			$this->writer->writeAttribute('length', (string)$link->getLength());
		}
		$this->writer->endElement();
	}


	private function addConstructText(string $element, Constructs\Text $text): void
	{
		$this->writer->startElement($element);
		if ($text->getType() !== null) {
			$this->writer->writeAttribute('type', $text->getType());
		}
		$this->writer->text($text->getText());
		$this->writer->endElement();
	}


	private function addElementEntry(Elements\Entry $entry): void
	{
		$this->writer->startElement('entry');
		$this->writer->writeElement('id', $entry->getId());
		if ($entry->getPublished() !== null) {
			$this->writer->writeElement('published', $entry->getPublished()->format(DateTimeInterface::ATOM));
		}
		$this->writer->writeElement('updated', $entry->getUpdated()->format(DateTimeInterface::ATOM));
		if ($entry->getSummary() !== null) {
			$this->addConstructText('summary', $entry->getSummary());
		}
		$this->addConstructText('title', $entry->getTitle());
		foreach ($entry->getLinks() as $links) {
			foreach ($links as $link) {
				$this->addElementLink($link);
			}
		}
		if ($entry->getContent() !== null) {
			$this->addConstructText('content', $entry->getContent());
		}
		$this->writer->endElement();
	}


	private function getXml(): string
	{
		$this->writer = new XMLWriter();
		$this->writer->openMemory();
		$this->writer->startDocument('1.0', 'UTF-8');
		$this->writer->startElementNs(null, 'feed', 'http://www.w3.org/2005/Atom');
		$this->writer->writeElement('id', $this->id);
		$this->writer->writeElement('title', $this->title);
		if ($this->updated !== null) {
			$this->writer->writeElement('updated', $this->updated->format(DateTimeInterface::ATOM));
		}
		$this->addElementAuthor();
		foreach ($this->links as $links) {
			foreach ($links as $link) {
				$this->addElementLink($link);
			}
		}
		foreach ($this->entries as $entry) {
			$this->addElementEntry($entry);
		}
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}


	public function __toString(): string
	{
		if (!$this->xml) {
			$this->xml = $this->getXml();
		}
		return $this->xml;
	}

}
