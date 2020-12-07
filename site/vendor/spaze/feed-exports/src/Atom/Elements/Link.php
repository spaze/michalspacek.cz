<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Elements;

/**
 * Atom link element.
 *
 * @author Michal Špaček
 */
class Link
{

	/** @var string */
	public const REL_SELF = 'self';

	/** @var string */
	public const REL_ALTERNATE = 'alternate';

	/** @var string */
	protected $href;

	/** @var string|null */
	protected $rel;

	/** @var string|null */
	protected $type;

	/** @var string|null */
	protected $hreflang;

	/** @var string|null */
	protected $title;

	/** @var integer|null */
	protected $length;


	/**
	 * @param string $href
	 * @param string|null $rel
	 * @param string|null $type
	 * @param string|null $hreflang
	 * @param string|null $title
	 * @param int|null $length An advisory length of the linked content in octets
	 */
	public function __construct(string $href, ?string $rel = null, ?string $type = null, ?string $hreflang = null, ?string $title = null, ?int $length = null)
	{
		$this->href = $href;
		$this->rel = $rel;
		$this->type = $type;
		$this->hreflang = $hreflang;
		$this->title = $title;
		$this->length = $length;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	public function getRel(): ?string
	{
		return $this->rel;
	}


	public function getType(): ?string
	{
		return $this->type;
	}


	public function getHreflang(): ?string
	{
		return $this->hreflang;
	}


	public function getTitle(): ?string
	{
		return $this->title;
	}


	public function getLength(): ?int
	{
		return $this->length;
	}

}
