<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\Compiler\PrintContext;
use Spaze\SubresourceIntegrity\HtmlElement;

class ScriptNode extends SriNode
{

	protected static ?HtmlElement $targetHtmlElement = HtmlElement::Script;


	public function print(PrintContext $context): string
	{
		return $this->printTag($context, $this->position, [
			'src' => $this->url,
			'integrity' => $this->hash,
		]);
	}

}
