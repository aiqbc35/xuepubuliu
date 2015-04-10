<?php 

class Upload{

	private $config = array(
			'mimes' 	=> 	array(), //允许上传的文件类型
			'maxSize'	=>	0, //允许文件上传大小 (0--不做限制)
			'exts'		=>	array(),	//允许文件上传的后缀
			'autoSub'	=>	true,	//允许子目录保存
			'subName'	=>	array('data','y-m-d'),//子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
			'rootPath'	=>	'./upload/',	//上传根目录
			'savePath'	=>	'',	//保存路劲
			'saveName'	=>	array('uniqid',''),	//上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
			'saveExt'	=>	'', //文件保存后缀，为空则使用原来后缀
		);

	/**
	 * [$error 上传错误信息]
	 * @var string
	 */
	private $error = '';	//上传错误信息

	 /**
     * 构造方法，用于构造上传实例
     * @param array  $config 配置
     */
	public function __construct ( $config = array() ) {
			//获取配置项
		$this->config = array_merge($this->config,$config);
		/*调整配置项*/
		if (!empty($this->config['mimes'])) {
			//将字符串转成数组
			if (is_string($this->mimes)) {
				$this->config['mimes'] = explode(',', $this->mimes);
			}
			//使用strtolower内置函数转换成相同格式
			$this->config['mimes'] = array_map('strtolower', $this->mimes);

		}

		if (!empty($this->config['exts'])) {
			
			if (is_string($this->exts)) {
				$this->config['exts'] = explode(',', $this->exts);
			}
			$this->config['exts'] = array('strtolower',$this->exts);
		}
		
	}
	/**
	 * [__get $this->config获取配置]
	 * @param  [string] $name [配置名称]
	 * @return [type]       [配置值]
	 */
	public function __get ($name) {
		return $this->config[$name];
	}
	/**
	 * [__set $this->config 设置配置项]
	 * @param [type] $name  [属性]
	 * @param [type] $value [值]
	 */
	public function __set ($name,$value) {
		if (isset($this->config[$name])) {
			$this->config[$name] = $value;
		}
	}
	/**
	 * [__isset 检查特定属性]
	 * @param  [type]  $name [description]
	 * @return boolean       [description]
	 */
	public function __isset ( $name ) {
		return isset($this->config[$name]);
	}
	/**
	 * [getError 获取上传错误信息]
	 * @return [string] [错误信息]
	 */
	public function getError () {
		return $this->error;
	}

	public function upload ( $files = '' ) {
		//判断如果没有传入文件 则自动获取$_FILES
		if ($files === '') {
			$files = $_FILES;
		}
		//判断如果文件为空则抛出错误信息
		if (empty($files)) {
			$this->error = '没有上传文件';
			return false;
		}
		//检测根目录是否存在
		if (!$this->checkRootPath()) {
			$this->error = '上传目录不存在，请手动创建'.$this->rootPath;
			return false;
		}
		//检测上传目录
		if (!$this->checkSavePath()) {
			return false;
		}
		//逐个检查并上传文件
		$info = array();
		//finfo_open内置函数  获取文件类型
		//function_exists内置函数 检查指定的函数是否已经定义
		if (function_exists('finfo_open')) {
			//获取文件扩展名
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
		}
		//对上传文件数组信息重组
		$files = $this->dealFiles($files);

		foreach ($files as $key => $value) {
			//去除名称里面的HTML以及PHP标签
			$value['name'] = strip_tags($value['name']);

			if(!isset($value['key'])) $value['key'] = $key;
			//通过扩展获取文件类型，可解决FLASH上传$FILES数组返回文件类型错误的问题
			//finfo_file内置函数  返回一个文件的信息
			if (isset($finfo)) {
				$value['type'] = finfo_file($finfo, $value['tmp_name']);
			}
			//pathinfo 函数以数组的形式返回文件路径的信息。
			//pathinfo 有三个属性 
			//1、PATHINFO_DIRNAME - 只返回 dirname。
			//2、PATHINFO_BASENAME - 只返回 basename，
			//3、PATHINFO_EXTENSION - 只返回 extension
			//获取上传文件后缀，允许上传无后缀文件
			$value['ext']    =   pathinfo($value['name'], PATHINFO_EXTENSION);
			//文件上传检测
			if (!$this->check($value)) {
				continue;
			}
			//生成保存文件名
			$savename = $this->getSaveName($value);
			if ($savename == false) {
				continue;
			}else{
				$value['savename'] = $savename;
			}
			//检测并创建子目录
			$subpath = $this->getSubPath($value['name']);
			if ($subpath === false) {
				continue;
			}else{
				$value['savepath'] = $this->savepath.$subpath;
			}

			//检查图像文件
			$ext = strtolower($value['ext']);
			if (in_array($ext, array('gif','jpg','jpeg','bmp','png','swf'))) {
				//getimagesize 获取图片的长宽
				$imginfo = getimagesize($value['tmp_name']);

				if (empty($imginfo) && ($ext == 'gif' && empty($imginfo['bits']))) {
					$this->error = '非法图像文件';
					continue;
				}
			}

			if ($this->save($value,$this->replace)) {
				unset($value['error'],$value['tmp_name']);
				$info[$key] = $value;
			}

			if (isset($finfo)) {
				finfo_close($finfo);
			}

			return empty($info) ? false : $info;


		}





	}
	/**
	 * [save 保存指定文件]
	 * @param  [array] $file    [保存文件信息]
	 * @param  [boolean] $replace [同名文件是否覆盖，true 覆盖，false 不覆盖]
	 * @return [boolean]          [true 保存成功，false 保存失败]
	 */
	private function save ($file,$replace) {
		$filename = $this->rootPath . $file['savepath'] . $file['savename'];
		//不覆盖同名文件
		if (!$replace && is_file($filename)) {
			$this->error = '存在同名文件'.$file['savename'];
			return false;
		}

		if (!move_uploaded_file($file['tmp_name'], $filename)) {
			$this->error = '文件上传保存错误!';
			return false;
		}
		return true;
	}

