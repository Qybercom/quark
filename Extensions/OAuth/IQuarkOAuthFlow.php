<?php
namespace Quark\Extensions\OAuth;

use Quark\QuarkDTO;

/**
 * Interface IQuarkOAuthFlow
 *
 * @package Quark\Extensions\OAuth
 */
interface IQuarkOAuthFlow {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function OAuthFlowServerAuthorize(QuarkDTO $request);

	/**
	 * @param QuarkDTO $request
	 *
	 * @return OAuthToken
	 */
	public function OAuthFlowServerToken(QuarkDTO $request);
}