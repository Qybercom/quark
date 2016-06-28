<?php
namespace Quark\Extensions\Payment\Providers\Payeer;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

/**
 * Class PayeerPaymentSystemField
 *
 * @package Quark\Extensions\Payment\Providers\Payeer
 */
class PayeerPaymentSystemField implements IQuarkModel, IQuarkStrongModel {
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'name' => '',
			'expression' => '',
			'example' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}