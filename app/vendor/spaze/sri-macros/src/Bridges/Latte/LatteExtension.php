<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte;

use Latte\Compiler\Tag;
use Latte\Extension;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ResourceHashNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ResourceUrlNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ScriptNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\StyleSheetNode;
use Spaze\SubresourceIntegrity\Config;

class LatteExtension extends Extension
{

	public function __construct(
		private readonly Config $sriConfig,
	) {
	}


	public function getTags(): array
	{
		return [
			'script' => fn(Tag $tag) => ScriptNode::create($tag, $this->sriConfig),
			'stylesheet' => fn(Tag $tag) => StyleSheetNode::create($tag, $this->sriConfig),
			'styleSheet' => fn(Tag $tag) => StyleSheetNode::create($tag, $this->sriConfig),
			'resourceUrl' => fn(Tag $tag) => ResourceUrlNode::create($tag, $this->sriConfig),
			'resourceHash' => fn(Tag $tag) => ResourceHashNode::create($tag, $this->sriConfig),
		];
	}

}
