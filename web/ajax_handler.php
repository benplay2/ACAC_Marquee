<?php
//Copyright 2022 Benjamin Brust

//    Copyright 2022 Benjamin Brust.
//    #Files required:
//    #ALL TEXT FILES WILL BE AUTOMATICALLY GENERATED
//    #
//    #All text files must be writable by all. (Taken care of by the python script)
//    #
//    #Text files used:
//    #badwords-big-opt.txt -> In directory of this file
//    #/var/www/theme.txt -> contains boolean & message
//    #/var/www/score.txt -> contains boolean & message
//    #/var/www/custT1.txt -> contains boolean & message
//    #/var/www/custT2.txt -> contains boolean & message
//    #/var/www/clerkCall.txt -> contains boolean & event1 $ event2
//    #/var/www/clerkMsg.txt -> contains boolean & message
//    #/var/www/custT3.txt -> contains boolean & message
//    #/var/www/ID.txt -> contains device ID
//    #/var/www/defTheme.txt -> contains default theme
//    #/var/www/cenLev.txt -> contains censorship level
//    #/var/www/ip.txt -> stores the IP address of the running system on one line
//    #/var/www/status.txt -> stores the last few status messages to update the web
//    #/var/www/time.txt -> contains 2 lines, boolean, and initial time set value in case of no pi internet access
//    #/var/www/sysMsg.txt -> stores system error messages
//    #/var/www/sysDo.txt -> contains one line that instructs the python code to perform special tasks with sysMsg.txt
//    
//    #Website Files used:
//    #Must have PHP enabled, and set php files to executable.
//    #    (Executable permission is taken care of by python script)
//    #/var/www/index.html -> redirects to index.php
//    #/var/www/index.php -> contains the user interface. Calls ajax_handler.php.
//    #/var/www/admin.php -> Use to change ID of display
//    #/var/www/ajax_handler.php -> Performs work on the text files. Called by index.php.
//    #/var/www/jquery-1.10.0.min.js -> javascript file to handle calling php file from javascript
//    	Many thanks to those who contributed to making this project a success:
//	
//		<li>GE Intelligent Platforms for donating the marquee displays</li>
//		<li>Benjamin Brust</li>
//		<li>Bob Brust</li>
//		<li>Greg Faust</li>
//	
//	Marquee website created by Benjamin Brust.
//	Please send any comments or suggestions to Ben Brust (brustb@gmail.com)
//	Copyright &copy 2013 Benjamin Brust.
        
if(isset($_POST['action']))
    {
        switch($_POST['action'])
        {
            //The following are the functions that can be called by index.php
            case 'refresh': alertRaspberry(); break;
            case 'clear': clearDisplay(); break;
            case 'toggleConfig': toggleConfig(); break;
            case 'setID': setID($_POST['ID']); break;
            case 'setdefTheme': setdefTheme($_POST['theme']); break;
            case 'setcenLev': setcenLev($_POST['cenLevInt']); break;
            case 'time': writeTime($_POST['set'], $_POST['on']); break;
            case 'theme': writeTheme($_POST['text'], $_POST['on']); break;
            case 'score': writeScore($_POST['text'], $_POST['on'], $_POST['event'], $_POST['team1'], $_POST['score1'], $_POST['team2'],
                                        $_POST['score2'], $_POST['team3'], $_POST['score3'], $_POST['team4'], $_POST['score4']); break;
            case 'custT1': writeCustT1($_POST['text'], $_POST['on']); break;
            case 'custT2': writeCustT2($_POST['text'], $_POST['on']); break;
            case 'clerk': writeClerkCall($_POST['on'], $_POST['event1']); break;
            case 'clerkM': writeClerkMessage($_POST['text'], $_POST['on']); break;
            case 'custT3': writeCustT3($_POST['text'], $_POST['on']); break;
            case 'status': setStatus($_POST['status']); break;
            case 'sysMsg': setSysDo($_POST['string']); break;
            default: break;
        }
    }
