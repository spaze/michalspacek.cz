<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Resource\ResourceInterface;
use stdClass;

class FileBuilder
{

	public const EXT_CSS = 'css';
	public const EXT_JS = 'js';


	/**
	 * Get build file mode data.
	 *
	 * @param ResourceInterface[] $resources
	 * @param string $pathPrefix Should be an absolute path
	 * @param string $buildPrefix
	 * @param string|null $extension
	 * @return stdClass
	 */
	public function build(array $resources, string $pathPrefix, string $buildPrefix, ?string $extension = null): stdClass
	{
		$content = '';
		foreach ($resources as $resource) {
			$content .= $resource->getContent();
			$extension = $extension ?: $resource->getExtension();
		}
		if (!$extension) {
			throw new Exceptions\UnknownExtensionException();
		}
		$build = sprintf(
			'%s/%s.%s',
			trim($buildPrefix, '/'),
			rtrim(strtr(base64_encode(hash('sha256', $content, true)), '+/', '-_'), '='),  // Encoded to base64url, see https://tools.ietf.org/html/rfc4648#section-5
			$extension,
		);
		$buildFilename = sprintf('%s/%s', rtrim($pathPrefix, '/'), $build);

		if (!is_writable(dirname($buildFilename))) {
			throw new Exceptions\DirectoryNotWritableException('Directory ' . dirname($buildFilename) . " doesn't exist or isn't writable");
		}

		file_put_contents($buildFilename, $content);

		$data = new stdClass();
		$data->url = $build;
		$data->filename = $buildFilename;
		return $data;
	}

}
