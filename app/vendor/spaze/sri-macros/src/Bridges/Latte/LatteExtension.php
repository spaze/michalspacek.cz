<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte;

use Latte\Compiler\Tag;
use Latte\Extension;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ResourceHashNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ResourceUrlNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\ScriptNode;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\SriNodeFactory;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\StyleSheetNode;

class LatteExtension extends Extension
{

	public function __construct(
		private readonly SriNodeFactory $sriNodeFactory,
	) {
	}


	public function getTags(): array
	{
		return [
			'script' => fn(Tag $tag) => $this->sriNodeFactory->create($tag, ScriptNode::class),
			'stylesheet' => fn(Tag $tag) => $this->sriNodeFactory->create($tag, StyleSheetNode::class),
			'styleSheet' => fn(Tag $tag) => $this->sriNodeFactory->create($tag, StyleSheetNode::class),
			'resourceUrl' => fn(Tag $tag) => $this->sriNodeFactory->create($tag, ResourceUrlNode::class),
			'resourceHash' => fn(Tag $tag) => $this->sriNodeFactory->create($tag, ResourceHashNode::class),
		];
	}

}