function alertRaspberry($varChange = false){
    //changes the file change.txt to tell raspberry pi to update
    //If only a variable change has occured, another line will be added
    $num = 1;
    $path = "change.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $num = intval($array[0]);
        $num += 1;
        if ($num > 1000){//cycle count after it reaches 1000
            $num == 1;
        }
    }
    file_put_contents('./change.txt', $num);
    if ($varChange){//if changed value only affects a variablely shown message
        file_put_contents('./change.txt', "\n", FILE_APPEND);
        file_put_contents('./change.txt', $varChange, FILE_APPEND);
    }
}
function toggleConfig(){
    //Toggle the display from showing first-time start or new messages
    $path = "change.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $num = intval($array[0]);
    }
    if ($num == 0){
        $message = "Messages on display have been enabled";
        file_put_contents('./change.txt', "1");
    }
    else{
        $message = "Configuration has been displayed on the marquee";
        file_put_contents('./change.txt', "0");
    }
    
    setStatus($message);
}
function clearDisplay(){
    //Clear display messages as well as all saved messages
    $path = "defTheme.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $defTheme = $array[0];
    }
    if (!$defTheme){
        $defTheme = "Welcome";
    }
    file_put_contents('./change.txt',"0");
    file_put_contents('./clerkCall.txt', "false");
    file_put_contents('./clerkMsg.txt', "false");
    file_put_contents('./custT1.txt', "false");
    file_put_contents('./custT2.txt', "false");
    file_put_contents('./custT3.txt', "false");
    file_put_contents('./score.txt', "false");
    file_put_contents('./scoreSave.txt', "");
    file_put_contents('./theme.txt', "true");
    file_put_contents('./theme.txt', "\n",FILE_APPEND);
    file_put_contents('./theme.txt', $defTheme , FILE_APPEND);
    file_put_contents('./time.txt', "false");
    
    setStatus("Display has been cleared of previously saved messages");
}
function writeTime($set, $on) {
    //Write the theme in the file
    file_put_contents('./time.txt', $on);
    file_put_contents('./time.txt', "\n", FILE_APPEND);
    file_put_contents('./time.txt', $set, FILE_APPEND);
    alertRaspberry();
    
    if ($on == "true"){
        $message = "Time enabled";
    }
    else{
        $message = "Time disabled";
    }
    setStatus($message);
}
function writeTheme($text, $on) {
    //Write the theme in the file
    $text = stripslashes($text);
    file_put_contents('./theme.txt', $on);
    file_put_contents('./theme.txt', "\n", FILE_APPEND);
    file_put_contents('./theme.txt', $text, FILE_APPEND);
    alertRaspberry();
    
    
    if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Theme message written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: theme off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Theme message is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing theme message";
    }
    setStatus($message);
}

function writeScore($text, $on, $event, $team1, $score1, $team2, $score2, $team3, $score3, $team4, $score4) {
    //write the score in the file
    file_put_contents('./score.txt', $on);
    file_put_contents('./score.txt', "\n", FILE_APPEND);
    file_put_contents('./score.txt', $text, FILE_APPEND);
    alertRaspberry();
    
    if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Score written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: score off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Score is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing score";
    }

    file_put_contents('./scoreSave.txt', $event);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $team1, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $score1, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $team2, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $score2, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $team3, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $score3, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $team4, FILE_APPEND);
    file_put_contents('./scoreSave.txt', "\n", FILE_APPEND);
    file_put_contents('./scoreSave.txt', $score4, FILE_APPEND);

    setStatus($message);
}

