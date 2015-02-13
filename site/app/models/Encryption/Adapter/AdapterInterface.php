<?php
namespace MichalSpacekCz\Encryption\Adapter;

/**
 * Encryption Adapter Interface.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
interface AdapterInterface
{

	public function encrypt($clearText, $key, $cipher, $iv);

	public function decrypt($cipherText, $key, $cipher, $iv);

	public function getIvLength($cipher);

	public function createIv($length);

}
