<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

final readonly class SecurityTxtFetchHostContentType
{

	/**
	 * @var lowercase-string
	 */
	private string $lowercaseContentType;

	/**
	 * @var lowercase-string|null
	 */
	private ?string $lowercaseCharsetParameter;


	public function __construct(
		private string $contentType,
		private ?string $charsetParameter,
	) {
		$this->lowercaseContentType = strtolower(trim($this->contentType));
		$this->lowercaseCharsetParameter = $this->charsetParameter !== null ? strtolower(trim($this->charsetParameter)) : null;
	}


	public function getContentType(): string
	{
		return $this->contentType;
	}


	public function getCharsetParameter(): ?string
	{
		return $this->charsetParameter;
	}


	/**
	 * @return lowercase-string
	 */
	public function getLowercaseContentType(): string
	{
		return $this->lowercaseContentType;
	}


	/**
	 * @return lowercase-string|null
	 */
	public function getLowercaseCharsetParameter(): ?string
	{
		return $this->lowercaseCharsetParameter;
	}

}
