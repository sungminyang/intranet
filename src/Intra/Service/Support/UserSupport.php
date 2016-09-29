<?php

namespace Intra\Service\Support;

use Intra\Core\JsonDto;
use Intra\Core\JsonDtoWrapper;
use Intra\Core\MsgException;
use Intra\Service\Support\Column\SupportColumn;
use Intra\Service\Support\Column\SupportColumnCategory;
use Intra\Service\Support\Column\SupportColumnComplete;
use Intra\Service\Support\Column\SupportColumnCompleteDatetime;
use Intra\Service\Support\Column\SupportColumnCompleteUser;
use Intra\Service\Support\Column\SupportColumnDate;
use Intra\Service\Support\Column\SupportColumnTeam;
use Intra\Service\Support\Column\SupportColumnText;
use Intra\Service\Support\Column\SupportDtoFactory;
use Intra\Service\User\UserDto;
use Intra\Service\User\UserPolicy;
use Intra\Service\User\UserSession;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserSupport
{

	/**
	 * @param UserDto    $target_user_dto
	 * @param SupportDto $support_dto
	 *
	 * @return JsonResponse
	 * @throws \Exception
	 */
	public static function add($target_user_dto, $support_dto)
	{
		return JsonDtoWrapper::create(function () use ($target_user_dto, $support_dto) {
			$insert_id = SupportModel::add($support_dto);
			if (!$insert_id) {
				throw new MsgException('자료추가 실패했습니다');
			}

			return new JsonDto('성공했습니다.');
		});
	}

	public static function edit($target, $id, $key, $value)
	{
		$support_dto = SupportDtoFactory::get($target, $id);

		$columns = SupportPolicy::getColumns($target);
		$user = UserSession::getSelfDto();
		if (!(self::isEditable($user, $columns, $key, $support_dto))) {
			return $support_dto->columns[$key];
		}
		SupportModel::edit($target, $id, $key, $value);
		$support_dto = SupportDtoFactory::get($target, $id);
		return $support_dto->columns[$key];
	}

	/**
	 * @param UserDto         $user
	 * @param SupportColumn[] $columns
	 * @param                 $key
	 * @param SupportDto      $support_dto
	 *
	 * @return bool
	 */
	private static function isEditable($user, $columns, $key, $support_dto)
	{
		if (UserPolicy::isSupportAdmin($user)) {
			return true;
		}
		foreach ($columns as $column) {
			if ($column->key == $key) {
				if ($column instanceof SupportColumnCategory ||
					$column instanceof SupportColumnText ||
					$column instanceof SupportColumnDate ||
					$column instanceof SupportColumnTeam
				) {
					if ($support_dto->uid == $user->uid) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public static function del($target, $id)
	{
		return JsonDtoWrapper::create(function () use ($target, $id) {
			$support_dto = SupportDtoFactory::get($target, $id);
			$user = UserSession::getSelfDto();
			self::assertDelete($user, $support_dto);
			$count = SupportModel::del($target, $support_dto->id);
			if (!$count) {
				throw new MsgException('삭제되지 않았습니다.');
			}

			return new JsonDto('삭제되었습니다.');
		});
	}

	private static function assertDelete($user, $support_dto)
	{
		if (UserPolicy::isSupportAdmin($user)) {
			return;
		}
		if ($support_dto->uid == $user->uid) {
			return;
		}
		throw new MsgException('권한이 없습니다.');
	}

	public static function complete($target, $id, $key)
	{
		return JsonDtoWrapper::create(function () use ($target, $id, $key) {
			$user = UserSession::getSelfDto();
			$columns = SupportPolicy::getColumns($target);

			foreach ($columns as $column) {
				if ($column->key == $key) {
					if ($column instanceof SupportColumnComplete) {
						$has_auth = ($column->callback_has_user_auth)($user);
						$is_admin = UserPolicy::isSupportAdmin($user);
						if (!($has_auth || $is_admin)) {
							throw new MsgException('권한이 없습니다.');
						}
					}
					break;
				}
			}
			foreach ($columns as $column) {
				if ($column instanceof SupportColumnComplete) {
					if ($column->key == $key) {
						SupportModel::edit($target, $id, $column->key, 1);
					}
				} elseif ($column instanceof SupportColumnCompleteUser) {
					if ($column->parent_column == $key) {
						SupportModel::edit($target, $id, $column->key, $user->uid);
					}
				} elseif ($column instanceof SupportColumnCompleteDatetime) {
					if ($column->parent_column == $key) {
						SupportModel::edit($target, $id, $column->key, date('Y/m/d H:i:s'));
					}
				}
			}

			return new JsonDto('승인되었습니다.');
		});
	}
}
