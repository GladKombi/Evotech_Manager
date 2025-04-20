<?php
try {
	session_start();

	// $connexion=new PDO('mysql:dbname=web243_kwetunature; host=mysql.us.cloudlogin.co', 'web243_kwetunature', 'cD3q5N1Ug=');
	$connexion = new PDO('mysql:dbname=evotech_manager; host=localhost', 'root', '');
} catch (Exception $e) {
	echo $e;
}
