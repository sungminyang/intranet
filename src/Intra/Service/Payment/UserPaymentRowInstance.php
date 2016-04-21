<?php
/**
 * Created by PhpStorm.
 * User: ridi
 * Date: 2016-04-12
 * Time: 오후 4:25
 */

namespace Intra\Service\Payment;


use Intra\Core\MsgException;
use Intra\Model\PaymentAcceptModel;
use Intra\Model\PaymentModel;
use Intra\Service\User\UserService;
use Intra\Service\User\UserSession;

class UserPaymentRowInstance
{
	private $user_payment_model;
	private $payment_id;

	public function __construct($payment_id)
	{
		$this->user_payment_model = new PaymentModel();
		$this->payment_id = $payment_id;
	}

	public function edit($key, $new_value)
	{
		$payment_dto = PaymentDto::importFromDatabaseRow(
			$this->user_payment_model->getPaymentWithoutUid($this->payment_id)
		);
		$old_value = $payment_dto->$key;

		if (!$this->assertEdit($key, $old_value, $new_value, $payment_dto)) {
			return $old_value;
		}
		$this->user_payment_model->update($this->payment_id, $key, $new_value);

		$updated_payment_dto = PaymentDto::importFromDatabaseRow(
			$this->user_payment_model->getPaymentWithoutUid($this->payment_id)
		);
		$updated_value = $updated_payment_dto->$key;;

		if ($key == 'status') {
			if ($updated_value == '결제 완료') {
				$type = '결제완료';
				UserPaymentMailService::sendMail($type, $this->payment_id);
			}
		} elseif ($key == 'price') {
			return number_format($updated_value) . ' 원';
		} elseif ($key == 'manager_uid') {
			$user_name = UserService::getNameByUidSafe($updated_value);
			if ($user_name === null) {
				return 'error';
			}
			return $user_name;
		}
		return $updated_value;
	}

	/**
	 * @param $key
	 * @param $old_value
	 * @param $new_value
	 * @param $payment_dto PaymentDto
	 * @return bool
	 * @throws MsgException
	 */
	private function assertEdit($key, $old_value, $new_value, $payment_dto)
	{
		if ($key == 'date') {
			//날짜를 변경할때 다른 월로는 변경불가
			$month_new = date('Ym', strtotime($new_value));
			$month_old = date('Ym', strtotime($old_value));
			if ($month_new != $month_old) {
				return false;
			}
		}
		if ($key == 'status') {
			if (!$payment_dto->is_co_accepted || !$payment_dto->is_manager_accepted) {
				throw new MsgException("아직 승인되지 않았습니다");
			}
		}
		if (!UserSession::getSelfDto()->is_admin) {
			return false;
		}
		return true;
	}

	public function del()
	{
		$res = $this->user_payment_model->del($this->payment_id);
		if ($res) {
			return 1;
		}
		return '삭제가 실패했습니다!';
	}

	public function acceptManageer()
	{
		$payment_dto = PaymentDto::importFromDatabaseRow(
			$this->user_payment_model->getPaymentWithoutUid($this->payment_id)
		);
		$self = UserSession::getSelfDto();
		if ($payment_dto->manager_uid != $self->uid) {
			throw new MsgException("담당 승인자가 아닙니다.");
		}
		return $this->accept('manager', $self->uid);
	}

	public function acceptCO()
	{
		$self = UserSession::getSelfDto();
		if (!$self->is_admin) {
			throw new MsgException("담당 승인자가 아닙니다.");
		}
		return $this->accept('co', $self->uid);
	}

	private function accept($user_type, $uid)
	{
		$payment_accept_dto = PaymentAcceptDto::importFromAddRequest($this->payment_id, $uid, $user_type);
		PaymentAcceptModel::insert($payment_accept_dto);
		return 1;
	}
}