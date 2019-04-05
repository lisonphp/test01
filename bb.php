<?php 
// 123
class IndexAction extends Action {
    
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('index','widget_edit','widget_delete','widget_add','calendar','sortcharts','getcustomercompany','getorigin','urldecode')
		);
		B('Authenticate', $action);
	}

	public function asd(){
		echo 213;exit;
		echo '第2次git';exit;
	}

	//接收省份和关注品牌，返回电销公司
	function getcustomercompany(){
		if($this->isAjax()){			
			$province = trim($_POST['province']);
			$product_id = intval($_POST['product_id']);	
			$temp = M('product')->where('product_id = %d',$product_id)->getField('customer_company');
			if($temp){
				$temp2 = M('area')->where('id = %d',$temp)->getField('company');
				$city = unserialize($temp2);
				sort($city);
				foreach ($city as $key => $value) {
					if(in_array($province,$value)){
						$res = $value['id'];
					}
				}				
			}else{
				$res = '品牌不存在！';
			}

			//return $res;
			echo $res;
		}
	}
	//接收咨询页面，返回编码后的咨询
	function urldecode(){
		if($this->isAjax()){
			$url = urldecode($_POST['url']);	
			/*$arr = parse_url($url);
			$arr_query = convertUrlQuery($arr['query']);
			$product_id = intval($arr_query['utm_brand']);
			$origin_id = intval($arr_query['utm_source']);
			if($product_id){
				$temp = M('product')->where('product_id = %d',$product_id)->find();	
				$origins = explode(',', $temp['point_origin']);
				if($temp['product_id'] != $temp['point_id'] && (in_array($origin_id, $origins) || in_array('999999', $origins))){
					$url = str_ireplace('utm_brand='.$product_id,'utm_brand='.$temp['point_id'],$url);
				}
			}*/
			echo $url;
		}
	}
	//接收媒介来源SEM简称，返回ID和名称
	function getorigin(){
		if($this->isAjax()){
			$jiancheng = $_POST['jiancheng'];			
			$origin = M('origin');
			$temp = $origin->where('name = %s',$pid)->getField('customer_company');
			$city = unserialize($temp);
			sort($city);//return $city;//return $pro->getlastsql();
			foreach ($city as $key => $value) {
				if(in_array($province,$value)){
					$res = $value['id'];
				}
			}
			//return $res;
			echo $res;
		}
	}
	public function index(){
		if(session('user_id')){
			//每次登录后清空参数，避免下次直接可以登录
			$loginopenid = M('LoginOpenid');
			$loginopenid->where(['user_id'=>session('user_id')])->save(['issuccess'=>0,'isupdate'=>0,'islostpass'=>0]);
		}
		$ip = get_client_ip();
		//window和linux的lanbox系统记录值，不能同时登录两个系统的功能  2019-03-22暂停
		// M('aopenvpn')->where(array('uid'=>session('user_id')))->save(array('type'=>2,'loginstatus'=>1,'ip'=>$ip,'addtime'=>date("Y-m-d H:i"),'username'=>session('name')));

		$user = M('User');
		$m_announcement = M('announcement');
		$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);		
		foreach($widget['sort'] as $k => $v){
			$res[] = $widget['dashboard'][$v];
		}
		
		$this->widget = $res;
		if (!F('smtp')) {
			alert('info', L('NOT_CONFIGURED_SMTP_INFORMATION_CLICK_HERE_TO_SET',array(U('setting/smtp'))));
		}
		if (!F('defaultinfo')) {
			alert('info', L('SYSTEM_INFORMATION_NOT_CONFIGURED_BY_DEFAULT_CLICK_HERE_TO_SET',array(U('setting/defaultinfo'))));
		}
		$where['department'] = array('like', '%('.session('department_id').')%');
		$where['status'] = array('eq', 1);
		$this->announcement_list = $m_announcement->where($where)->order('order_id')->select();
		$this->alert = parseAlert();
		$this->display();
	}
	
	public function widget_edit(){
		$user = M('User');
		$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widgets = unserialize($dashboard);
		if(isset($_GET['id']) && $_GET['id']!=''){
			/**
			 * 所有的小部件
			 * Function : 判断模块下的某个操作是否有权限
			 * @action  : 默认使用index操作来判断权限
			 */
			$widget_module = array(
				array('module'=>'customer','action'=>'index','tag'=>'Salesfunnel','name'=>'销售漏斗'),
				array('module'=>'customer','action'=>'index','tag'=>'Customerorigin','name'=>'客户来源'),
				array('module'=>'log','action'=>'index','tag'=>'Notepad','name'=>'便笺'),
				array('module'=>'finance','action'=>'index','tag'=>'Receivemonthly','name'=>'月度财务'),
				array('module'=>'finance','action'=>'index','tag'=>'Receiveyearcomparison','name'=>'财务年度对比')
			);
			//如果没有权限，从数组中去除
			foreach($widget_module as $k=>$v){
				if($v['module'] == 'log') continue;//默认便笺所有人都有权限
				if(!vali_permission($v['module'], $v['action'])){
					unset($widget_module[$k]);
				}
			}
			
			$this->widget_module = $widget_module;
			$this->edit_demo = $widgets['dashboard'][$_GET['id']];
			$this->display();
		} elseif(isset($_POST['widget_id']) && $_POST['widget_id']!='') {
			$title = $_POST['title']!='' && isset($_POST['title']) ? $_POST['title'] : '未定义组件';	
			$widgets['dashboard'][$_POST['widget_id']]['title'] = $title;
			$widgets['dashboard'][$_POST['widget_id']]['widget'] = $_POST['widget'];
			$newdashboard['dashboard']['dashboard'] = serialize($widgets);
			
			if($user->where('user_id = %d', session('user_id'))->setField('dashboard', serialize($widgets))){
				alert('success', L('MODIFY_THE_COMPONENT_INFORMATION_SUCCESSFULLY',array($_POST['widget'])), U('index/index'));
			}else{
				alert('error', L('MODIFY_THE_COMPONENT_INFORMATION_NO_CHANGE',array($_POST[widget])), U('index/index'));
			}
		}
	}
	
	public function widget_delete(){
		if(isset($_GET['id']) && $_GET['id']!=''){
			$user = M('User');
			$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
			$widget = unserialize($dashboard);
			unset($widget['dashboard'][$_GET['id']]);
			unset($widget['sort'][array_search($_GET['id'], $widget['sort'])]);
			if($user->where('user_id = %d', session('user_id'))->setField('dashboard', serialize($widget))){
				alert('success', '删除组件成功！', U('index/index'));
			}else{
				alert('error', '删除组件失败！',$_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	//serialize  unserialize
	public function widget_add(){
		if($this->isPost()){
			if($_POST['widget']){
				$user = M('User');
				$title = $_POST['title']!='' && isset($_POST['title']) ? $_POST['title'] : '未命名组件';
				$dashboard = $user->where('user_id = %d', session('user_id'))->getField('dashboard');
				$widget = unserialize($dashboard);
				if(!is_array($widget)){
					$widget = array();
				}
				$max_id = 0;
				foreach($widget['dashboard'] as $v){
					if($v['id'] > $max_id) $max_id = $v['id'];
				}
				
				$widget['dashboard'][$max_id+1] = array('widget'=>$_POST['widget'], 'level'=>$_POST['level'], 'title'=>$title, 'id'=>$max_id+1);
				
				$widget['sort'][] = $max_id+1;

				$newdashboard['dashboard'] = serialize($widget);
				if($user->where('user_id = %d', session('user_id'))->save($newdashboard)){
					alert('success', '添加组件成功', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '添加组件失败，请填写组件名!', $_SERVER['HTTP_REFERER']);
			}
		}else{
			/**
			 * 所有的小部件
			 * Function : 判断模块下的某个操作是否有权限
			 * @action  : 默认使用index操作来判断权限
			 */
			$widget_module = array(
				array('module'=>'customer','action'=>'index','tag'=>'Salesfunnel','name'=>'销售漏斗'),
				array('module'=>'customer','action'=>'index','tag'=>'Customerorigin','name'=>'客户来源'),
				array('module'=>'log','action'=>'index','tag'=>'Notepad','name'=>'便笺'),
				array('module'=>'finance','action'=>'index','tag'=>'Receivemonthly','name'=>'月度财务'),
				array('module'=>'finance','action'=>'index','tag'=>'Receiveyearcomparison','name'=>'财务年度对比')
			);
			//如果没有权限，从数组中去除
			foreach($widget_module as $k=>$v){
				if($v['module'] == 'log') continue;//默认便笺所有人都有权限
				if(!vali_permission($v['module'], $v['action'])){
					unset($widget_module[$k]);
				}
			}
			$this->widget_module = $widget_module;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	 * @author 		: myron
	 * @function	: 首页日历获取任务和日程数据
	 * @return		: 任务和日程
	 **/
	public function calendar(){
		$role_id = session('role_id');
		$month_start = strtotime(date('Y-m-1',time()));	//本月开始时间
		$month_end = $month_start+(30*86400)-1;			//本月开始时间
		$date_begin = $month_start - 86400*6;			//本月1号6天前(日历上最多显示1号前六天)
		$date_end = $month_end + 86400*14;				//本月最后一天14天后(日历上最多显示月末14天后)

		//任务
		$taskData = array();
		$m_task = M('task');
		$where['owner_role_id']  = array('like', "%,$role_id,%");
		$where['about_roles']  = array('like',"%,$role_id,%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$map['create_date'] = array('egt', $date_begin);
		//$map['due_date'] = array('elt', $date_end);
		$map['is_deleted'] = array('eq', 0);
		$map['status'] = array('neq', '完成');
		$map['isclose'] = array('eq', 0);

		$task = $m_task->field('task_id, subject, create_date, due_date, "task" as type')->where($map)->order('create_date asc')->select();
		foreach($task as $k=>$v){
			$j = 0;
			for($i=$date_begin;$i<=$date_end;$i+=86400){
				$j=$i+86400;
				//每一天
				if($v['create_date'] < $j && $v['due_date'] >= $i){
					$url = U('task/index','field=subject&condition=is&act=search&search='.urlencode($v['subject']));
					$taskData[] = array(
						'title'=> '<a href="'.$url.'" target="_blank">'.$v['subject'].'</a>',
						'description'=>'',
						'datetime'=>$i,
						'type'=>'task'
					);
				}
			}
		}
		
		//日程
		$eventData = array(); 
		$m_event = M('event');
		$condition['owner_role_id']  = array('eq', $role_id);
		$condition['start_date'] = array('egt', $date_begin);
		// $condition['end_date'] = array('elt', $date_end);
		$condition['is_deleted'] = array('eq', 0);
		$condition['isclose'] = array('eq', 0);
		
		$event = $m_event->field('event_id,subject, start_date, end_date, "event" as type')->where($condition)->order('create_date desc')->select();
		foreach($event as $k=>$v){
			$j = 0;
			for($i=$date_begin;$i<=$date_end;$i+=86400){
				$j=$i+86400;
				//每一天
				if($v['start_date'] < $j && $v['end_date'] >= $i){
					$url = U('event/index','field=subject&condition=is&act=search&search='.urlencode($v['subject']));
					$eventData[] = array(
						'title'=>'<a href="'.$url.'" target="_blank">'.$v['subject'].'</a>',
						'description'=>'',
						'datetime'=>$i,
						'type'=>'event'
					);
				}
			}
		}

		$calendarData = array_merge($taskData, $eventData);
		$this->ajaxReturn($calendarData,'success',1);
	}
	
	//首页图表排序
	
	public function sortCharts(){
		$chart_arr = explode(',',$_POST['chart_arr']);	//用户拖动后的顺序
		$m_user = M('user');
		$dashboardSer = $m_user->where('role_id = %d', session('role_id'))->getField('dashboard');	//拖动前数据库的顺序
		$dashboard = unserialize($dashboardSer);
		$dashboard['sort'] = $chart_arr;
		$m_user->where('role_id = %d', session('role_id'))->setField('dashboard',serialize($dashboard));
	}
}