<?php
namespace Quark\Extensions\Payment\Providers\Payeer;

use Quark\IQuarkModel;
use Quark\IQuarkLinkedModel;

use Quark\QuarkCollection;
use Quark\QuarkModel;

/**
 * Class PayeerPaymentSystem
 *
 * @property string $id = ''
 * @property string $name = ''
 * @property string[] $currencies = []
 * @property QuarkCollection|PayeerPaymentSystemField[] $fields
 *
 * @package Quark\Extensions\Payment\Providers\Payeer
 */
class PayeerPaymentSystem implements IQuarkModel, IQuarkLinkedModel {
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'name' => '',
			'currencies' => array(),
			'fields' => new QuarkCollection(new PayeerPaymentSystemField())
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel(new PayeerPaymentSystem(), array(
			'id' => $raw
		));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->id;
	}
}