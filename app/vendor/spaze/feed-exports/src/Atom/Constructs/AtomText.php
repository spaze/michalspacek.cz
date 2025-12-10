<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Constructs;

class AtomText
{

	public function __construct(
		private string $text,
		private ?AtomTextType $type = null,
	) {
	}


	public function getText(): string
	{
		return $this->text;
	}


	public function getType(): ?AtomTextType
	{
		return $this->type;
	}

}
