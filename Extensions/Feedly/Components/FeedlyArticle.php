<?php
namespace Quark\Extensions\Feedly\Components;

use Quark\QuarkDate;

/**
 * Class FeedlyArticle
 *
 * @package Quark\Extensions\Feedly\Components
 */
class FeedlyArticle {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var QuarkDate $_created
	 */
	private $_created;

	/**
	 * @var QuarkDate $_crawled
	 */
	private $_crawled;

	/**
	 * @var string $_title = ''
	 */
	private $_title = '';

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @var string $_author = ''
	 */
	private $_author = '';

	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_cover = ''
	 */
	private $_cover = '';

	/**
	 * @param string $url = ''
	 * @param string $title = ''
	 * @param string $content = ''
	 */
	public function __construct ($url = '', $title = '', $content = '') {
		$this->URL($url);
		$this->Title($title);
		$this->Content($content);
	}

	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param QuarkDate $created = null
	 *
	 * @return QuarkDate
	 */
	public function &Created (QuarkDate $created = null) {
		if (func_num_args() != 0)
			$this->_created = $created;

		return $this->_created;
	}

	/**
	 * @param QuarkDate $crawled = null
	 *
	 * @return QuarkDate
	 */
	public function &Crawled (QuarkDate $crawled = null) {
		if (func_num_args() != 0)
			$this->_crawled = $crawled;

		return $this->_crawled;
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
	 * @param string $cover = ''
	 *
	 * @return string
	 */
	public function Cover ($cover = '') {
		if (func_num_args() != 0)
			$this->_cover = $cover;

		return $this->_cover;
	}
}