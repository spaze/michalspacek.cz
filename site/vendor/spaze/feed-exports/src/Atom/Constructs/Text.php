<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Constructs;

/**
 * Atom text construct.
 *
 * @author Michal Špaček
 */
class Text
{

	/** @var string */
	public const TYPE_TEXT = 'text';

	/** @var string */
	public const TYPE_HTML = 'html';

	/** @var string */
	protected $text;

	/** @var string */
	protected $type;


	/**
	 * Text constructor.
	 *
	 * @param string $text
	 * @param string|null $type
	 */
	public function __construct(string $text, ?string $type = null)
	{
		$this->text = $text;
		$this->type = $type;
	}


	/**
	 * Get text.
	 *
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}


	/**
	 * Get text type.
	 *
	 * @return string|null
	 */
	public function getType(): ?string
	{
		return $this->type;
	}

}
