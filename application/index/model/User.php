<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/1/30
 * Time: 13:19
 */

namespace app\index\model;

use think\Model;

class User extends Model{

	protected $pk = 'openid';

	public function IsExistsOpenid($openid){
		if($this->find($openid)){
			return true;
		}else{
			return false;
		}
	}
	public function UpdateInfo(array $userInfo){
		if ($this->IsExistsOpenid($userInfo["openid"])){
			if(isset($userInfo["openid"])) {
				$_user = new self();
				$_user->allowField(true)
				      ->save($userInfo,['openid'=>$userInfo["openid"]]);
			}else{
				exit("Miss openid");
			}
		}else{
			$_user = new self($userInfo);
			$_user->allowField(true)
			      ->save();
		}
	}

	public function GetUserInfo(){

	}

}