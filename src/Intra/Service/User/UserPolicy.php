<?php
/**
 * Created by PhpStorm.
 * User: ridi
 * Date: 2016-02-18
 * Time: 오후 6:10
 */

namespace Intra\Service\User;

class UserPolicy
{
	public static function is_first_page_replaceable($self)
	{
		if ($self->is_admin || in_array($self->name, ['한진규', '임다영'])) {
			return true;
		}
		return false;
	}

	public static function is_holiday_editable($self)
	{
		if ($self->is_admin || in_array($self->name, ['임다영'])) {
			return true;
		}
		return false;
	}
}