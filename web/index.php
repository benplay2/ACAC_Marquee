<html lang="en">
<head>
    <title>Marquee Controller</title>
    <!--Copyright 2013 Benjamin Brust.-->
<!--    #Files required:
    #ALL TEXT FILES WILL BE AUTOMATICALLY GENERATED
    #
    #All text files must be writable by all. (Taken care of by the python script)
    #
    #Text files used:
    #badwords-big-opt.txt -> In directory of this file
    #/var/www/theme.txt -> contains boolean & message
    #/var/www/score.txt -> contains boolean & message
    #/var/www/custT1.txt -> contains boolean & message
    #/var/www/custT2.txt -> contains boolean & message
    #/var/www/clerkCall.txt -> contains boolean & event1 $ event2
    #/var/www/clerkMsg.txt -> contains boolean & message
    #/var/www/custT3.txt -> contains boolean & message
    #/var/www/ID.txt -> contains device ID
    #/var/www/defTheme.txt -> contains default theme
    #/var/www/cenLev.txt -> contains censorship level
    #/var/www/ip.txt -> stores the IP address of the running system on one line
    #/var/www/status.txt -> stores the last few status messages to update the web
    #/var/www/time.txt -> contains 2 lines, boolean, and initial time set value in case of no pi internet access
    #/var/www/sysMsg.txt -> stores system error messages
    #/var/www/sysDo.txt -> contains one line that instructs the python code to perform special tasks with sysMsg.txt
    
    #Website Files used:
    #Must have PHP enabled, and set php files to executable.
    #    (Executable permission is taken care of by python script)
    #/var/www/index.html -> redirects to index.php
    #/var/www/index.php -> contains the user interface. Calls ajax_handler.php.
    #/var/www/admin.php -> Use to change ID of display
    #/var/www/ajax_handler.php -> Performs work on the text files. Called by index.php.
    #/var/www/jquery-1.10.0.min.js -> javascript file to handle calling php file from javascript-->
    
    
    <!--The Following php code is to enter initial values into the forms.-->
    <?php
    //Get current toggle message to display to user
    $path = "change.txt";
    //change.txt contains a number and possible a second line. Each display update, the number changes.
    //If the number is 0, it signals to the python code to display configuration information.
    //If the second line exists, it means that the only display change has been involving a message treated
    //as a variable by the marquee. (such as time and clerkCall).
    global $changeVal;
    if (!file_exists($path)){
        $buttonDisp = "Show Messages";
        $buttonTip = "Display messages instead of configuration on display";
    }
    else{// (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $changeVal = $array[0];
        if ($changeVal == "0"){
            $buttonDisp = "Show Messages";//text to show on button
            //tool tip for this button
            $buttonTip = "Display messages instead of configuration on display";
        }
        else{
            $buttonDisp = "Show Configuration on Display"; //text to show on button
            //tool tip for this button
            $buttonTip = "Display web address, ID, and default theme on display, as when display is first started";
        }
    }
    function boolConvert($stringIn){//function to convert text boolean to php useable, and returns the current display state
        //Returns string. Use is to label check-marks
        global $changeVal;
        if (($changeVal == "0") || ($stringIn === "false")){//the display will not show any messages except for configuratioon when $changeVal is 0
            return "Currently off";//text for button
        }
        else if ($stringIn === "true"){
            return "Currently on";//text for button
        }
        else{
            return $stringIn;
        }
    }
    function buttonToggle($boolStringIn){//either returns a space or a checkmark, for use with custom checkmarks
        //Returns string
        global $changeVal;//the display will not show any messages except for configuratioon when $changeVal is 0
        if (($changeVal == "0") || ($boolStringIn == "false")){//if the message is off
            return " ";//display a blank checkbox
        }
        else if ($boolStringIn == "true"){//if the message is on
            return "&#x2713";//display a button with a checkmark in it
        }
        else{
            return $boolStringIn;
        }
    }
    function getColor($boolIn){//function to convert text boolean to useable
        //Returns string, or boolean if input is not boolean as specified
        global $changeVal;//the display will not show any messages except for configuratioon when $changeVal is 0
        if ($changeVal == "0" || $boolIn == "false"){ //if the message is off
            return "red-style";//show the status of the message in red
        }
        else if ($boolIn == "true"){//if the message is on
            return "green-style";//show the status of the message in green
        }
        else{
            return false;
        }
    }
    
    //Get current ID to display to user
    $path = "ID.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));//create array from ID.txt
        $ID = $array[0]; //first line of ID.txt is the ID of the display
    }
    else{
        $ID = "Unnamed";
    }
    //Get previous status messages to show to user
    $path = "status.txt";
    if (file_exists($path)){
        $statusArray = preg_split("/\n/", file_get_contents($path));//create array from status.txt. Used later in web page.
    }
    ////Get previous internet status
    //$path = "internet.txt";
    //if (file_exists($path)){
    //    $internetArray = preg_split("/\n/", file_get_contents($path));//create array from internet.txt. Used later for time.
    //    $internetOn = $internetArray[0];
    //}
    //else{
    //    $internetOn = "false";
    //}
    $internetOn = "false"; //override automatic clock set with internet
    
    //Get current score boolean
    $path = "score.txt";
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }
    $array = preg_split("/\n/", file_get_contents($path));//create array from score.txt
    $scoreOn = boolConvert($array[0]); //get the display state
    $scoreColor = getColor($array[0]); //get the color format of the status to display to web operator
    $scoreTog = buttonToggle($array[0]); //get the status of the custom checkbox
    $scoreBool = $array[0]; //store the raw boolean string for use with javascript

    //Get current score to display to user
    $path = "scoreSave.txt";
    if (!file_exists($path)){
        file_put_contents($path, "");
        chmod($path, 0777);
    }
    $array = preg_split("/\n/", file_get_contents($path));//create array from scoreSave.txt
    if (isset($array[0])){
        $event = $array[0]; //event that the meet is scored through
        if (isset($array[1])){
            $team1 = $array[1]; //first team to show score
            if (isset($array[2])){
                $score1 = $array[2]; //score of first team
                if (isset($array[3])){
                    $team2 = $array[3]; //second team to show score
                    if (isset($array[4])){
                        $score2 = $array[4]; //score of second team
                        if (isset($array[5])){
                            $team3 = $array[5]; //third team to show score
                            if (isset($array[6])){
                                $score3 = $array[6]; //score of third team
                                if (isset($array[7])){
                                    $team4 = $array[7]; //fourth team to show score
                                    if (isset($array[8])){
                                        $score4 = $array[8];//score of fourth team
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    //Get info about the time display
    $path = "time.txt";
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }
    $array = preg_split("/\n/", file_get_contents($path));//create array from theme.txt
    $timeOn = boolConvert($array[0]); //get the boolean display state
    $timeColor = getColor($array[0]); //get the color format to show the theme status
    $timeBool = $array[0];//store the raw boolean string for use with javascript
    $timeTog = buttonToggle($array[0]); //get the status for the custom checkbox
    
    //Get current theme to display to user
    $path = "theme.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }
    $array = preg_split("/\n/", file_get_contents($path));//create array from theme.txt
    $themeOn = boolConvert($array[0]); //get the boolean display state
    $themeMsg = $array[1]; //message displayed by theme
    $themeColor = getColor($array[0]); //get the color format to show the theme status
    $themeBool = $array[0];//store the raw boolean string for use with javascript
    $themeTog = buttonToggle($array[0]); //get the status for the custom checkbox
    
    //Get current custom message 1 to display to user
    $path = "custT1.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }

    $array = preg_split("/\n/", file_get_contents($path)); //create array from custT1.txt
    $custT1On = boolConvert($array[0]); //get the boolean display state
    if (isset($array[1])){
        $custT1Msg = $array[1]; //message displayed by cuetom message 1
    }
    $custT1Color = getColor($array[0]); //get the color format to show the custom message status
    $custT1Bool = $array[0]; //store the raw boolean string for use with javascript
    $custT1Tog = buttonToggle($array[0]); //get the status for the custom checkbox

    //Get current custom message 2 to display to user
    $path = "custT2.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }

    $array = preg_split("/\n/", file_get_contents($path));
    $custT2On = boolConvert($array[0]); //get the boolean display state
    if (isset($array[1])){
        $custT2Msg = $array[1];
    }
    $custT2Color = getColor($array[0]); //get the color format to show the custom message status
    $custT2Bool = $array[0]; //store the raw boolean string for use with javascript
    $custT2Tog = buttonToggle($array[0]); //get the status for the custom checkbox

    //Get current clerk call to display to user
    $path = "clerkCall.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
        chmod($path, 0777);
    }

    $array = preg_split("/\n/", file_get_contents($path));
    $clerkOn = boolConvert($array[0]);//get the boolean display state
    if (isset($array[1])){
        $clerkEvent1 = $array[1]; //events called to clerk
    }
    //$clerkEvent2 = $array[2];//used to be second blank before version 2.0
    $clerkCallColor = getColor($array[0]); //get the color format to show the Clerk Call status
    $clerkCallBool = $array[0];//store the raw boolean string for use with javascript
    $clerkCallTog = buttonToggle($array[0]); //get the status for the custom checkbox

    //Get current clerk message to display to user
    $path = "clerkMsg.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
    }

    $array = preg_split("/\n/", file_get_contents($path));
    $clerkMsgOn = boolConvert($array[0]);//get the boolean display state
    if (isset($array[1])){
        $clerkMsg = $array[1];//the message
    }
    $clerkMsgColor = getColor($array[0]); //get the color format to show the clerk message status
    $clerkMsgBool = $array[0];//store the raw boolean string for use with javascript
    $clerkMsgTog = buttonToggle($array[0]); //get the status for the custom checkbox

    //Get current custom message 3 to display to user
    $path = "custT3.txt"; //contains 2 lines, boolean, and message
    if (!file_exists($path)){
        file_put_contents($path, "false");
    }

    $array = preg_split("/\n/", file_get_contents($path));
    $custT3On = boolConvert($array[0]);//get the boolean display state
    if (isset($array[1])){
        $custT3Msg = $array[1]; //the message
    }
    $custT3Color = getColor($array[0]); //get the color format to show the custom message status
    $custT3Bool = $array[0];//store the raw boolean string for use with javascript
    $custT3Tog = buttonToggle($array[0]); //get the status for the custom checkbox

    
    //Compile currently displayed messages to show to user
    //They will be added to lists for the top and bottom display in the order that they are written to the marquee
    if ($changeVal == "0"){//only the default screen will be up
        $path = "defTheme.txt";//path to the default theme of the display
        if (file_exists($path)){
            $array = preg_split("/\n/", file_get_contents($path));
            $topArray[0] = $array[0];//set the first message on the top line to the default theme (only top message in config mode)
        }
        else{
            $topArray[0] = "Default Theme";
        }
        
        $path = "./ip.txt";//path to the text file containing one line, the IP address
        $ip_address = "The.Web.Address.to.This.Page";//if the IP address is not accessable, set this as default
        if (file_exists($path)){
            $array = preg_split("/\n/", file_get_contents($path));
            $ip_address = $array[0];
        }
        else{
            $ip_address = "Device IP address";
        }
        $bottomArray[0] = "Website ==> http://" . $ip_address; //concatenate the strings, add to the bottom display line
        $bottomArray[1] = "Unit Name ==> " . strval($ID);
    }
    else{//do the regular (show messages that are enabled)
        $Tindex = 0; //index of the top message array
        if ($timeBool == "true"){
            $topArray[$Tindex] = "*Current Date and Time*";
            $Tindex += 1;
        }
        if ($themeBool == "true"){
            $topArray[$Tindex] = $themeMsg;//add the theme message to the top display line list
            $Tindex += 1;
        }
        if($custT1Bool == "true"){
            $topArray[$Tindex] = $custT1Msg;//add custom message 1 to the top display line list
            $Tindex += 1;
        }
        if ($custT2Bool == "true"){
            $topArray[$Tindex] = $custT2Msg; //add custom message 2 to the top display line list
            $Tindex += 1;
        }
        if ($scoreBool == "true"){
            $path = "score.txt"; //this file inludes 2 lines, boolean, and the finalized score message
            if (file_exists($path)){
                $array = preg_split("/\n/", file_get_contents($path));
                $topArray[$Tindex] = $array[1];//add the score message to the top display line list
                $Tindex += 1;
            }
        }
        $Bindex = 0;//index of the bottom message array
        if($clerkMsgBool == "true"){
            $bottomArray[$Bindex]  = $clerkMsg; //add clerk message to the bottom display line list
            $Bindex += 1;
        }
        if ($custT3Bool == "true"){
            $bottomArray[$Bindex] = $custT3Msg; //add custom message 2(bottom) to the bottom display line list
            $Bindex += 1;
        }
        if ($clerkCallBool == "true"){
            $stringShow = "Events " . strval($clerkEvent1) . " to clerk";  //add clerk call to the bottom display line list
            $bottomArray[$Bindex] = $stringShow;
            $Bindex += 1;
        }
    }
?>
    <script type="text/javascript" src="jquery-1.10.0.min.js"></script>
    <script type="text/javascript">
        //Function is to convert string bool to javascript useable boolean
        function toJSBool (boolIn) {
            //Returns boolean
            if (boolIn == "true"){
                return true;
            }
            else if (boolIn == "false") {
                return false;
            }
            else{
                return boolIn;
            }
        }
    </script>
        <script type="text/javascript">
        //Function is to toggle between time displayment on the top screen
        function timeResult () {
            //returns void
            var changeNum = <?=$changeVal?>;//taken from php earlier
            var turnOn = toJSBool(<?=$timeBool?>);//gives value of 1 or 0 (if this message is on or not)
            var internetOn = toJSBool(<?=$internetOn?>);
            var status;
            var cancel = false;
            
            if (changeNum == "0") {//if it is the first message to display, the user wants it on
                turnOn = true;
            }
            else{
                turnOn = !turnOn;//toggle the state
            }
            if ((turnOn == true) && (internetOn == false)) {
                //The following code retrieves the current date & time from the user's computer
                var date = new Date();
                var month = date.getMonth() + 1;
                var day = date.getDate();
                var year = date.getFullYear();
                var hour = date.getHours();
                var minute = date.getMinutes();
                var second = date.getSeconds();
                var timeSet = month + "/" + day + "/" + year + " " + hour + ":" + minute + ":" + second;
                //It then prompts the user to check time
                var timeSet = prompt("Enter current date & Time: M/D/Y H:M:S",timeSet);
                if (!timeSet) {
                    //The user hit cancel
                    turnOn = false;
                    cancel = true;
                }
            }
            if (!cancel) {            
                //call the function in ajax_handler.php to write the theme message, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'time', set: timeSet, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error sending Theme message: " + data;
                    //}
                    reloadPage();
                })
            }

            
    }
        </script>
    <script type="text/javascript">
        //Function is to write theme on the top screen
        function themeResult (form,onToggle) {
            //returns void
            var changeNum = <?=$changeVal?>;//taken from php earlier
            var textVar = form.themeIn.value;
            var turnOn = toJSBool(<?=$themeBool?>);//gives value of 1 or 0 (if this message is on or not)
            var status;
            
            if (onToggle) {
                turnOn = !turnOn;
                if (changeNum == "0") {
                    turnOn = true;
                }
            }
            else{
                turnOn = true;
            }
            if (textVar.length == 0) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Error: Theme message is too long, it must be 250 characters or less.")
            }
            else{
                //call the function in ajax_handler.php to write the theme message, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'theme', text: textVar, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error sending Theme message: " + data;
                    //}
                    reloadPage();
            })
            }
    }
    </script>
    <script type="text/javascript">
        //Function is to write score on the top screen
        function scoreResult (form,onToggle) {
            //returns void
            var team1 = form.name1box.value;
            var team2 = form.name2box.value;
            var team3 = form.name3box.value;
            var team4 = form.name4box.value;
            var score1 = form.score1box.value;
            var score2 = form.score2box.value;
            var score3 = form.score3box.value;
            var score4 = form.score4box.value;
            var turnOn = form.scoreOn.checked
            var eventNum = form.eventBox.value;
            var textVar = "Score through event " + eventNum + ": " + team1 + " " + score1;
            
            var turnOn = toJSBool(<?=$scoreBool?>);//gives value of 1 or 0 (if this message is on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if (turnOn) {
                if ((!(team1 && score1 && eventNum))) {
                    turnOn = false;
                }
            }
            if (team2 && score2) {
                textVar += ", " + team2 + " " + score2;
            }
            if (team3 && score3) {
                textVar += ", " + team3 + " " + score3;
            }
            if (team4 && score4) {
                textVar += ", " + team4 + " " + score4;
            }
            if (textVar.length > 250) {
                //must check for length to be under 250 characters
                rollOver = 250 - textVar.length;
                alert("Error: Score statement is " + rollOver + " characters too long. Full message must be fewer than 250 characters.");
            }
            else{
                //call the function in ajax_handler.php to write the score, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'score', text: textVar, on: turnOn, event: eventNum, team1: team1, score1: score1,
                        team2: team2, score2: score2, team3: team3, score3: score3, team4: team4, score4: score4}, function(data){
                    //if (data){
                    //    status = "Error sending score: " + data;
                    //}
                    reloadPage()
                })
                //the following code saves entries for retrieval later
                //$.post("ajax_handler.php", {action: 'scoreSave', on: turnOn, event: eventNum, team1: team1, score1: score1,
                //        team2: team2, score2: score2, team3: team3, score3: score3, team4: team4, score4: score4}, function(data){
                //            reloadPage();
                //            })
            }
    }
    </script>
    
    <script type="text/javascript">
        //Function is to write custom message 1 on the top screen
        function custTResult1 (form,onToggle) {
            //returns void
            var textVar = form.custTIn.value;
            
            var turnOn = toJSBool(<?=$custT1Bool?>);//gives value of 1 or 0 (if this message is on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if (textVar.length == 0) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Custom message is too long. Must be 250 characters or less.")
            }
            else{
                //call the functiono in ajax_handler.php to write the custom message 1, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'custT1', text: textVar, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error sending custom message 1: " + data;
                    //}
                    reloadPage();
                 })
            }
        }
    </script>
    <script type="text/javascript">
        //Function is to write custom message 2 on the top screen
        function custTResult2 (form,onToggle) {
            //returns void
            var textVar = form.custTIn.value;
            
            var turnOn = toJSBool(<?=$custT2Bool?>);//gives value of 1 or 0 (if this message is on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if (textVar.length == 0) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Custom message is too long. Must be 250 characters or less.")
            }
            else{
                //call the functiono in ajax_handler.php to write the custom message 2, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'custT2', text: textVar, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error sending custom message 2: " + data;
                    //}
                    reloadPage();
                 })
            }
    }
    </script>
    <script type="text/javascript">
        //Function is to write clerk report on the lower screen
        function clerkResult (form,onToggle) {
            //returns void
            var event1 = form.event1.value;
            var textVar;
            textVar = "Events " + event1 + " to clerk";
            
            var turnOn = toJSBool(<?=$clerkCallBool?>);//gives value of 1 or 0 (if the clerk call is already on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if ((event1.length == 0)) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Clerk call is too long. Must be 250 characters or less.")
            }
            else{
                //call the functiono in ajax_handler.php to write the clerk call, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'clerk', on: turnOn, event1: event1}, function(data){
                    //if (data){
                    //    status = "Error sending clerk call: " + data;
                    //}
                    reloadPage();
                 })
            }
        }
    </script>
    <script type="text/javascript">
        //Function is to write custom clerk message on lower screen
        function custClerkResult (form,onToggle) {
            //returns void
            var textVar = form.custTIn.value;
            
            var turnOn = toJSBool(<?=$clerkMsgBool?>);//gives value of 1 or 0 (if this message is on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if (textVar.length == 0) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Custom message is too long. Must be 250 characters or less.")
            }
            else{
                //call the functiono in ajax_handler.php to write the clerk message, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'clerkM', text: textVar, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error Sending Custom Clerk Message: " + data;
                    //}
                    reloadPage();
                 })
            }
    }
    </script>
    <script type="text/javascript">
        //Function is to write custom message 2 on lower screen
        function custBResult2 (form,onToggle) {
            //returns void
            var textVar = form.custTIn.value;
            var turnOn = toJSBool(<?=$custT3Bool?>);//gives value of 1 or 0 (if this message is on or not)
            if (onToggle) {
                turnOn = !turnOn;
            }
            else{
                turnOn = true;
            }
            if (textVar.length == 0) {
                turnOn = false;
            }
            if (textVar.length > 250) {
                alert("Custom message is too long. Must be 250 characters or less.")
            }
            else{
                //call the functiono in ajax_handler.php to write the lower custom message 2, update the marquee, and add a status message
                $.post("ajax_handler.php", {action: 'custT3', text: textVar, on: turnOn}, function(data){
                    //if (data){
                    //    status = "Error sending Lower Custom Message 2: " + data;
                    //}
                    reloadPage();
                 })
            }
    }
    </script>
    <script type="text/javascript">
        function refreshDisplay () {
            //returns void
            $.post("ajax_handler.php", {action: 'refresh', varChange: 'false', alert: 'true'}, function(data){
                //if (data){
                //    alert("Error refreshing display: " + data);
                //}
            })
            $.post("ajax_handler.php", {action: 'status', status: 'Display has been refreshed'}, function(data){
                //if (data){
                //    alert("Error refreshing display: " + data);
                //}
                reloadPage();
            })
        }
    </script>
    <script type="text/javascript">
        //Function is to clear stored data
        function clearData() {
            //returns void
            var c = confirm("Are you sure you want to clear all messages on the display?");
            
            if (c==true) {
                $.post("ajax_handler.php", {action: 'clear'}, function(data){
                    //if (data){
                    //    message = "Error clearing display: " + data;
                    //}
                    reloadPage();
                })
            }
            //else{
            //    alert("Display was not cleared.");
            //}
        }
    </script>
    <script type="text/javascript">
        //Function is to show configuration on display
        function showConfig() {
            //returns void
            var c = confirm("Toggle configuration display?");
            if (c==true) {
                $.post("ajax_handler.php", {action: 'toggleConfig'}, function(data){
                    //if (data){
                    //    message = "Error toggling display: " + data;
                    //}
                    reloadPage();
                })
            }
            
        }
    </script>
    <script type="text/javascript">
        //the following function reloads the page
        function reloadPage() {
            //returns void
            window.location.reload(true);
        }
    </script>
    <script type="text/javascript">
        //the following sets color of text
        function getColor(boolIn) {
            //returns string
            if (boolIn){
                return "auto-style6";
            }
            else{
                return "auto-style7";
            }
        }
    </script>
	<style type="text/css">
	.auto-style2 {
		font-size: large;
	}
	.auto-style3 {
		text-align: center;
	}
	.auto-style4 {
		text-align: left;
	}
	.auto-style5 {
		font-size: medium;
	}
        .green-style {
		COLOR: green;
	}
        .red-style {
		COLOR: red;
	}
	.auto-style9 {
		border-width: 0px;
	}
	.auto-style11 {
		border-left-style: solid;
		border-left-width: 1px;
		border-right-style: solid;
		border-right-width: 1px;
		border-top-style: solid;
		border-top-width: 1px;
	}
	.auto-style12 {
		border-left-style: solid;
		border-left-width: 1px;
		border-right-style: solid;
		border-right-width: 1px;
		border-bottom-style: solid;
		border-bottom-width: 1px;
	}
	.auto-style13 {
		border-style: solid;
		border-width: 1px;
		padding: 1px 4px;
	}
        
	.auto-style14 {
		border-left-style: solid;
		border-left-width: 1px;
		border-right-style: solid;
		border-right-width: 1px;
		border-top-style: solid;
		border-top-width: 1px;
		text-align: center;
	}
        
	.auto-style15 {
		vertical-align: top;
	}
        
	.auto-style16 {
	border-right-style: solid;
	border-right-width: 1px;
}
.auto-style17 {
	border-right-width: 0px;
	border-top-width: 0px;
	border-bottom-width: 0px;
		margin-right: 0px;
	}
