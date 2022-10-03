<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\Compiler\PrintContext;
use Spaze\SubresourceIntegrity\HtmlElement;

class StyleSheetNode extends SriNode
{

	protected static ?HtmlElement $targetHtmlElement = HtmlElement::Link;


	public function print(PrintContext $context): string
	{
		return $this->printTag($context, $this->position, [
			'rel' => 'stylesheet',
			'href' => $this->url,
			'integrity' => $this->hash,
		]);
	}

}
