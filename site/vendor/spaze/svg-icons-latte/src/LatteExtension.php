<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons;

use Latte\Compiler\Tag;
use Latte\Extension;
use Spaze\SvgIcons\Nodes\IconNodeFactory;

class LatteExtension extends Extension
{

	public function __construct(
		private readonly IconNodeFactory $iconNodeFactory,
	) {
	}


	public function getTags(): array
	{
		return [
			'icon' => fn(Tag $tag) => $this->iconNodeFactory->create($tag),
		];
	}

}