.auto-style19 {
	border-style: solid;
	border-width: 1px;
}
        
	</style>
</head>
<body BGCOLOR="#CEE3F6">
    <form name="refreshDisplayForm" action="" method="get">
        <div class="auto-style3">
			<span class="auto-style2">Welcome to the ACAC Marquee Controller!<br></span>
			Unit Name<span class="auto-style5">: <?=$ID?></span><br>
			<br>
			<input name="refreshFormButton" title="Will Re-Send all data acquired to the display. Only use if issues are noticed" value="Refresh Display" onclick="refreshDisplay()" type="button">
        	<input name="refreshPageButton" title="Refresh current web-page (Will update current display values)" value="Refresh Page" onclick="reloadPage()" type="button">
			<br>
        	<input name="clearDataButton" title="Clear all messages from display. This resumes the default theme and shows web address. Use when starting up before a meet. ALL MESSAGES WILL BE LOST!" value="Clear Data" onclick="clearData()" type="button">
    		<input name="displayConfigButton" title="<?=$buttonTip?>" value="<?=$buttonDisp?>" onclick="showConfig()" type="button"></div>
		<div class="auto-style3">
			<strong><br>Guidelines:</strong><br>
                        1. Enter desired message in 
			text box, then press submit or the checkbox.<br>
                        2. Turn on or off each message by 
			the check box.<br>3. Refreshing the web page will 
			populate text boxes with current message values.<br>
                        4. If there is 
			an issue with a message displayed, it may be resolved by clicking 
			&quot;Refresh Display&quot;.<br><br>
						<br></div>
						

    	<table style="width: 100%">
			<tr>
				<td class="auto-style3" style="width: 50%"><strong>Currently 
				Displayed:</strong></td>
				<td class="auto-style3">
                                    <strong>
                                <?php
                                if(isset($statusArray[0])){
                                    echo ("Status Messages:");
                                }?>
                                    </strong></td>
			</tr>
			<tr>
				<td class="auto-style3" style="width: 50%">
				<table style="width: 100%" class="auto-style17">
					<tr>
						<td style="width: 64px; height: 26px;" class="auto-style16"><span class="auto-style15">Top</span>:</td>
						<td class="auto-style19" style="height: 26px; width: 567px;">
                                                <?php
                                                $count = 0;
                                                if (isset($topArray[0])){
                                                    foreach ($topArray as $message){
                                                        if ($count > 0){
                                                            echo ("<br>");
                                                        }
                                                        echo ($message);
                                                        $count += 1;
                                                    }
                                                }
                                                ?>
                                                
					</tr>
					<tr>
						<td style="width: 64px" class="auto-style16"><span class="auto-style15">
						Bottom</span>:</td>
						<td class="auto-style19" style="width: 250px">
                                                    <?php
                                                    if (isset($bottomArray[0])){
                                                        $count = 0;
                                                        foreach ($bottomArray as $message){
                                                            if ($count > 0){
                                                                echo ("<br>");
                                                            }
                                                            echo ($message);
                                                            $count += 1;
                                                        }
                                                    }
                                                    ?>
                                                </td>
					</tr>
				</table>
				</td>
				<td class="auto-style3">
                                    <?php
                                    if (isset($statusArray[0])){
                                        $count = 0;
                                        foreach ($statusArray as $status){
                                            if ($count > 0){
                                                echo ("<br>");
                                            }
                                            echo ($status);
                                            $count += 1;
                                        }
                                    }
                                    ?>
			</tr>
		</table>
						

    </form>
    <div>
    <form class="auto-style5">
    	<div class="auto-style3">
    	<strong>Top Line:</strong>
    </div>
    </form>
    
    
    <table style="width: 100%" class="auto-style9">
		<tr>
			<td class="auto-style11" style="width: 50%"><form action="" method="get" name="themeForm">
				<div class="auto-style3">
		
    <p class="auto-style3"><strong>Time:</strong>
        <input name="timeOn" value="<?=$timeTog?>" type="button" onClick="timeResult()">        
                - <span class="<?=$timeColor?>"><?=$timeOn?></span></p>
                <strong>Theme Message:</strong>
        <input name="themeOn" value="<?=$themeTog?>" type="button" onClick="themeResult(this.form,true)">        
                - <span class="<?=$themeColor?>"><?=$themeOn?></span><br>
        
        <input name="themeIn" value="<?=$themeMsg?>" size="21" maxlength="250" type="text">
        <input name="themeButton" value="Submit" onclick="themeResult(this.form,false)" type="button">
    			</div>
    </form>
        
    <form name="customTForm1" action="" method="get">
        <div class="auto-style3">
			<strong>Custom Message 1:</strong>
        <input name="cust1On" value="<?=$custT1Tog?>" type="button" onClick="custTResult1(this.form,true)"> -
        <span class="<?=$custT1Color?>"><?=$custT1On?><br></span>
        
        <input name="custTIn" value="<?=$custT1Msg?>" size="21" maxlength="250" type="text">
        <input name="custTButton" value="Submit" onclick="custTResult1(this.form,false)" type="button">
    	</div>
    </form>
    <form name="customTForm2" action="" method="get">
        <div class="auto-style3">
			<strong>Custom Message 2:</strong>
        <input name="cust2On" value="<?=$custT2Tog?>" type="button" onClick="custTResult2(this.form,true)"> -
        <span class="<?=$custT2Color?>"><?=$custT2On?><br></span>
        
        <input name="custTIn" value="<?=$custT2Msg?>" size="21" maxlength="250" type="text">
        <input name="custTButton" value="Submit" onclick="custTResult2(this.form,false)" type="button">
		</div>
    </form>


    
