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
	 * @param string $config = ''
	 * @param string $id = ''
	 *
	 * @return QuarkModel|PayPalBillingPlan
	 */
	public static function Config ($config = '', $id = '') {
		$plan = new self();
		$plan->_payment = new Payment($config);
		$plan->id = $id;

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
	 * @param string $duration = PayPal::BILLING_TYPE_DURATION_INFINITE
	 *
	 * @return BillingPlanCreateScenario
	 */
	public function PlanCreate ($name = '', $description = '', $duration = PayPal::BILLING_TYPE_DURATION_INFINITE) {
		return $this->_payment->Scenario(new BillingPlanCreateScenario($name, $description, $duration));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingPlanActivateScenario
	 */
	public function PlanActivate ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingPlanActivateScenario($this->id));
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingPlanDeactivateScenario
	 */
	public function PlanDeactivate ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingPlanDeactivateScenario($this->id));
	}

	/**
	 * @return BillingPlanUpdateScenario
	 */
	public function PlanUpdate () {
		return $this->_payment->Scenario(new BillingPlanUpdateScenario());
	}

	/**
	 * @param string $id = ''
	 *
	 * @return BillingPlanGetScenario
	 */
	public function PlanGet ($id = '') {
		if (func_num_args() != 0)
			$this->id = $id;

		return $this->_payment->Scenario(new BillingPlanGetScenario($this->id));
	}

	/**
	 * @param int $page = 0
	 * @param string $state = PayPal::BILLING_STATE_CREATED
	 * @param int $page_size = 10
	 * @param bool $total = true
	 *
	 * @return BillingPlanListScenario
	 */
	public function PlanList ($page = 0, $state = PayPal::BILLING_STATE_CREATED, $page_size = 10, $total = true) {
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