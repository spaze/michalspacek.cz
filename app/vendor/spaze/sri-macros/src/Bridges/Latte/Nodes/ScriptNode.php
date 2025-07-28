<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\Compiler\PrintContext;
use Spaze\SubresourceIntegrity\HtmlElement;

class ScriptNode extends SriNode
{

	public function print(PrintContext $context): string
	{
		$attributes = [
			'src' => $this->sriConfig->getUrl($this->resources, HtmlElement::Script),
			'integrity' => $this->sriConfig->getHash($this->resources, HtmlElement::Script),
		];
		return $this->printTag($context, $this->position, $attributes, HtmlElement::Script);
	}

}
