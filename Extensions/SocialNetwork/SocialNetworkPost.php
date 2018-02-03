<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkDate;
use Quark\QuarkFile;
use Quark\QuarkObject;

/**
 * Class SocialNetworkPost
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkPost {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_author = ''
	 */
	private $_author = '';

	/**
	 * @var QuarkDate $_dateCreated
	 */
	private $_dateCreated;

	/**
	 * @var QuarkDate $_dateUpdated
	 */
	private $_dateUpdated;

	/**
	 * @var $_audience
	 */
	private $_audience;

	/**
	 * @var bool $_sensitive = false
	 */
	private $_sensitive = false;

	/**
	 * @var string $_reply = ''
	 */
	private $_reply = '';

	/**
	 * @var QuarkFile[] $_attachments = []
	 */
	private $_attachments = array();

	/**
	 * @var string $_target = null
	 */
	private $_target = null;

	/**
	 * @var string $_site = ''
	 */
	private $_site = '';

	/**
	 * @var string $_title = ''
	 */
	private $_title = '';

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @param string $content = ''
	 * @param $audience = null
	 * @param string $target = null
	 */
	public function __construct ($content = '', $audience = null, $target = null) {
		$this->Content($content);
		$this->Audience($audience);
		$this->Target($target);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URL ($url = '') {
		if (func_num_args() != 0)
			$this->_url = $url;

		return $this->_url;
	}

	/**
	 * @param string $author = ''
	 *
	 * @return string
	 */
	public function Author ($author = '') {
		if (func_num_args() != 0)
			$this->_author = $author;

		return $this->_author;
	}

	/**
	 * @param $audience = null
	 *
	 * @return mixed
	 */
	public function Audience ($audience = null) {
		if (func_num_args() != 0)
			$this->_audience = $audience;

		return $this->_audience;
	}

	/**
	 * @param bool $sensitive = false
	 *
	 * @return bool
	 */
	public function Sensitive ($sensitive = false) {
		if (func_num_args() != 0)
			$this->_sensitive = $sensitive;

		return $this->_sensitive;
	}

	/**
	 * @param string $reply = ''
	 *
	 * @return string
	 */
	public function Reply ($reply = '') {
		if (func_num_args() != 0)
			$this->_reply = $reply;

		return $this->_reply;
	}

	/**
	 * @param QuarkFile $attachment = null
	 *
	 * @return SocialNetworkPost
	 */
	public function Attach (QuarkFile $attachment = null) {
		if (func_num_args() != 0)
			$this->_attachments[] = $attachment;

		return $this;
	}

	/**
	 * @param QuarkFile[] $attachments = []
	 *
	 * @return QuarkFile[]
	 */
	public function Attachments ($attachments = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($attachments, new QuarkFile()))
			$this->_attachments = $attachments;

		return $this->_attachments;
	}

	/**
	 * @param string $target = null
	 *
	 * @return string
	 */
	public function Target ($target = null) {
		if (func_num_args() != 0)
			$this->_target = $target;

		return $this->_target;
	}

	/**
	 * @param string $site = ''
	 *
	 * @return string
	 */
	public function Site ($site = '') {
		if (func_num_args() != 0)
			$this->_site = $site;

		return $this->_site;
	}

	/**
	 * @param string $title = ''
	 *
	 * @return string
	 */
	public function Title ($title = '') {
		if (func_num_args() != 0)
			$this->_title = $title;

		return $this->_title;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() != 0)
			$this->_content = $content;

		return $this->_content;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return SocialNetworkPost
	 */
	public function Create ($content = '') {
		if (func_num_args() != 0)
			$this->Content($content);

		$this->_dateCreated = QuarkDate::GMTNow();

		return $this;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return SocialNetworkPost
	 */
	public function Update ($content = '') {
		if (func_num_args() != 0)
			$this->Content($content);

		$this->_dateUpdated = QuarkDate::GMTNow();

		return $this;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateCreated (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_dateCreated = $date;

		return $this->_dateCreated;
	}

	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateUpdated (QuarkDate $date = null) {
		if (func_num_args() != 0)
			$this->_dateUpdated = $date;

		return $this->_dateUpdated;
	}
}