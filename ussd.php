<?php

//libraries import
require_once('config.php');
require_once('oauth.php');
require_once('dbConnector.php');
require_once('AfricasTalkingGateway.php');

//constants
define('AT_USERNAME','********');
define('AT_APIKEY','*******************************');
define('AT_ENVIRONMENT','******');

//login to salesforce using oauth password authentication
$oauth = new oauth(CLIENT_ID,CLIENT_SECRET,CALLBACK_URL,LOGIN_URL,CACHE_DIR);

//username password authorization
$oauth->auth_with_password(USERNAME,PASSWORD,120);

// Reads the variables sent via POST from our gateway
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];

//Explode the text to get the value of the latest interaction - think 1*1
$textArray=explode('*', $text);
$userResponse=trim(end($textArray));

//4. Set the default level of the user
$level='998'; //new user record to be inserted
$user_name = 'Guest';

//5. Check the level of the user from the DB and retain default level if none is found for this session
$sql = "SELECT * FROM `response` WHERE ID = (SELECT MAX(ID) FROM `response` WHERE sessionID = '".$sessionId."')";
$levelQuery = $db->query($sql);
if($result = $levelQuery->fetch_assoc()) {
    $level = $result['level'];
}


//if level is empty create sessions
if($level != null || $level != ''){
  //check if user response == quit
  if($userResponse == '999'){  //if user selected option 999

    //return a string with user name telling them to select
    $response  = "END Thanks for using our service";

    //insert response to table and upgrade to next ussd level
    $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`,`levelResponse`)
    VALUES('".$phoneNumber."','".$sessionId."','999','".$response."','Quit' )";
    $db->query($sql1);

    header('Content-type: text/plain');
    echo $response;
    exit();

  }else if($userResponse == '998'){

    //create session record
      $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`)
      VALUES('".$phoneNumber."','".$sessionId."','0','New Session Created' )";
      $db->query($sql1);

      //fetch username from salesforce using the
      $name = $oauth->get_record('Primary_Phone__c', trim($phoneNumber),'Employee__c');

      //if returns a name then  set username to the supplied name
      // else set as Guest
      if(is_array($name) && $name['totalSize'] > 0){
        //get the username as returned from salesforce
        $user_name = $name['records']['0']['Name'];

      }

      //return a string with user name telling them to select
      $response  = "CON Hello ". $user_name ." Please Select An Option Below.\n";
      $response .= " 1. Take Survey \n";
      $response .= " 2. My Account\n";
      $response .= " 998. Home \n";
      $response .= " 999. Quit ";

      header('Content-type: text/plain');
      echo $response;
      exit();
  }

  //check value of last responce
  switch($level){
    case '0':
      if($userResponse != null || $userResponse != ''){

        //check the response the user selected
        if($userResponse == '1'){ // if user selected Take Survey Option

          //tell the user to select gender
          $response = "CON Please Select Your Gender \n";
          $response .= "1. Male \n";
          $response .= "2. Female \n";
          $response .= "998. Home \n";
          $response .= "999. Quit ";

          header('Content-type: text/plain');
          echo $response;

          $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`,`levelResponse`)
          VALUES('".$phoneNumber."','".$sessionId."','01','".$response."','".$userResponse."' )";
          $db->query($sql1);

        }else if($userResponse == '2'){ //if user selects my account option

          //fetch account details from salesforce
          $userDetails = $oauth->get_record('Primary_Phone__c', trim($phoneNumber),'Employee__c');

          //if returns details then set username to the supplied name
          if(is_array($userDetails) && $userDetails['totalSize'] > 0){

            //get the details as returned from salesforce
            $user_name = $userDetails['records']['0']['Name'];
            $user_dpt = $userDetails['records']['0']['Department__c'];

            //print the response
            $response  = "END Username        :  ".$user_name." \n";
            $response .= "Phone Number        :  ".$phoneNumber." \n";
            $response .= "Department Unit     :  ".$department." ";

            header('Content-type: text/plain');
            echo $response;

          }else{//if user is not using salesforce registered number
            $response  = "END Username :  Guest \n";
            $response .= "This Number is not registered in salesforce";

            header('Content-type: text/plain');
            echo $response;

          }

        }

      }

    break;

    case '01':

      //return a string with user name telling them to select
      $response = "CON Survey Question 1? \n";

      header('Content-type: text/plain');
      echo $response;

      $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`,`levelResponse`)
      VALUES('".$phoneNumber."','".$sessionId."','02','".$response."','".$userResponse."' )";
      $db->query($sql1);

    break;

    case '02':
      //return a string with user name telling them to select
      $response  = "CON Survey Question 2?\n";

      header('Content-type: text/plain');
      echo $response;

      //insert response to table and upgrade to next ussd level
      $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`,`levelResponse`)
      VALUES('".$phoneNumber."','".$sessionId."','1000','".$response."','".$userResponse."' )";
      $db->query($sql1);

    break;

    //case 998 - Return to main homepage
    case '998':
    //create session record
      $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`)
      VALUES('".$phoneNumber."','".$sessionId."','0','New Session Created' )";
      $db->query($sql1);

      //fetch username from salesforce using the
      $name = $oauth->get_record('Primary_Phone__c', trim($phoneNumber),'Employee__c');

      //if returns a name then  set username to the supplied name
      // else set as Guest
      if(is_array($name) && $name['totalSize'] > 0){
        //get the username as returned from salesforce
        $user_name = $name['records']['0']['Name'];

      }

      //return a string with user name telling them to select
      $response  = "CON Hello ". $user_name ." Please Select An Option Below.\n";
      $response .= " 1. Take Survey \n";
      $response .= " 2. My Account\n";
      $response .= " 998. Home \n";
      $response .= " 999. Quit ";

      header('Content-type: text/plain');
      echo $response;
    break;

    //case 999 - Quit App
    case '999':

      //return a string with user name telling them to select
      $response  = "END Thanks for using our service";

      header('Content-type: text/plain');
      echo $response;

    break;

    case '1000':

      //END Session and thank user for performing the survey
      $response  = "END Thanks for taking you time doing the survey\n";
      $response .= "Your Response is appreciated\n";

      //send airtime if user was among the first 10 respondents
      //insert the airtime table this record
      $sql1 = "INSERT INTO `airtime`(`phoneNumber`,`sessionId`)
      VALUES('".$phoneNumber."','".$sessionId."')";
      $db->query($sql1);

      /////// QUERY TO GET  NUMBER OF PEOPLE WHO HAVE RECEIVED CREDIT
      $sql2 = "SELECT * FROM airtime WHERE sentAirtime = 1";
      $Query = $db->query($sql2);

     //get num of rows/records in the db that have recieved airtime
      $row_cnt = $Query->num_rows;
      //if more than 10 people have received airtime then dont send Airtime
      // else send Airtime
      if($row_cnt <= 10){
        //check if the phone number has received airtime before
        $sql3 = "SELECT * FROM airtime WHERE phoneNumber = '".$phoneNumber."' AND sentAirtime = 1 ";
        $Query1 = $db->query($sql3);
        $row_cnt1 = $Query1->num_rows;

        //if user hasnt received airtime then they are eligible for airtime
        if($row_cnt1 == 0){
          //user has not received airtime before

          /////////////////////// SEND AIRTIME CODE ///////////////////////////////////////
          $recipients = array( array("phoneNumber"=>"".$phoneNumber."", "amount"=>"KES 10") );
          //JSON encode
          $recipientStringFormat = json_encode($recipients);

          $response .= "You will Receive Airtime\n";
          //$gateway = new AfricasTalkingGateway($username, $apikey);
          $gateway = new AfricasTalkingGateway(AT_USERNAME, AT_APIKEY,AT_ENVIRONMENT);
          try {
            //call api to send airtime.
            $results = $gateway->sendAirtime($recipientStringFormat);

            //if successfull then update the table to show that the user has already received airtime
            $sql4 = "UPDATE airtime SET sentAirtime = 1 WHERE phoneNumber = '".$phoneNumber."' AND sessionId = '".$sessionId."' ";
            $Query2 = $db->query($sql4);


          }catch(AfricasTalkingGatewayException $e){
            echo $e->getMessage();
          }

          //////////////////////////////////////////////////////////////////////////////////

        }
      }

      header('Content-type: text/plain');
      echo $response;

    break;
  }

}else{
  //create session record
  $sql1 = "INSERT INTO `response`(`PhoneNumber`,`sessionID`,`level`,`levelText`)
  VALUES('".$phoneNumber."','".$sessionId."','".$level."','New INsert' )";
  $db->query($sql1);
}

?>
