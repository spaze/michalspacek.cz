<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\ExtensionWithNoContentTypeException;
use MichalSpacekCz\Media\Exceptions\UnsupportedContentTypeException;

class SupportedImageFileFormats
{

	private const SUPPORTED_MAIN_IMAGES = [
		'image/gif' => 'gif',
		'image/png' => 'png',
		'image/jpeg' => 'jpg',
	];

	private const SUPPORTED_ALTERNATIVE_IMAGES = [
		'image/webp' => 'webp',
	];


	/**
	 * @throws ContentTypeException
	 */
	public function getMainExtensionByContentType(string $contentType): string
	{
		if (!isset(self::SUPPORTED_MAIN_IMAGES[$contentType])) {
			throw new UnsupportedContentTypeException($contentType, self::SUPPORTED_MAIN_IMAGES);
		}
		return self::SUPPORTED_MAIN_IMAGES[$contentType];
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getAlternativeExtensionByContentType(string $contentType): string
	{
		if (!isset(self::SUPPORTED_ALTERNATIVE_IMAGES[$contentType])) {
			throw new UnsupportedContentTypeException($contentType, self::SUPPORTED_ALTERNATIVE_IMAGES);
		}
		return self::SUPPORTED_ALTERNATIVE_IMAGES[$contentType];
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getAlternativeContentTypeByExtension(string $extension): string
	{
		$types = array_flip(self::SUPPORTED_ALTERNATIVE_IMAGES);
		if (!isset($types[$extension])) {
			throw new ExtensionWithNoContentTypeException($extension, self::SUPPORTED_ALTERNATIVE_IMAGES);
		}
		return $types[$extension];
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getMainContentTypes(): array
	{
		return array_keys(self::SUPPORTED_MAIN_IMAGES);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getAlternativeContentTypes(): array
	{
		return array_keys(self::SUPPORTED_ALTERNATIVE_IMAGES);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getMainExtensions(): array
	{
		return array_values(self::SUPPORTED_MAIN_IMAGES);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getAlternativeExtensions(): array
	{
		return array_values(self::SUPPORTED_ALTERNATIVE_IMAGES);
	}

}
