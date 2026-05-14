<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Spaze\SvgIcons\Exceptions\SvgIconException;

class SvgIcons
{

	public function __construct(
		private readonly string $dir,
	) {
	}


	/**
	 * @param string $icon The icon name without the `.svg` extension
	 * @throws SvgIconException
	 */
	public function getSvg(string $icon): string
	{
		$iconName = basename($icon);
		try {
			return FileSystem::read(sprintf('%s/%s.svg', $this->dir, $iconName));
		} catch (IOException $e) {
			throw new SvgIconException($iconName, $this->dir, previous: $e);
		}
	}

}
