<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons\Nodes;

use Generator;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;

class IconNode extends StatementNode
{

	public function __construct(
		private readonly string $svg,
	) {
	}


	public function print(PrintContext $context): string
	{
		return $context->format('echo %dump %line;', $this->svg, $this->position);
	}


	public function &getIterator(): Generator
	{
		/**
		 * @noinspection PhpBooleanCanBeSimplifiedInspection
		 * @phpstan-ignore-next-line
		 */
		false && yield;
	}

}