</td>
			<td class="auto-style14"><form name="scoreForm" action="" method="get">
        <div class="auto-style4">
			<strong>Team Scores:</strong>
        <input name="scoreOn" value="<?=$scoreTog?>" type="button" onClick="scoreResult(this.form,true)"> -
        <span class="<?=$scoreColor?>"><?=$scoreOn?><br></span>
        
		<div class="auto-style4">
        
        Show score through event: <input name="eventBox" value="<?=$event?>" maxlength="3" size="3" type="text"></div>
		<p class="auto-style4">
        Team: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; Score: <br>
        <input name="name1box" value="<?=$team1?>" maxlength="200" type="text">
        <input name="score1box" value="<?=$score1?>" maxlength="8" size="6" type="text"></p>
		<p class="auto-style4">
        <input name="name2box" value="<?=$team2?>" maxlength="200" type="text">
        <input name="score2box" value="<?=$score2?>" maxlength="8" size="6" type="text"></p>
		<p class="auto-style4">
        <input name="name3box" value="<?=$team3?>" maxlength="200" type="text">
        <input name="score3box" value="<?=$score3?>" maxlength="8" size="6" type="text"></p>
		<p class="auto-style4">
        <input name="name4box" value="<?=$team4?>" maxlength="200" type="text">
        <input name="score4box" value="<?=$score4?>" maxlength="8" size="6" type="text">
        <input name="teamsButton" value="Submit" onclick="scoreResult(this.form,false)" type="button">
    
