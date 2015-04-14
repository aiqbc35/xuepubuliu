<?php 

class UplocadAll{

	private $config = array(
			'maxSize' 	=> 	0, 			//上传的文件大小
			'exts' 		=> 	array(), 	//文件后缀
			'types'		=>	array(),		//文件类型
			'savePath'	=>	'',				//文件保存路径
			'saveRule'	=>	'uniqid',		//文件命名规则
		);

	private $error = '';	//错误信息
	private $uploadFileInfo;

	public function __get ($name) {
		return $this->config[$name];
	}

	public function __set ($name,$value) {

			if (isset($this->config[$name])) {
				$this->config[$name] = $value;
			}
		
	}

	public function __isset ( $name ) {
		return isset($this->config[$name]);
	}


	public function __construct ($config = array()) {
			if (is_array($config)) {
				$this->config = array_merge($this->config,$config);
			}
	}
	/**
	 * [getError 抛出错误信息]
	 * @return [type] [description]
	 */
	public function getError () {
		return $this->error;
	}

	public function upload ($savePath = '') {
		if (empty($savePath))
			$savePath = $this->savePath;
		//对上传目录进行处理
		if (!is_dir($savePath)) {
			if (!mkdir($savePath,0777,true)) {
				$this->error = '上传目录'.$savePath.'不存在';
				return false;
			}
		}elseif (!is_writeable($savePath)) {
			$this->error = '上传目录'.$savePath.'不可写';
			return false;
		}

		$file = $this->dealFiles($_FILES);
		$info = array();
		$upstatus = false;
		foreach ($file as $key => $value) {
			if (!empty($value['name'])) {
				if(!isset($value['key'])) $value['key'] = $key;
				$value['ext'] = $this->getExt($value['name']);
				$value['savePath'] = $savePath;
				$value['saveName'] = $this->getSaveName($value);

				if (!$this->check($value)) {
					return false;
				}

				if(!$this->save($value)) return false;

				unset($value['tmp_name'],$value['error']);
				$info[] = $value;
				$upstatus = true;
			}
		}

		if ($upstatus) {
			$this->uploadFileInfo = $info;
			return true;
		}else{
			$this->error = '没有选择上传文件';
			return false;
		}
		
		
	}
	/**
	 * [getUploadIndo 抛出上传文件信息]
	 * @return [array] [文件信息]
	 */
	public function getUploadInfo () {
		return $this->uploadFileInfo;
	}

	/**
	 * [getSaveName 根据文件命名规则修改文件名]
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	private function getSaveName ($file) {
		$rule = $this->saveRule;
		if (empty($rule)) {
			$saveName = $file['name'];
		}else{
			if (function_exists($rule)) {
				$saveName = $rule().'.'.$file['ext'];
			}else{
				$saveName = $rule.'.'.$file['extension'];
			}
		}

		return $saveName;
	}

	/**
	 * [save 文件保存]
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	private function save ($file) {
		$filename = $file['savePath'].$file['saveName'];
		if (in_array(strtolower($file['ext']), array('gif','jpg','jpeg','bmp','png','swf'))) {
			$info = getimagesize($file['tmp_name']);
			if ($info == false || (strtolower($file['ext'] == false && empty($info['bits'])))) {
				$this->error = '非法图像文件';
				return false;
			}

		}

		if (!move_uploaded_file($file['tmp_name'], $filename)) {
			$this->error = '文件上传保存错误！';
			return false;
		}

		return true;
	}

	/**
	 * [check 检查文件]
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	private function check ( $file ) {

		if ($file['error'] !== 0) {
			$this->error($file['error']);
			return false;
		}
		
		if (!$this->checkSize($file['size'])) {

			$this->error = '上传文件的大小超过了';
			return false;
		}

		if (!$this->checkType($file['type'])) {
			$this->error = '上传文件类型是不被允许的！';
			return false;
		}
		if (!$this->checkExt($file['ext'])) {
			$this->error = '上传文件的后缀不被允许！';
			return false;
		}
		if (!$this->checkupload($file['tmp_name'])) {
			$this->error = '上传文件不合法';
			return false;
		}

		return true;

	}
	/**
	 * [checkupload 判断是否合法上传]
	 * @param  [type] $filename [description]
	 * @return [type]           [description]
	 */
	private function checkupload ($filename) {
		return is_uploaded_file($filename);
	}

	/**
	 * [checkExt 检查文件后缀]
	 * @param  [type] $ext [description]
	 * @return [type]      [description]
	 */
	private function checkExt ($ext) {
		if (!empty($this->exts)) 
			return in_array(strtolower($ext), $this->exts);
		return true;
		
	}

	/**
	 * [checkType 检查文件类型]
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	private function checkType ($type) {
		if (!empty($this->types)) 
			return in_array(strtolower($type), $this->types);
		return true;
		
	}

	/**
	 * [checkSize 检查大小]
	 * @param  [type] $size [description]
	 * @return [type]       [description]
	 */
	private function checkSize ($size) {
		return !($size > $this->maxSize) || (0 == $this->maxSize);
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
	 * [getExt 获取文件后缀]
	 * @param  [ayyar] $filename [文件名]
	 * @return [string]           [文件后缀]
	 *  pathinfo() 函数以数组的形式返回文件路径的信息,返回一个关联数组包含有 path 的信息。
	 *  [dirname]     => /testweb
	 *  [basename]	  => test.txt
	 *  [extension]   => txt
	 * 
	 */
	private function getExt ($filename) {
		$pathinfo = pathinfo($filename);
		return $pathinfo['extension'];
	}


	/**
	 * [dealFiles 对上传信息进行重组]
	 * @param  [array] $files [上传文件信息]
	 * @return [array] $fileArray      [重组后的信息]
	 */
	private function dealFiles ($files) {

		$fileArray = array();
		$n = 0;

		foreach ($files as $key => $value) {
			if (is_array($value['name'])) {
				$keys = array_keys($value);
				$count = count($value['name']);
				for ($i=0; $i < $count; $i++) { 
					$fileArray[$n]['key'] = $key;
					foreach ($keys as $_key) {
						$fileArray[$n][$_key] = $value[$_key][$i];
					}
					$n++;
				}
			}else{
				$fileArray = $files;
			}
		}

		return $fileArray;
	}

}




 ?>