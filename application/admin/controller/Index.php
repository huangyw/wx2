<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/2/4
 * Time: 10:58
 */

namespace app\admin\controller;


use app\admin\model\Crowdfunding;
use app\admin\model\CrowdfundingItems;
use app\index\model\User;
use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Session;
use WxJsApi\lib\WxWebUserApi;
use PHPExcel;
use PHPExcel_IOFactory;

class Index extends Controller {

	protected $openid;

	public function __construct( Request $request = null ) {
		parent::__construct( $request );

		$this->openid = Session::get('openid');
		if(!$this->openid){
			$tool     = new WxWebUserApi();
			$userInfo = $tool->GetUserinfo();
			$u        = new User();
			$u->UpdateInfo( $userInfo );
			$this->openid = $userInfo['openid'];
		}
		if(User::get(['openid' => $this->openid, 'role' => '1'])){
			Session::set( 'openid', $this->openid );
		}else{
			return $this->error('您无权登陆系统后台。');
		}
	}

	//合买后台主页
	public function index() {

		$cf = Crowdfunding::all( function ( $query ) {
			$query->where('status <> 0')->limit( 50 )->order( 'cycle_id', 'desc' );
		} );
		$cf_new = Crowdfunding::all( function ( $query ) {
			$query->where('status = 0')->limit( 50 )->order( 'cycle_id', 'desc' );
		} );
		$cycle_ids = array();
		$moneys    = array();
		foreach ( $cf as $key => $u ) {
			array_push( $cycle_ids, $u->cycle_id );
			array_push( $moneys, $u->money );
		}

		return $this->fetch( 'index', [
			'cycle_ids' => $cycle_ids,
			'moneys'    => $moneys,
			'his_cf'    => $cf,
			'new_cf'    => $cf_new
		] );
	}

	//合买后台新增期数页面
	public function add() {

		if ( Request::instance()->isAjax() ) {
			$data = Request::instance()->post();

			if ( Crowdfunding::isExistsCycleId( $data['cycle_id'] ) ) {
				return '1';
			} else {
				Db::startTrans();
				try {
					//更新合买表
					$cf = new Crowdfunding( [
						'cycle_id'   => $data['cycle_id'],
						'money'      => array_sum( $data['moneys'] ),
						'status'     => '0',
						'start_time' => time()
					] );
					$cf->allowField( [ 'cycle_id', 'money','status','start_time' ] )->save( false );

					//更新合买子表
					$cfi  = new CrowdfundingItems();
					$list = array();
					for ( $i = 0; $i < sizeof( $data['reds'] ); $i ++ ) {
						array_push( $list, [
							'cycle_id' => $data['cycle_id'],
							'red'      => $data['reds'][ $i ],
							'blue'     => $data['blues'][ $i ],
							'quantity' => $data['nums'][ $i ],
							'money'    => $data['moneys'][ $i ],
						] );
					}
					$cfi->allowField( [ 'cycle_id', 'red', 'blue', 'quantity', 'money' ] )->saveAll( $list, false );
					Db::commit();

					return '0';
				} catch ( \Exception $e ) {
					Db::rollback();

					return '2';
				}
			}
		} else {
			return $this->fetch( 'add' );
		}
	}

	//查看合买内容
	public function items( $cycle_id ) {

		$cf  = Crowdfunding::get( $cycle_id );
		$cfi = $cf->getItems();

		$reds   = array();
		$blues  = array();
		$nums   = array();
		$moneys = array();
		foreach ( $cfi as $key => $u ) {
			array_push( $reds, $u->red );
			array_push( $blues, $u->blue );
			array_push( $nums, $u->quantity );
			array_push( $moneys, $u->money );
		};

		return $this->fetch( 'items', [
			'cycle_id' => $cycle_id,
			'reds'     => $reds,
			'blues'    => $blues,
			'nums'     => $nums,
			'moneys'   => $moneys
		] );

	}

	//修改合买内容
	public function edit( $cycle_id ) {

		if ( Request::instance()->isAjax() ) {
			$data = Request::instance()->post();
			Db::startTrans();
			try {
				//更新合买表
				$cf = new Crowdfunding;
				$cf->save( [
					'money' => array_sum( $data['moneys'] )
				], [ 'cycle_id' => $data['cycle_id'] ] );

				//更新合买子表(先删除原有的再新增)
				CrowdfundingItems::destroy( [ 'cycle_id' => $cycle_id ] );
				$cfi  = new CrowdfundingItems();
				$list = array();
				for ( $i = 0; $i < sizeof( $data['reds'] ); $i ++ ) {
					array_push( $list, [
						'cycle_id' => $data['cycle_id'],
						'red'      => $data['reds'][ $i ],
						'blue'     => $data['blues'][ $i ],
						'quantity' => $data['nums'][ $i ],
						'money'    => $data['moneys'][ $i ],
					] );
				}
				$cfi->allowField( [ 'cycle_id', 'red', 'blue', 'quantity', 'money' ] )->saveAll( $list, false );
				Db::commit();

				return '0';
			} catch ( \Exception $e ) {
				Db::rollback();

				return '2';
			}
		}

		$cf  = Crowdfunding::get( $cycle_id );
		$cfi = $cf->getItems();

		$reds   = array();
		$blues  = array();
		$nums   = array();
		$moneys = array();
		foreach ( $cfi as $key => $u ) {
			array_push( $reds, $u->red );
			array_push( $blues, $u->blue );
			array_push( $nums, $u->quantity );
			array_push( $moneys, $u->money );
		};

		return $this->fetch( 'edit', [
			'cycle_id' => $cycle_id,
			'reds'     => $reds,
			'blues'    => $blues,
			'nums'     => $nums,
			'moneys'   => $moneys
		] );
	}

	//删除当期合买
	public function delete( $cycle_id ) {
		Db::startTrans();
		try {
			Crowdfunding::destroy( $cycle_id );
			CrowdfundingItems::destroy( [ 'cycle_id' => $cycle_id ] );
			Db::commit();

			return $this->fetch( 'delete', [ 'status' => true ] );
		} catch ( \Exception $e ) {
			Db::rollback();

			return $this->fetch( 'delete', [ 'status' => false ] );
		}
	}

	//查看合买进度
	public function progress( $cycle_id ) {

		$cf = Crowdfunding::get($cycle_id);
		$up = Db::view('Progress', 'openid,nickname,headimgurl,cycle_id,money' )
				->where(['cycle_id' => $cycle_id])
				->select();

		return $this->fetch( 'progress', [
			'cycle_id'      => $cycle_id,
			'user_purchase' => $up,
			'total_money'   => $cf['money']
		] );
	}

	public function test( $cycle_id =null) {
	}
}