</p></div></form>
</td>
		</tr>
		<tr>
			<td class="auto-style12" style="width: 50%">&nbsp;</td>
			<td class="auto-style12">&nbsp;</td>
		</tr>
	</table>
    </div>
    <div class="auto-style13">
    <div class="auto-style4">
    	<div class="auto-style3">
    <br>
        <span class="auto-style5"><strong>Bottom Line: </strong></span>
    <br>
    	</div>
    </div>
    <form name="clerkForm" action="" method="get">
        <div class="auto-style3">
			<strong>Clerk of course event call:</strong>
        <input name="clerkOn" value="<?=$clerkCallTog?>" type="button" onClick="clerkResult(this.form,true)"> -
        <span class="<?=$clerkCallColor?>"><?=$clerkOn?><br></span>
        
        Events  
        <input name="event1" value="<?=$clerkEvent1?>" size="16" maxlength="200" type="text">
        to clerk
        <input name="custTButton" value="Submit" onclick="clerkResult(this.form,false)" type="button">
		</div>
    </form>
    
    <table style="width: 100%">
		<tr>
			<td style="width: 50%"><form name="customClerkForm" action="" method="get">
        <div class="auto-style3">
			<strong>Clerk Note/Cust. Message 1:</strong>
        <input name="custClerkOn" value="<?=$clerkMsgTog?>" type="button" onClick="custClerkResult(this.form,true)"> -
        <span class="<?=$clerkMsgColor?>"><?=$clerkMsgOn?><br></span>
        <input name="custTIn" value="<?=$clerkMsg?>" size="21" maxlength="250" type="text">
        <input name="custTButton" value="Submit" onclick="custClerkResult(this.form,false)" type="button">
		</div>
    </form>
</td>
			<td><form name="customMessageForm3" action="" method="get">
        <div class="auto-style3">
			<strong>Custom Message 2:</strong>
        <input name="custOn" value="<?=$custT3Tog?>" type="button" onClick="custBResult2(this.form,true)">-
        <span class="<?=$custT3Color?>"><?=$custT3On?><br></span>
        <input name="custTIn" value="<?=$custT3Msg?>" size="21" maxlength="250" type="text">
        <input name="custTButton" value="Submit" onclick="custBResult2(this.form,false)" type="button">
    	</div>
    </form>
</td>
		</tr>
	</table>
    
    	<br></div>
	<p>Many thanks to those who contributed to making this project a success:</p>
	<ul>
		<li>GE Intelligent Platforms for donating the marquee displays</li>
		<li>Benjamin Brust</li>
		<li>Bob Brust</li>
		<li>Greg Faust</li>
	</ul>
	<p>Marquee website created by Benjamin Brust.
        <br>Version 2.35 7/24/2013</p>
	<p>Please send any comments or suggestions to
	<a href="mailto:brustb@gmail.com?subject=Swim Team Marquee Displays">Ben 
	Brust</a>.</p>
	<p>Copyright &copy 2013 Benjamin Brust.</p>
</body>
</html>