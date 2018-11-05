<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom;

/**
 * Atom feed.
 *
 * @author Michal Špaček
 */
class Feed
{

	/** @var string */
	protected $xml;

	/** @var \XMLWriter */
	protected $writer;

	/** @var string */
	protected $id;

	/** @var string */
	protected $title;

	/** @var \DateTimeInterface */
	protected $updated;

	/** @var Elements\Link[] */
	protected $links = [];

	/** @var Constructs\Person */
	protected $author;

	/** @var Elements\Entry[] */
	protected $entries = [];


	/**
	 * Feed constructor.
	 * @param string $id
	 * @param string $title
	 * @param \DateTimeInterface|null $updated
	 */
	public function __construct(string $id, string $title, ?\DateTimeInterface $updated = null)
	{
		$this->id = $id;
		$this->title = $title;
		$this->updated = $updated;
	}


	/**
	 * Set link with rel="self".
	 *
	 * @param string $href
	 */
	public function setLinkSelf(string $href): void
	{
		$this->addLink(new Elements\Link($href, Elements\Link::REL_SELF));
	}


	/**
	 * Set author.
	 *
	 * @param Constructs\Person $author
	 */
	public function setAuthor(Constructs\Person $author): void
	{
		$this->author = $author;
	}


	/**
	 * Set updated time.
	 *
	 * @param \DateTimeInterface $updated
	 */
	public function setUpdated(\DateTimeInterface $updated): void
	{
		$this->updated = $updated;
	}


	/**
	 * Get updated time.
	 *
	 * @return \DateTimeInterface|null $updated
	 */
	public function getUpdated(): ?\DateTimeInterface
	{
		return $this->updated;
	}


	/**
	 * Add a link.
	 *
	 * @param Elements\Link $link
	 */
	public function addLink(Elements\Link $link)
	{
		$this->links[$link->getRel()][] = $link;
	}


	/**
	 * Add an entry.
	 *
	 * @param Elements\Entry $entry
	 */
	public function addEntry(Elements\Entry $entry)
	{
		$this->entries[] = $entry;
	}


	/**
	 * Add author element.
	 */
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


	/**
	 * Add link element.
	 * @param Elements\Link $link
	 */
	private function addElementLink(Elements\Link $link): void
	{
		$this->writer->startElement('link');
		$this->writer->writeAttribute('href', $link->getHref());
		if ($link->getRel()) {
			$this->writer->writeAttribute('rel', $link->getRel());
		}
		if ($link->getType()) {
			$this->writer->writeAttribute('type', $link->getType());
		}
		if ($link->getHreflang()) {
			$this->writer->writeAttribute('hreflang', $link->getHreflang());
		}
		if ($link->getTitle()) {
			$this->writer->writeAttribute('title', $link->getTitle());
		}
		if ($link->getLength()) {
			$this->writer->writeAttribute('length', $link->getLength());
		}
		$this->writer->endElement();
	}


	/**
	 * Add text construct.
	 *
	 * @param string $element
	 * @param Constructs\Text $text
	 */
	private function addConstructText(string $element, Constructs\Text $text)
	{
		$this->writer->startElement($element);
		if ($text->getType()) {
			$this->writer->writeAttribute('type', $text->getType());
		}
		$this->writer->text($text->getText());
		$this->writer->endElement();
	}


	/**
	 * Add entry element.
	 *
	 * @param Elements\Entry $entry
	 */
	private function addElementEntry(Elements\Entry $entry): void
	{
		$this->writer->startElement('entry');
		$this->writer->writeElement('id', $entry->getId());
		$this->writer->writeElement('published', $entry->getPublished()->format(\DateTime::ATOM));
		$this->writer->writeElement('updated', $entry->getUpdated()->format(\DateTime::ATOM));
		if ($entry->getSummary()) {
			$this->addConstructText('summary', $entry->getSummary());
		}
		$this->addConstructText('title', $entry->getTitle());
		foreach ($entry->getLinks() as $links) {
			foreach ($links as $link) {
				$this->addElementLink($link);
			}
		}
		if ($entry->getContent()) {
			$this->addConstructText('content', $entry->getContent());
		}
		$this->writer->endElement();
	}


	/**
	 * Get resulting XML.
	 *
	 * @return string
	 */
	private function getXml()
	{
		$this->writer = new \XMLWriter();
		$this->writer->openMemory();
		$this->writer->startDocument('1.0', 'UTF-8');
		$this->writer->startElementNs(null, 'feed', 'http://www.w3.org/2005/Atom');
		$this->writer->writeElement('id', $this->id);
		$this->writer->writeElement('title', $this->title);
		$this->writer->writeElement('updated', $this->updated->format(\DateTime::ATOM));
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


	/**
	 * Convert to string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->xml) {
			$this->xml = $this->getXml();
		}
		return $this->xml;
	}

}
