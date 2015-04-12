<?php
	/*
		Other affiliated files (do not delete these):
			vend.php
			vend_data.txt
	
		@author Nick Clark <nick@itsoftware.net.au>
		@version 1.0.0
	*/

	// if there is an error
	if(isset($_GET['error'])) {
		throw new exception('Vend Connection Error: ' . $_GET['error']);
		exit;
	}

	$code = "";
	$prefix = "";

	// Set the secret code
	if(isset($_GET['code'])) {
		$code = $_GET['code'];
	}

	// Set the domain prefix
	if(isset($_GET['domain_prefix'])) {
		$prefix = $_GET['domain_prefix'];
	}

	// Put the secret code and domain prefix in the vend_data text file
	file_put_contents("vend_data.txt", $code . "|" . $prefix);
?>