<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

final readonly class PhotoFile
{

	public function __construct(
		private(set) string $file,
		private(set) int $width,
		private(set) int $height,
	) {
	}

}
