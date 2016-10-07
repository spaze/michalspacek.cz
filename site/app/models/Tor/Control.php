<?php
namespace MichalSpacekCz\Tor;

/**
 * Tor control service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Control
{

	/** @var string */
	private $host;

	/** @var integer */
	private $port;

	/** @var string */
	private $password;

	/** @var resource */
	private $fp;


	/**
	 * @param string $host
	 * @param integer $port
	 * @param string $password
	 */
	public function setControl($host, $port, $password)
	{
		$this->host = $host;
		$this->port = $port;
		$this->password = $password;
	}


	/**
	 * Request new Tor circuits.
	 *
	 * @return null
	 */
	public function cleanCircuits()
	{
		$this->open()->authenticate();
		$this->command('SIGNAL', 'NEWNYM');
		$this->close();
	}


	/**
	 * Open connection to control port.
	 *
	 * @return self
	 */
	private function open()
	{
		$this->fp = fsockopen($this->host, $this->port, $errNo, $errStr);
		if ($this->fp === false) {
			throw new \RuntimeException("Can't connect to control port: {$errStr}", $errNo);
		}
		return $this;
	}


	private function authenticate()
	{
		$this->command('AUTHENTICATE', "\"{$this->password}\"");
		list($code, $message) = explode(' ', $this->read(), 2);
		if ($code != 250) {
			throw new \RuntimeException("Auth failed: {$message}", $code);
		}
	}


	private function read($length = 1024)
	{
		return fread($this->fp, $length);
	}


	private function command($command, $data = null)
	{
		fputs($this->fp, trim("{$command} {$data}"). "\r\n");
		return $this;
	}


	/**
	 * Close control port connection.
	 *
	 * @return self
	 */
	private function close()
	{
		$this->command('QUIT');
		fclose($this->fp);
		return $this;
	}

}
