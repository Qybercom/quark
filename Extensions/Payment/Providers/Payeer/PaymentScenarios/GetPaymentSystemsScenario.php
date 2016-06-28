<?php
namespace Quark\Extensions\Payment\Providers\Payeer\PaymentScenarios;

use Quark\QuarkDTO;
use Quark\QuarkCollection;
use Quark\QuarkModel;

use Quark\Extensions\Payment\IQuarkPaymentInstrument;
use Quark\Extensions\Payment\IQuarkPaymentProvider;
use Quark\Extensions\Payment\IQuarkPaymentScenario;

use Quark\Extensions\Payment\Providers\Payeer\Payeer;
use Quark\Extensions\Payment\Providers\Payeer\PayeerPaymentSystem;
use Quark\Extensions\Payment\Providers\Payeer\PayeerPaymentSystemField;

/**
 * Class GetPaymentSystemsScenario
 *
 * @package Quark\Extensions\Payment\Providers\Payeer\PaymentScenarios
 */
class GetPaymentSystemsScenario implements IQuarkPaymentScenario {
	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var QuarkCollection|PayeerPaymentSystem[] $_systems
	 */
	private $_systems;

	/**
	 * @param IQuarkPaymentProvider|Payeer $provider
	 * @param IQuarkPaymentInstrument $instrument = null
	 *
	 * @return bool
	 */
	public function Proceed (IQuarkPaymentProvider $provider, IQuarkPaymentInstrument $instrument = null) {
		$this->_response = $provider->API('getPaySystems');

		if (!$provider->ResponseOK($this->_response)) return false;
		if (!isset($this->_response->list)) return false;

		$this->_systems = new QuarkCollection(new PayeerPaymentSystem());

		foreach ($this->_response->list as $item) {
			/**
			 * @var QuarkModel|PayeerPaymentSystem $system
			 */
			$system = new QuarkModel(new PayeerPaymentSystem(), array(
				'id' => $item->id,
				'name' => $item->name,
				'currencies' => $item->currencies
			));

			foreach ($item->r_fields as $id => $field)
				$system->fields->Add(new QuarkModel(new PayeerPaymentSystemField(), array(
					'id' => $id,
					'name' => $field->name,
					'expression' => $field->reg_expr,
					'example' => $field->example
				)));

			$this->_systems->Add($system);
		}

		return true;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @return QuarkCollection|PayeerPaymentSystem[]
	 */
	public function Systems () {
		return $this->_systems;
	}
}