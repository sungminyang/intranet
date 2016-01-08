<?php
/**
 * Created by PhpStorm.
 * User: KHS
 * Date: 2016. 1. 4.
 * Time: 오후 12:04
 */

namespace Intra\Service\Press;

use Intra\Core\JsonDto;
use Intra\Service\IntraDb;
use Intra\Service\User;
use Intra\Service\UserSession;

class Press
{
	private $user;

	/**
	 * @param $user User
	 */
	public function __construct($user)
	{
		$this->user = $user;
	}

	public function index()
	{
		$db = IntraDb::getGnfDb();

		$return = [
			'user' => $this->user,
			'press' => $db->sqlDicts('select * from press order by date desc'),
			'manager' => UserSession::isPressManager()
		];

		return $return;
	}

	public function add($date, $media, $title, $link_url, $note)
	{
		$db = IntraDb::getGnfDb();

		$row = [
			'date' => $date,
			'media' => $media,
			'title' => $title,
			'link_url' => $link_url,
			'note' => $note
		];

		try {
			$db->sqlInsert('press', $row);
		} catch (\Exception $e) {
			return '자료를 추가할 수 없습니다. 다시 확인해 주세요';
		}

		return true;
	}

	public function del($press_id)
	{
		$db = IntraDb::getGnfDb();

		$where = [
			'id' => $press_id
		];

		try {
			$db->sqlDelete('press', $where);
		} catch (\Exception $e) {
			return '삭제가 실패했습니다!';
		}

		return true;
	}

	public function edit($press_id, $key, $value)
	{
		$db = IntraDb::getGnfDb();

		$update = [$key => $value];
		$where = [
			'id' => $press_id
		];

		$db->sqlUpdate('press', $update, $where);
		$new_value = $db->sqlData('select ? from press where ?', sqlColumn($key), sqlWhere($where));

		return $new_value;
	}

	public function getListByJson()
	{
		$press = $this->index();

		$json_dto = new JsonDto();
		$json_dto->data = $press['press'];

		return json_encode(
			(array)$json_dto
		);
	}
}