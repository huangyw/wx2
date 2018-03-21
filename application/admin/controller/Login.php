<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/2/20
 * Time: 22:59
 */

namespace app\admin\controller;


use think\Controller;
use think\Request;
use think\Session;

class Login extends Controller {

	public function index() {

		if($this->request->isPost()) {
			if ( input( 'userName' ) == '44122314' && input( 'pwd' ) == '307572' ) {
				Session::set( 'userName', '44122314' );
				$this->redirect( 'admin/index/index' );
			} else {
				return $this->fetch( 'index', [ 'err_code' => 'FAIL' ] );
			}
		}
		return $this->fetch();
	}
}