	/**
	 * [getSubPath 获取子目录的名称]
	 * @param  [array] $filename [上传的文件信息]
	 * @return [type]           [description]
	 */
	private function getSubPath ($filename) {
		$subpath = '';
		$rule = $this->subName;

		if ($this->autoSub && !empty($rule)) {
			$subpath = $this->getName($rule,$filename).'/';

			if (!empty($subpath) && !$this->mkdir($this->savePath.$subpath)) {
				return false;
			}

		}

		return $subpath;
	}


	/**
	 * [getSaveName 根据文件名规则获取保存文件名]
	 * @param  [string] $file [文件信息]
	 * @return [string]       [文件名]
	 */
	private function getSaveName ($file) {
		$rule = $this->saveName;

		if (empty($rule)) {
			//保持文件名不变
			//解决pathinfo中文文件名BUG
			$filename = substr(pathinfo("_{$file['name']}",PATHINFO_FILENAME), 1);
			$savename = $filename;
		}else{
			$savename = $this->getName($rule,$file['name']);
			if (empty($savename)) {
				$this->error = '文件命名规则错误！';
				return false;
			}
		}
		//支持强制更改后缀
		$ext = empty($this->config['saveExt']) ? $file['ext'] : $this->saveExt;
		return $savename.'.'.$ext;

	}
	/**
	 * [getName 根据指定的规则获取文件或目录名称]
	 * @param  [array] $rule     [规则]
	 * @param  [string] $filename [原文件名]
	 * @return [string]           [新文件名]
	 */
	private function getName ($rule,$filename) {

		$name = '';

		if (is_array($rule)) { //数组规则
			$func = $rule[0];
			$param = (array) $rule[1];
			//自 PHP 5 起，可以很容易地通过在 $value 之前加上 & 来修改数组的元素。此方法将以引用赋值而不是拷贝一个值。 
			foreach ($param as &$value) {
				//在$value中查找__FILE__并替换成$filename
				$value = str_replace('__FILE__', $filename, $value);
			}
			//call_user_func_array 回调函数 使用$fuc 对$param 进行处理
			$name = call_user_func_array($func,$param);
		}elseif (is_string($rule)) {//字符串规则
			//function_exists 在已经定义的函数列表（包括系统自带的函数和用户自定义的函数）中查找$rule，
			//如果给定的函数已经被定义就返回 TRUE
			if (function_exists($rule)) {
				//回调
				$name = call_user_func($rule);
			}else{
				$name = $rule;
			}
		}

		return $name;
	}
	/**
	 * [checkRootPath 检测上传目录是否存在]
	 * @return [boolean] [检测结果，true-通过，false-失败]
	 */
	private function checkRootPath ( ) {
		//判断是否为目录并且是否可读写
		if (!(is_dir($this->rootPath) && is_writable($this->rootPath))) {			
			return false;
		}		
		return true;	
	}
	/**
	 * [checkSavePath 检测上传目录]
	 * @return [boolean] [检测结果，true-通过，false-失败]
	 */
	private function checkSavePath () {
		//检测并创建目录
		if (!$this->mkdir()) {
			return false;
		}else{
				//检测目录是否可写
			if (!is_writable($this->rootPath.$this->savePath)) {
				$this->error = '上传目录{$this->savePath}不可写!';
				return false;
			}else{
				return true;
			}

		}

	}
	/**
	 * [dealFiles 转换上传文件数组为正确的变量]
	 * @param  [array] $files [上传的文件]
	 * @return [array]        [description]
	 */
	private function dealFiles ($files) {
			$fileArr = array();
			$n = 0;

			foreach ($files as $key => $value) {
					//判断是否为多个文件上传
					if (is_array($value['name'])) {
						//获取文件名
						$keys = array_keys($value);
						//获取总共有多少个文件
						$count = count($value['name']);

						for ($i=0; $i < $count; $i++) { 
							$fileArr[$n]['key'] = $key;
							foreach ($keys as $_key) {
								$fileArr[$n][$_key] = $value[$_key][$i];
							}
							$n++;
						}


					}else{
						$fileArr = $files;
						break;
					}

			}

			return $fileArr;
	}
	/**
	 * [check 检查上传的文件]
	 * @param  [array] $file [数组]
	 * @return [type]       [description]
	 */
	private function check ($file){
			//文件上传失败，获取失败错误信息
			if ($file['error']) {
				$this->error($file['error']);
				return false;
			}
			//无效上传
			if (empty($file['name'])) {
				$this->error = '未知上传错误';
			}
			//检查是否合法上传文件
			//is_uploaded_file 判断文件是否通过http post 传递 如果通过则返回true
			if (!is_uploaded_file($file['tmp_name'])) {
				$this->error = '非法上传文件';
				return false;
			}
			//检查文件大小
			if (!$this->checkSize($file['size'])) {
				$this->error = '上传文件大小不符';
				return false;
			}
			//检查文件Mime类型
			//TODO:FLASH上传的文件获取到的mime类型都为application/octet-stream
			if (!$this->checkMime($file['type'])) {
				$this->error = '上传文件MIME类型不允许！';
				return false;
			}
			//检查文件后缀
			if (!$this->checkExt($file['ext'])) {
				$this->error = '不是被允许的上传文件后缀';
				return false;
			}


			return true;

	}
	/**
	 * [error 获取错误代码信息]
	 * @param  [string] $errorNo [错误代码]
	 * @return [type]          [description]
	 */
	private function error ($errorNo) {
		switch ($errorNo) {
			case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
                break;
            case 3:
                $this->error = '文件只有部分被上传！';
                break;
            case 4:
                $this->error = '没有文件被上传！';
                break;
            case 6:
                $this->error = '找不到临时文件夹！';
                break;
            case 7:
                $this->error = '文件写入失败！';
                break;
            default:
                $this->error = '未知上传错误！';
		}
	}

