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

	/** @var string|null */
	protected $type;


	public function __construct(string $text, ?string $type = null)
	{
		$this->text = $text;
		$this->type = $type;
	}


	public function getText(): string
	{
		return $this->text;
	}


	public function getType(): ?string
	{
		return $this->type;
	}

}
