<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use finfo;
use MichalSpacekCz\Utils\Exceptions\EmptyFilenameException;

final readonly class MimeType
{

	private const array IMAGE_MIME_TYPES_BY_EXTENSION = [
		'gif' => 'image/gif',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'webp' => 'image/webp',
	];


	/**
	 * @throws EmptyFilenameException
	 */
	public static function detectMimeType(string $filename): ?string
	{
		if ($filename === '') {
			throw new EmptyFilenameException();
		}
		$type = new finfo(FILEINFO_MIME_TYPE)->file($filename);
		return $type !== false ? $type : null;
	}


	/**
	 * @param lowercase-string $extension
	 */
	public static function getMimeTypeByExtension(string $extension): ?string
	{
		return self::IMAGE_MIME_TYPES_BY_EXTENSION[$extension] ?? null;
	}

}
