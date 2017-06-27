<?php
namespace Quark\Extensions\Quark\Archives;

use Quark\IQuarkArchive;

use Quark\QuarkArchiveItem;
use Quark\QuarkDate;

/**
 * Class ARArchive
 *
 * http://citforum.ru/operating_systems/manpages/AR.4.shtml
 * https://ru.wikipedia.org/wiki/Deb_(%D1%84%D0%BE%D1%80%D0%BC%D0%B0%D1%82_%D1%84%D0%B0%D0%B9%D0%BB%D0%BE%D0%B2)
 *
 * @package Quark\Extensions\Quark\Archives
 */
class ARArchive implements IQuarkArchive {
	const TYPE = "!<arch>\n";
	const HEADER = "`\n";
	const BLOCK = 60;
	
	/**
	 * @param QuarkArchiveItem[] $items
	 *
	 * @return string
	 */
	public function Pack ($items) {
		$out = self::TYPE;
		
		foreach ($items as $item)
			$out .= $this->PackItem($item);
		
		return $out;
	}
	
	/**
	 * @param QuarkArchiveItem $item
	 * @param int $user = 0
	 * @param int $group = 0
	 *
	 * @return string
	 */
	public function PackItem (QuarkArchiveItem $item, $user = 0, $group = 0) {
		return pack(
				'a16a12a6a6a8a10',
				sprintf('%-16s', $item->location),
				sprintf('%-12s', $item->DateModified()->Timestamp()),
				sprintf('%-6s', $user),
				sprintf('%-6s', $group),
				sprintf('%-8s', decoct($item->Permissions())),
				sprintf('%-10s', $item->size)
			)
			. self::HEADER
			. $item->Content();
	}
	
	/**
	 * @param string $data
	 *
	 * @return QuarkArchiveItem[]
	 */
	public function Unpack ($data) {
		$data = substr($data, strlen(self::TYPE));
		
		$next = 0;
		$read = true;
		$orig = strlen($data);
		$out = array();
		
		while ($read) {
			$item = $this->UnpackItem($data, $next);
			$next = $item->Next();
			$read = $item->Next() < $orig;
			$out[] = $item;
		}
		
		return $out;
	}
	
	/**
	 * @param string $data = ''
	 * @param int $start = 0
	 *
	 * @return QuarkArchiveItem
	 */
	public function UnpackItem ($data = '', $start = 0) {
		$header = substr($data, $start, self::BLOCK);
		$meta = unpack('a16name/a12date/a6uid/a6gid/a8mode/a10size', $header);
		
		$item = new QuarkArchiveItem(
			trim($meta['name']),
			'',
			QuarkDate::FromTimestamp((int)$meta['date']),
			(int)$meta['size']
		);
		
		$item->Content(substr($data, $start + self::BLOCK, $item->size));
		$item->Next($start + self::BLOCK + $item->size);
		
		return $item;
	}
}