	/**
	 * [checkExt 检查上传的文件是否合法]
	 * @param  [string] $ext [后缀]
	 * @return [type]      [description]
	 */
	private function checkExt ($ext) {
		return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->exts);
	}

	/**
	 * [checkMime 检查MIME类型是否合法]
	 * @param  [string] $mime [数据]
	 * @return [type]       [description]
	 */
	private function checkMime ( $mime ) {
		//如果配置项为空则直接返回true
		//否则 首先将文件类型用strtolower转换成小写 再使用in_array 去配置项查找,如果有则返回true
		return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->mimes);
	}

	/**
	 * [checkSize 检查文件大小是否合法]
	 * @param  [integer] $size [数据]
	 * @return [type]       [description]
	 */
	private function checkSize ($size) {
			//当$size 小于设定值时 return 1;
			return !($size > $this->maxSize) || (0 == $this->maxSize);

	}

	/**
	 * [mkdir 创建目录]
	 * @return [boolean] [创建状态，true-成功，false-失败]
	 */
	private function mkdir( ) {
		//组合新路径
		$dir = $this->rootPath.$this->savePath;

		if (is_dir($dir)) {
			return true;
		}

		if (mkdir($dir,0777,true)) {
			return true;
		}else{
			$this->error = '目录{$this->savePath}创建失败！';
			return false;
		}
	}


}


 ?>