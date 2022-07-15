<?php
    $path = "ID.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $ID = $array[0];
    }
    $path = "defTheme.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $defTheme = $array[0];
    }
    $path = "cenLev.txt";
    if (file_exists($path)){
        $array = preg_split("/\n/", file_get_contents($path));
        $cenLev = $array[0];
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd"
    >
<html lang="en">
<head>
    <title>Administrative</title>
    <script src="jquery-1.10.0.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        //Function is to write device ID
        function setID (form) {
            setPassword = 'raspberry';
            var password = prompt("Please enter password");
            var ID = form.IDin.value;
            if (password == setPassword) {
                $.post("ajax_handler.php", {action: 'setID', ID: ID}, function(data){
                    if (data) {
                        alert("Fail: " + data);
                    }
                    else{
                        alert("Success: ID is " + ID);
                    }
                 })
            }
            else{
                alert("Incorrect password");
            }
        }
    </script>
    <script type="text/javascript">
        //Function is to write device default theme
        function setdefTheme (form) {
            setPassword = 'raspberry';
            var password = prompt("Please enter password");
            var theme = form.Themein.value;
            if (password == setPassword) {
                $.post("ajax_handler.php", {action: 'setdefTheme', theme: theme}, function(data){
                    if (data) {
                        alert("Fail: " + data);
                    }
                    else{
                        alert("Success: Default theme is " + theme);
                    }
                 })
            }
            else{
                alert("Incorrect password");
            }
        }
    </script>
    <script type="text/javascript">
        //Function is to write device censorship level (typically 3)
        function setCenLev (form) {
            setPassword = 'raspberry';
            var password = prompt("Please enter password");
            var cenLev = form.cenLevin.value;
	    try {
		cenLevInt = parseInt(cenLev)
		if (cenLevInt >= 1) {
		    if (password == setPassword) {
			$.post("ajax_handler.php", {action: 'setcenLev', cenLevInt: cenLev}, function(data){
			    if (data) {
				alert("Fail: " + data);
			    }
			    else{
				alert("Success: Censorship level is " + cenLevInt);
			    }
			 })
		    }
		    else{
			alert("Incorrect password");
		    }
		}
	    } catch(e) {
		alert("Failure to change: Must be integer.");
	    }
	    
        }
    </script>
        <script type="text/javascript">
        //Function is to write device censorship level (typically 3)
        function sysMsg (string) {
            setPassword = 'raspberry';
            var password = prompt("Please enter password");
	    
	    if (password == setPassword) {
		$.post("ajax_handler.php", {action: 'sysMsg', string: string}, function(data){
		    if (data) {
			alert("Fail: " + data);
		    }
		    else{
			alert("Success: sysMsg.txt - " + string);
		    }
		 })
	    }
	    else{
		alert("Incorrect password");
	    }

	    
        }
    </script>
</head>
    <!--Copyright 2013 Benjamin Brust.-->
    

<body>
    <form name="set ID" action="" method="get">
	<strong>Set ID:</strong><br>
        <input name="IDin" size="20" value="<?=$ID?>" maxlength="20" type="text">
        <input name="custIDButton" value="Submit" onclick="setID(this.form)" type="button">
	
    </form>
    <form name="set Theme" action="" method="get">
	<strong>Set Default Theme:</strong><br>
        <input name="Themein" value="<?=$defTheme?>" size="21" maxlength="250" type="text">
        <input name="defTButton" value="Submit" onclick="setdefTheme(this.form)" type="button">
    </form>
    <br>
    <form name="set Censorship Level" action="" method="get">
	<strong>Set Censorship Level:</strong><br>
	Censorship level is an integer. Default is 3. <br>
	The lower number, the more strict the censorship. <br>
        <input name="cenLevin" value="<?=$cenLev?>" size="2" maxlength="2" type="text">
        <input name="cenLevButton" value="Submit" onclick="setCenLev(this.form)" type="button">
	
    </form>
    <br>
    <form name = "system messages" action = "" method = "get">
	<strong>System messages:</strong><br>
	View <a href="sysMsg.txt">sysMsg.txt</a> <br>
	<input name="showButton" value="Write status to sysMsg.txt" onclick="sysMsg('status')" type="button">
	<input name="clearButton" value="Clear sysMsg.txt" onclick="sysMsg('clear')" type="button">
	
    </form>
    <br>
    <br>
	<p>Many thanks to those who contributed to making this project a success:</p>
	<ul>
		<li>GE Intelligent Platforms for donating the marquee displays</li>
		<li>Benjamin Brust</li>
		<li>Bob Brust</li>
		<li>Greg Faust</li>
	</ul>
	Marquee website created by Benjamin Brust.
	<br>Version 2.35 7/24/2013 <br>
	Please send any comments or suggestions to
	<a href="mailto:brustb@gmail.com?subject=Swim Team Marquee Displays">Ben 
	Brust</a>.<br>
	Copyright &copy 2013 Benjamin Brust.

</body>
</html>
