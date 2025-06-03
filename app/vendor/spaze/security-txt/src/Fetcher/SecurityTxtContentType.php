<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

final readonly class SecurityTxtContentType
{

	/**
	 * @var lowercase-string
	 */
	private string $lowercaseContentType;

	/**
	 * @var lowercase-string|null
	 */
	private ?string $lowercaseCharset;


	public function __construct(
		private string $contentType,
		private ?string $charset,
	) {
		$this->lowercaseContentType = strtolower(trim($this->contentType));
		$this->lowercaseCharset = $this->charset !== null ? strtolower(trim($this->charset)) : null;
	}


	public function getContentType(): string
	{
		return $this->contentType;
	}


	public function getCharset(): ?string
	{
		return $this->charset;
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
	public function getLowercaseCharset(): ?string
	{
		return $this->lowercaseCharset;
	}

}
