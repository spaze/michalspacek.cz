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

	public const string TYPE_TEXT = 'text';
	public const string TYPE_HTML = 'html';


	public function __construct(
		private string $text,
		private ?string $type = null,
	) {
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
