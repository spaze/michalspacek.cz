<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

/**
 * SubresourceIntegrity\FileBuilder service.
 *
 * @author Michal Špaček
 */
class FileBuilder
{

	/**
	 * Get build file mode data.
	 * @param string[] $resources
	 * @param string $pathPrefix Should be an absolute path
	 * @param string $buildPrefix
	 * @return \stdClass
	 */
	public function build(array $resources, string $pathPrefix, string $buildPrefix): \stdClass
	{
		$content = $extension = '';
		foreach ($resources as $resource) {
			$localFilename = sprintf('%s/%s', rtrim($pathPrefix, '/'), ltrim($resource, '/'));
			$content .= file_get_contents($localFilename);
			$extension = $extension ?: pathinfo($localFilename, PATHINFO_EXTENSION);
		}
		$build = sprintf('%s/%s.%s',
			trim($buildPrefix, '/'),
			rtrim(strtr(base64_encode(hash('sha256', $content, true)), '+/', '-_'), '='),  // Encoded to base64url, see https://tools.ietf.org/html/rfc4648#section-5
			$extension
		);
		$buildFilename = sprintf('%s/%s', rtrim($pathPrefix, '/'), $build);

		if (!is_writable(dirname($buildFilename))) {
			throw new Exceptions\DirectoryNotWritableException('Directory ' . dirname($buildFilename) . " doesn't exist or isn't writable");
		}

		file_put_contents($buildFilename, $content);

		$data = new \stdClass();
		$data->url = $build;
		$data->filename = $buildFilename;
		return $data;
	}

}
