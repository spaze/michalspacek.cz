<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use MichalSpacekCz\Http\FetchMetadata\FetchMetadataHeader;
use Nette\Http\FetchDest;
use Nette\Http\FetchSite;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Http\UrlScript;
use Nette\Utils\Arrays;
use Override;

final class Request implements IRequest
{

	/** @var array<string, mixed> */
	private array $post = [];

	/** @var array<string, string> */
	private array $cookies = [];

	/** @var array<string, FileUpload> */
	private array $files = [];

	private string $method = '';

	/** @var array<string, string> */
	private array $headers = [];

	private ?string $remoteAddress = null;

	private UrlScript $url;


	public function __construct()
	{
		$this->url = new UrlScript();
	}


	#[Override]
	public function getUrl(): UrlScript
	{
		return $this->url;
	}


	public function setUrl(UrlScript $url): void
	{
		$this->url = $url;
	}


	/**
	 * @return ($key is null ? array<mixed> : mixed|null) The `|null` is for Psalm, which reads the `mixed` in Nette's own type as not nullable
	 */
	#[Override]
	public function getQuery(?string $key = null): mixed
	{
		return $key === null ? $this->url->getQueryParameters() : $this->url->getQueryParameter($key);
	}


	/**
	 * @return ($key is null ? array<mixed> : mixed|null) The `|null` is for Psalm, which reads the `mixed` in Nette's own type as not nullable
	 */
	#[Override]
	public function getPost(?string $key = null): mixed
	{
		return $key === null ? $this->post : $this->post[$key] ?? null;
	}


	public function setPost(string $key, mixed $value): void
	{
		$this->post[$key] = $value;
	}


	/**
	 * @param string $key
	 * @return FileUpload|null
	 */
	#[Override]
	public function getFile(string $key): ?FileUpload
	{
		return $this->files[$key] ?? null;
	}


	/**
	 * @return array<string, FileUpload>
	 */
	#[Override]
	public function getFiles(): array
	{
		return $this->files;
	}


	#[Override]
	public function getCookie(string $key): ?string
	{
		return $this->cookies[$key] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	#[Override]
	public function getCookies(): array
	{
		return $this->cookies;
	}


	#[Override]
	public function getMethod(): string
	{
		return $this->method;
	}


	#[Override]
	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	#[Override]
	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	#[Override]
	public function getHeaders(): array
	{
		return $this->headers;
	}


	#[Override]
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	#[Override]
	public function isAjax(): bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	/**
	 * Nette's AccessPolicy calls isFrom() on the request for `#[Requires(sameOrigin: true)]` and UI\Form for a submit,
	 * and IRequest doesn't declare it, so there is no #[Override] and the dead-code detector can't see the call.
	 * The answer is what the Sec-Fetch-* headers say, the way a browser says it; the real request also takes the `_nss`
	 * cookie from a browser that sends no Sec-Fetch-Site, a fallback no test needs.
	 *
	 * @param FetchSite|list<FetchSite> $site
	 * @param FetchDest|list<FetchDest>|null $dest
	 * @phpstan-ignore shipmonk.deadMethod
	 */
	public function isFrom(FetchSite|array $site, FetchDest|array|null $dest = null, ?bool $user = null): bool
	{
		$actualSite = FetchSite::tryFrom($this->getHeader(FetchMetadataHeader::Site->value) ?? '');
		$actualDest = FetchDest::tryFrom($this->getHeader(FetchMetadataHeader::Dest->value) ?? '');
		$actualUser = $this->getHeader(FetchMetadataHeader::User->value) === '?1';
		return $actualSite !== null
			&& Arrays::contains(is_array($site) ? $site : [$site], $actualSite)
			&& ($dest === null || ($actualDest !== null && Arrays::contains(is_array($dest) ? $dest : [$dest], $actualDest)))
			&& ($user === null || $user === $actualUser);
	}


	#[Override]
	public function getRemoteAddress(): ?string
	{
		return $this->remoteAddress;
	}


	#[Override]
	public function getRemoteHost(): ?string
	{
		return null;
	}


	#[Override]
	public function getRawBody(): ?string
	{
		return null;
	}


	public function setCookie(string $name, string $value): void
	{
		$this->cookies[$name] = $value;
	}


	public function setMethod(string $method): void
	{
		$this->method = $method;
	}


	public function setHeader(string $name, string $value): void
	{
		$this->headers[strtolower($name)] = $value;
	}


	public function setRemoteAddress(string $remoteAddress): void
	{
		$this->remoteAddress = $remoteAddress;
	}


	public function reset(): void
	{
		$this->post = [];
		$this->cookies = [];
		$this->files = [];
		$this->method = '';
		$this->headers = [];
		$this->remoteAddress = null;
	}

}