function writeCustT1($text, $on) {
    //write custom message in the file
    $text = stripslashes($text);
    file_put_contents('./custT1.txt', $on);
    file_put_contents('./custT1.txt', "\n", FILE_APPEND);
    file_put_contents('./custT1.txt', $text, FILE_APPEND);
    alertRaspberry();
    
    if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Custom message 1 written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: Custom message 1 off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Custom message 1 is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing custom message 1";
    }
    setStatus($message);
}
function writeCustT2($text, $on) {
    $text = stripslashes($text);
    //write custom message to file
    file_put_contents('./custT2.txt', $on);
    file_put_contents('./custT2.txt', "\n", FILE_APPEND);
    file_put_contents('./custT2.txt', $text, FILE_APPEND);
    alertRaspberry();
    
     if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Custom message 2 written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: Custom message 2 off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Custom message 2 is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing custom message 2";
    }
    setStatus($message);
}
function writeClerkCall($on, $event1) {
    $event1 = stripslashes($event1);
    //write clerk call to file
    $varChange = false;
    $path = "clerkCall.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $clerkOn = $array[0];
        if ($clerkOn == $on){
            $varChange = true;
        }
    }

    file_put_contents('./clerkCall.txt', $on);
    file_put_contents('./clerkCall.txt', "\n", FILE_APPEND);
    file_put_contents('./clerkCall.txt', $event1, FILE_APPEND);
    
    alertRaspberry($varChange);
    
    if (sizeof($event1) > 0 && sizeof($event1) <= 250){
        if ($on == "true"){
            $message = "Clerk call written successfully: " . strval($event1);
        }
        else{
            $message = "Completed the action requested: Clerk call off";
        }
    }
    else if (sizeof($event1) > 250){
        $over = 250-sizeof($event1);
        $message = "Error: Clerk call is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing clerk call";
    }
    setStatus($message);
    
    
}

function writeClerkMessage($text, $on) {
    $text = stripslashes($text);
    //write clerk message to file
    file_put_contents('./clerkMsg.txt', $on);
    file_put_contents('./clerkMsg.txt', "\n", FILE_APPEND);
    file_put_contents('./clerkMsg.txt', $text, FILE_APPEND);
    alertRaspberry();
    
    if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Custom clerk message written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: Custom clerk message off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Custom clerk message is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing custom clerk message";
    }
    setStatus($message);
}
function writeCustT3($text, $on) {
    $text = stripslashes($text);
    //write custom message to file
    file_put_contents('./custT3.txt', $on);
    file_put_contents('./custT3.txt', "\n", FILE_APPEND);
    file_put_contents('./custT3.txt', $text, FILE_APPEND);
    alertRaspberry();
    
    if (sizeof($text) > 0 && sizeof($text) <= 250){
        if ($on == "true"){
            $message = "Lower custom message 2 written successfully: " . strval($text);
        }
        else{
            $message = "Completed the action requested: Lower custom message 2 off";
        }
    }
    else if (sizeof($text) > 250){
        $over = 250-sizeof($text);
        $message = "Error: Lower custom message 2 is " . strval($over) . " characters too long.";
    }
    else{
        $message = "Error writing lower custom message 2";
    }
    setStatus($message);
}


function setID($ID) {
    $ID = stripslashes($ID);
    //set the device id
    file_put_contents('./ID.txt', $ID);
}
function setdefTheme($theme) {
    $theme = stripslashes($theme);
    //set the device theme
    file_put_contents('./defTheme.txt', $theme);
}
function setcenLev($cenLevInt) {
    //set the device censorship level
    file_put_contents('./cenLev.txt', $cenLevInt);
}
function setSysDo($string) {
    //update sysDo.txt to string
    file_put_contents('./sysDo.txt', $string);
    alertRaspberry();
}

function setStatus($status){
    //write the status, saving the past 2...
    $status = stripslashes($status);
    $path = "status.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $num = intval($array[0]);
        file_put_contents('./status.txt', $status);
        
        $len = sizeof($array);
        $count = 0;
        while(($count <= 2) && ($count < $len)){
            file_put_contents('./status.txt', "\n", FILE_APPEND);
            file_put_contents('./status.txt', $array[$count], FILE_APPEND);
            $count += 1;
        }
    }
    else {
        file_put_contents('./status.txt', $status);
    }
}
?>