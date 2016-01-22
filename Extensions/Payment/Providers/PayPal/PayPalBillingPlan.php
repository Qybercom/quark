<?php
namespace Quark\Extensions\Payment\Providers\PayPal;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;

use Quark\QuarkDTO;
use Quark\QuarkModel;

use Quark\Extensions\Payment\Payment;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanActivateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanCreateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanDeactivateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanUpdateScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanGetScenario;
use Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanListScenario;

/**
 * Class PayPalBillingPlan
 *
 * @property string $id = ''
 *
 * @package Quark\Extensions\Payment\Providers\PayPal
 */
class PayPalBillingPlan implements IQuarkModel, IQuarkLinkedModel {
	/**
	 * @var Payment $_payment
	 */
	private $_payment;

	/**
	 * @param string $config
	 *
	 * @return QuarkModel|PayPalBillingPlan
	 */
	public static function Config ($config = '') {
		$plan = new self();
		$plan->_payment = new Payment($config);

		return $plan->_payment->Config()->PaymentProvider() instanceof PayPal ? new QuarkModel($plan) : null;
	}

	/**
	 * @return bool
	 */
	public function Proceed () {
		return $this->_payment->Proceed();
	}

	/**
	 * @return QuarkDTO
	 */
	public function Response () {
		return $this->_payment->Response();
	}

	/**
	 * @param string $name = ''
	 * @param string $description = ''
	 * @param string $duration = PayPalBilling::TYPE_DURATION_INFINITE
	 *
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanCreateScenario
	 */
	public function PlanCreate ($name = '', $description = '', $duration = PayPalBilling::TYPE_DURATION_INFINITE) {
		return $this->_payment->Scenario(new BillingPlanCreateScenario($name, $description, $duration));
	}

	/**
	 * @param string $plan = ''
	 *
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanActivateScenario
	 */
	public function PlanActivate ($plan = '') {
		return $this->_payment->Scenario(new BillingPlanActivateScenario($plan));
	}

	/**
	 * @param string $plan = ''
	 *
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanDeactivateScenario
	 */
	public function PlanDeactivate ($plan = '') {
		return $this->_payment->Scenario(new BillingPlanDeactivateScenario($plan));
	}

	/**
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanUpdateScenario
	 */
	public function PlanUpdate () {
		return $this->_payment->Scenario(new BillingPlanUpdateScenario());
	}

	/**
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanUpdateScenario
	 */
	public function PlanGet () {
		return $this->_payment->Scenario(new BillingPlanGetScenario($this->id));
	}

	/**
	 * @param int $page = 0
	 * @param string $state = PayPalBilling::STATE_CREATED
	 * @param int $page_size = 10
	 * @param bool $total = true
	 *
	 * @return \Quark\Extensions\Payment\Providers\PayPal\PaymentScenarios\BillingPlanUpdateScenario
	 */
	public function PlanList ($page = 0, $state = PayPalBilling::STATE_CREATED, $page_size = 10, $total = true) {
		return $this->_payment->Scenario(new BillingPlanListScenario($page, $state, $page_size, $total));
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => ''
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
		return new QuarkModel(new PayPalBillingPlan(), array(
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