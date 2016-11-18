<?php

/** @var $this Intra\Core\Control */

$db = \Intra\Service\IntraDb::getGnfDb();

$user = \Intra\Service\User\UserSession::getSelfDto();
$request = $this->getRequest();

$uid = $user->uid;
$id = $request->get('id');
$desc = $request->get('desc');
$from = $request->get('from');
$to = $request->get('to');
$room_id = $request->get('room_id');

if ($user->is_admin) {
	$where = compact('id');
} else {
	$where = compact('id', 'uid');
}
$update = compact('desc', 'from', 'to', 'room_id');
if ($db->sqlUpdate('room_events', $update, $where)) {
	return 1;
}

return '예약 변경이 실패했습니다. 개발팀에 문의주세요';