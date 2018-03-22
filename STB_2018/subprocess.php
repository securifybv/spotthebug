<?php

	function syncDB() { .. };
	function bcdechex($dec) { .. };

	$n = "37662123018614894363000505295713618793576071522786088024797791265545848021381210845377436711475574095578204570155391624887547302667908836835675664304233389970978806204091543317544485506272376395108063018443343946143277267975714261855561020081290532392743652531864719705264768528667954781074780935036249053507555769459";
	$d = "3";

	class localDB extends SQLite3 {
	  function __construct() {
	  	$this->open('/var/www/html/stb.db');
	  }
	}

	function checkRow($db, $query) {
		$ret = $db->query($query);
	   	$row = $ret->fetchArray(SQLITE3_ASSOC);
	   	if ($row === false) 
	    	throw new Exception("retrieving row");
	   	else
	   		return $row;
	}

	function checkUpdate($db, $query) {
		if (!$db->exec($query))
			throw new Exception("update query");
	}

	function performTransfer($db, $parsed) {
		$transaction_fee = 0.1;

		// Get user data
		$userRow = checkRow($db, "SELECT * from accounts WHERE amount >= ".(int)($parsed["amount"]+$transaction_fee)." AND currency = '" . $db->escapeString($parsed["currency"])."' AND user_id = ". $db->escapeString($parsed["uid"])." AND token = '". $db->escapeString($parsed["token"])."'");
		
		// Get target account data
		$targetRow = checkRow($db, "SELECT * FROM accounts WHERE currency = '" . $db->escapeString($parsed["currency"]) . "' AND address = '" . $db->escapeString($parsed["targetAd"]) ."'");

		// Free transactions to accounts for same user
		if ($userRow["user_id"] == $targetRow["user_id"])
			$transaction_fee = 0.0;

		// Add money to target account
		checkUpdate($db, "UPDATE accounts SET amount = ".($targetRow["amount"] + $parsed["amount"])." WHERE currency = '".$targetRow["currency"]."' AND ID = '".$targetRow["ID"]."'");
		
		// Substract from local account
		checkUpdate($db, "UPDATE accounts SET amount = ".($userRow["amount"] - $parsed["amount"] - $transaction_fee)." WHERE currency = '".$userRow["currency"]."' AND ID = '".$userRow["ID"]."'");	
	}

	try {
		// Retrieve shared memory
		$shm_id = shmop_open((int)$argv[1], "c", 0644, (int)$argv[2]);
		$ciphertext = shmop_read($shm_id, 0, (int)$argv[2]);
		
		// To make all our cloud-based microservices independant, we use local sqlite databases 
		$db = new localDB();
		if(!$db)
			throw new Exception($db->lastErrorMsg());
		
		// We use RSA for encryption. Even if you can bypass our high-grade security, you have to know a user's cash token!
		$msg = hex2bin(bcdechex(bcpowmod($ciphertext, $d, $n)));
	
		// Parse message, format = "userID;token;currency;amount;to_address"
		$exp = explode(";", $msg);
		if (sizeof($exp) != 5)
			throw new Exception("parsing message\x00");

		$parts = ["uid" => $exp[0], "token" => $exp[1], "currency" => $exp[2], "amount" => $exp[3], "targetAd" => $exp[4]];
		if ($parts["amount"] <= 0)
			throw new Exception("can't transfer negative amount\x00");

		// Perform transfer
		performTransfer($db, $parts);

		// Sync db with backend service
		syncDB();

		shmop_write($shm_id, $parts["token"].' transferred '.$parts["amount"].' to '.$parts["targetAd"]."\x00", 0);

	} catch(Exception $e) {
		shmop_write($shm_id, '# Error: ' . $e->getMessage(), 0);
		exit(0);
	}
?>