<?php
/** @var $this Intra\Core\Control */


$db = \Intra\Service\IntraDb::getGnfDb();

$rooms = $db->sqlDicts('select * from rooms  where is_visible = "1"');
$name = \Intra\Service\User\UserSession::getSelfDto()->name;

return compact('rooms', 'name');
