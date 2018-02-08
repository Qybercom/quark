<?php
namespace Quark\Extensions\Feedly;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkURI;
use Quark\QuarkDate;

use Quark\Extensions\Feedly\Components\FeedlyArticle;
use Quark\Extensions\Feedly\Components\FeedlyCategory;

use Quark\Extensions\SocialNetwork\SocialNetworkUser;

use Quark\Extensions\OAuth\OAuthAPIException;
use Quark\Extensions\OAuth\OAuthConsumerBehavior;

/**
 * Class Feedly
 *
 * @package Quark\Extensions\Feedly
 */
class Feedly implements IQuarkExtension {
	const PROFILE_CURRENT = '';

	const COUNT_DEFAULT = 20;
	const COUNT_MAX = 1000;

	use OAuthConsumerBehavior;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		if (func_num_args() != 0)
			$this->OAuthConfig($config);
	}

	/**
	 * @param OAuthAPIException $e
	 * @param string $action = ''
	 * @param string $message = ''
	 * @param $out = null
	 *
	 * @return mixed
	 */
	private function _error (OAuthAPIException $e, $action = '', $message = '', $out = null) {
		Quark::Log('[Feedly::' . $action . '] ' . $message . '. API error:', Quark::LOG_WARN);

		Quark::Trace($e->Request());
		Quark::Trace($e->Response());

		$this->_errorLast = $e->Error();

		return $out;
	}

	/**
	 * @return SocialNetworkUser
	 */
	public function Profile () {
		try {
			/** @noinspection PhpParamsInspection */
			$api = $this->_provider->OAuthAPI('/v3/profile');

			$user = new SocialNetworkUser($api->id, $api->fullName);
			$user->Email($api->email);
			$user->PhotoFromLink($api->picture);
			$user->RegisteredAt(QuarkDate::FromTimestamp($api->created));
			$user->Language($api->locale);
			$user->Verified($api->verified);

			if ($api->gender == 'male') $user->Gender(SocialNetworkUser::GENDER_MALE);
			if ($api->gender == 'female') $user->Gender(SocialNetworkUser::GENDER_FEMALE);

			return $user;
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'Profile', 'Can not get user profile', null);
		}
	}

	/**
	 * @param bool $all = true
	 *
	 * @return FeedlyCategory[]
	 */
	public function Categories ($all = true) {
		try {
			/** @noinspection PhpParamsInspection */
			$api = $this->_provider->OAuthAPI('/v3/categories');

			$categories = $api->Data();
			$out = array();

			if ($all)
				$out[] = new FeedlyCategory(FeedlyCategory::ALL);

			if (is_array($categories))
				foreach ($categories as $category)
					$out[] = new FeedlyCategory($category->id, $category->label);

			return $out;
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'Categories', 'Can not get categories', array());
		}
	}

	/**
	 * @param string $user = ''
	 * @param string $category = ''
	 * @param string $continuation = ''
	 * @param bool $unread = false
	 * @param int $count = self::COUNT_DEFAULT
	 *
	 * @return FeedlyArticle[]
	 */
	public function Articles ($user = '', $category = '', $continuation = '', $unread = false, $count = self::COUNT_DEFAULT) {
		try {
			/** @noinspection PhpParamsInspection */
			$api = $this->_provider->OAuthAPI(QuarkURI::Build('/v3/streams/contents', array(
				'streamId' => 'user/' . $user . '/category/' . $category,
				'count' => $count,
				'unreadOnly' => $unread ? 'true' : 'false',
				//'ranked' => 'newest',
				'continuation' => $continuation
			)));

			$out = array();

			if (isset($api->items) && is_array($api->items))
				foreach ($api->items as $item) {
					$article = new FeedlyArticle($item->originId, $item->title);
					$article->ID($item->id);

					if (isset($item->author)) $article->Author($item->author);
					if (isset($item->published)) $article->Created(QuarkDate::FromTimestamp($item->published));
					if (isset($item->crawled)) $article->Crawled(QuarkDate::FromTimestamp($item->crawled));
					if (isset($item->summary->content)) $article->Content($item->summary->content);
					if (isset($item->visual->url)) $article->Cover($item->visual->url);

					$out[] = $article;
				}

			return $out;
		}
		catch (OAuthAPIException $e) {
			return $this->_error($e, 'Articles', 'Can not get articles', array());
		}
	}
}