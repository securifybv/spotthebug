<?php
	define("UPLOAD_DIR", $_SERVER['DOCUMENT_ROOT']);

	if (!empty($_FILES["picture"])) {
		$myfile = $_FILES["picture"];
	}else{
		exit();
	}

	// On default imagename (e.g. "1980MoNaLiSa.jpg") toupper the username
	$myName = preg_replace('/^[0-9]{4}(.*)\.jpg/e', 'strtoupper(\\1)', $myFile["name"]);

	log_message('INFO','##ENTER##: new file uploaded -> : ' . $myName);

	$myName = preg_replace("/[^A-Z0-9).(:_-]/i", "_", $myName);

	$fileUploaded = UPLOAD_DIR . $myName;

	// get the uploaded file
	move_uploaded_file($myFile["tmp_name"], $fileUploaded);

	// Check if valid image, null if no image
	$valid = getimagesize($fileUploaded);

	// Get a random string of 25 chars for filename
	$random = exec("dd if=/dev/random count=1 | md5sum | head -c10");

	// Prep the filename
	$fileConvert = UPLOAD_DIR . $random . $myName . ".jpg";

	// Paranoid
	if (file_exists($fileConvert)){
		log_message('ERROR','This should not happen! Random file already exists!');
		exit();
	}

	// Convert image to JPG format

	if ($valid){
		exec("/usr/bin/convert $fileUploaded $fileConvert", $result, $resultcode);
	}else{
		log_message('ERROR','File is not a valid image!');
		echo "not valid";
	}

	// Clean up file
	unlink($fileUploaded);

	exit();
?>
