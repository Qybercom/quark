<?php
namespace Quark\Scenarios\Manage;

use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkHTTPServerHost;
use Quark\QuarkArchException;
use Quark\QuarkThreadSet;

/**
 * Class Index
 *
 * @package Quark\Scenarios\Manage
 */
class Index implements IQuarkTask {
	/**
	 * @var string[] $_secure
	 */
	private $_secure = array(
		'/Services',
		'/Models',
		'/ViewModels',
		'/Views',
	);

	/**
	 * @param string[] $secure = []
	 *
	 * @return string[]
	 */
	public function Secure ($secure = []) {
		if (func_num_args() != 0)
			$this->_secure = $secure;

		return $this->_secure;
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function Task ($argc, $argv) {
		$manage = Quark::Config()->WebManagement();

		if ($manage == null)
			throw new QuarkArchException('Attempt to start a not configured management web panel instance');

		$namespace = 'Quark/Scenarios/Manage/Web/Services';
		$root = __DIR__ . '/Web';
		$base = $root . '/Services';

		$http = new QuarkHTTPServerHost($manage, $root, $base, $namespace, Quark::Config()->WebManagementCertificate());

		$http->AllowHost('127.0.0.1');
		$http->Deny($this->_secure);

		if (!$http->Bind())
			throw new QuarkArchException('Can not bind management web panel instance on ' . $manage);

		QuarkThreadSet::Queue(function () use (&$http) {
			$http->Pipe();
		});
	}
}