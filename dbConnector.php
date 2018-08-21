<?php
//theUssdDb.php
//Connection Credentials
$servername = 'localhost';
$username = '*****';
$password = '*****';
$database = '*****';



    // Create connection
    $db = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($db->connect_error) {
        header('Content-type: text/plain');
        //log error to file/db $e-getMessage()
        die("END An error was encountered. Please try again later");
    }


?>
