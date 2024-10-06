<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\Compiler\PrintContext;

class ResourceUrlNode extends SriNode
{

	public function print(PrintContext $context): string
	{
		return $context->format(
			'echo %escape(%dump) %line;',
			$this->url,
			$this->position,
		);
	}

}
