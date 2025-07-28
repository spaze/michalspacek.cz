<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\Compiler\PrintContext;
use Spaze\SubresourceIntegrity\HtmlElement;

class StyleSheetNode extends SriNode
{

	public function print(PrintContext $context): string
	{
		$attributes = [
			'rel' => 'stylesheet',
			'href' => $this->sriConfig->getUrl($this->resources, HtmlElement::Link),
			'integrity' => $this->sriConfig->getHash($this->resources, HtmlElement::Link),
		];
		return $this->printTag($context, $this->position, $attributes, HtmlElement::Link);
	}

}
