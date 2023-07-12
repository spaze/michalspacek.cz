<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\ExtensionWithNoContentTypeException;
use MichalSpacekCz\Media\Exceptions\UnsupportedContentTypeException;

class SupportedImageFileFormats
{

	/** @var non-empty-array<string, string> */
	private array $supportedMainImages = [
		'image/gif' => 'gif',
		'image/png' => 'png',
		'image/jpeg' => 'jpg',
	];

	/** @var non-empty-array<string, string> */
	private array $supportedAlternativeImages = [
		'image/webp' => 'webp',
	];


	/**
	 * @throws ContentTypeException
	 */
	public function getMainExtensionByContentType(string $contentType): string
	{
		if (!isset($this->supportedMainImages[$contentType])) {
			throw new UnsupportedContentTypeException($contentType, $this->supportedMainImages);
		}
		return $this->supportedMainImages[$contentType];
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getAlternativeExtensionByContentType(string $contentType): string
	{
		if (!isset($this->supportedAlternativeImages[$contentType])) {
			throw new UnsupportedContentTypeException($contentType, $this->supportedAlternativeImages);
		}
		return $this->supportedAlternativeImages[$contentType];
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getAlternativeContentTypeByExtension(string $extension): string
	{
		$types = array_flip($this->supportedAlternativeImages);
		if (!isset($types[$extension])) {
			throw new ExtensionWithNoContentTypeException($extension, $this->supportedAlternativeImages);
		}
		return $types[$extension];
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getMainContentTypes(): array
	{
		return array_keys($this->supportedMainImages);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getAlternativeContentTypes(): array
	{
		return array_keys($this->supportedAlternativeImages);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getMainExtensions(): array
	{
		return array_values($this->supportedMainImages);
	}


	/**
	 * @return non-empty-list<string>
	 */
	public function getAlternativeExtensions(): array
	{
		return array_values($this->supportedAlternativeImages);
	}

}
