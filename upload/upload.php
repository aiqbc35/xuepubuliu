<?php 
include './uploadAll.class.php';

$upload = new UplocadAll();

$upload->exts = array('jpg','png','jpeg','gif');
$upload->savePath = './images/';
$upload->maxSize = 1024000;
$upload->exts = array('jpg');

if ($upload->upload()) {
	p($upload->getUploadInfo());
}else{
	echo $upload->getError();
}


function p ($data = array()) {
	echo "<pre>";
	print_r($data);
	echo "<pre>";
}

 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<title>上传文件</title>
</head>
<body>
	<form action="" method="post" enctype="multipart/form-data">
		<input type="file" name="file[]" /><br />
		<input type="file" name="file[]" /><br />
		<input type="submit" value="提交" />
	</form>
</body>
</html>