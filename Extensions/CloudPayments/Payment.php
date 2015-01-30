<?php
namespace Quark\Extensions\CloudPayments;

use Quark\IQuarkExtension;

/**
 * Class Payment
 *
 * @package Quark\Extensions\CloudPayments
 */
class Payment implements IQuarkExtension {
	/**
	 * @var IPaymentScenario
	 */
	private $_scenario;

	/**
	 * @param IPaymentScenario $scenario
	 */
	public function __construct (IPaymentScenario $scenario) {
		$this->_scenario = $scenario;
	}
}