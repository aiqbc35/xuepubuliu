<?php 
include './upload.class.php';

$upload = new Upload();

$upload->exts = array('jpg','png','jpeg','gif');
$upload->savePath = './images/';

$info = $upload->upload();

if (!$info) {
	$upload->getError();
}else{
	print_r($info);
	echo '上传成功！';
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
		<input type="file" name="file[]" />
		<input type="submit" value="提交" />
	</form>
</body>
</html>