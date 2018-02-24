<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkDate;
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
	 * @var string $_authorPublic = ''
	 */
	private $_authorPublic = '';

	/**
	 * @var QuarkDate $_dateCreated
	 */
	private $_dateCreated;

	/**
	 * @var QuarkDate $_dateUpdated
	 */
	private $_dateUpdated;

	/**
	 * @var SocialNetworkPostAudience $_audience
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
	 * @var SocialNetworkPostAttachment[] $_attachments = []
	 */
	private $_attachments = array();

	/**
	 * @var string $_target = null
	 */
	private $_target = null;

	/**
	 * @var string[] $_categories = []
	 */
	private $_categories = array();

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
	 * @param string $target = null
	 * @param SocialNetworkPostAudience $audience = null
	 */
	public function __construct ($content = '', $target = null, SocialNetworkPostAudience $audience = null) {
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
	 * @param string $author = ''
	 *
	 * @return string
	 */
	public function AuthorPublic ($author = '') {
		if (func_num_args() != 0)
			$this->_authorPublic = $author;

		return $this->_authorPublic;
	}

	/**
	 * @param SocialNetworkPostAudience $audience = null
	 *
	 * @return SocialNetworkPostAudience
	 */
	public function &Audience (SocialNetworkPostAudience $audience = null) {
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
	 * @param SocialNetworkPostAttachment $attachment = null
	 *
	 * @return SocialNetworkPost
	 */
	public function Attach (SocialNetworkPostAttachment $attachment = null) {
		if (func_num_args() != 0)
			$this->_attachments[] = $attachment;

		return $this;
	}

	/**
	 * @param SocialNetworkPostAttachment[] $attachments = []
	 *
	 * @return SocialNetworkPostAttachment[]
	 */
	public function Attachments ($attachments = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($attachments, new SocialNetworkPostAttachment()))
			$this->_attachments = $attachments;

		return $this->_attachments;
	}

	/**
	 * @param string $type::TYPE_URL
	 *
	 * @return SocialNetworkPostAttachment[]
	 */
	public function AttachmentsByType ($type = SocialNetworkPostAttachment::TYPE_URL) {
		$out = array();

		foreach ($this->_attachments as $i => &$attachment)
			if ($attachment->Type() == $type)
				$out[]  =$attachment;

		return $out;
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
	 * @param string[] $categories = []
	 *
	 * @return string[]
	 */
	public function Categories ($categories = []) {
		if (func_num_args() != 0)
			$this->_categories = $categories;

		return $this->_categories;
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