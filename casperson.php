<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	session_start();
	
	ini_set("error_reporting", E_ALL & ~E_NOTICE);
	ini_set("display_errors", 1);

	/* Setting up the connection to SQL server*/
	$user = '';
	$pass = '';
	$server = 'SERVER_NAME';
	$database = 'DATABASE_NAME';
	$connection_string = "DRIVER={SQL Server};SERVER=$server;DATABASE=$database"; 
	$db = odbc_connect($connection_string,$user,$pass);
	$access = 0;
	
	/* Exit_message session */
	if (!isset($_SESSION["exit_message"]))
	{
		$_SESSION["exit_message"] = "";
	}
	
	/*
		When the participant clicks "Submit" button after filling all the information up,
		first, it checks all details are correctly entered, then
		the information is stored as cookies for their future use,
		and the page redirect the participant to next survey page.
	*/
	date_default_timezone_set('NZ');
	
	if($_REQUEST["submitinfo"] == "Submit")
	{
		/* initialise elements */
		$upi = $_REQUEST["studentupi"];
		$gender = $_REQUEST["gender"];
		$education = $_REQUEST["education"];
		$nationality = $_REQUEST["nationality"];
		$department = $_REQUEST["department"];
		$birthday = $_REQUEST["birthday"];
		//$birthday = DateTime::createFromFormat('m/d/Y', $_REQUEST["birthday"]);
		$lastvisit = $_REQUEST["lastvisit"];
		//$lastvisit = DateTime::createFromFormat('m/d/Y', $_REQUEST["lastvisit"]);
		$service_5 = $_REQUEST["Service_5"];
		$error = "";
		
		/* Validate if all participant's details are entered correctly*/
		// A length of UPI must be equal to or longer than 6
		if (strlen($upi) < 5)
			$error .= "* Your UPI is incorrect!   ";
		// Initial value for gender is 'select gender'
		if ($gender == "Select Gender")
			$error .= "* Please select your gender!   ";
		// Initial value for education is 'select education'
		if ($education == "Select Education")
			$error .= "* Please select your education!   ";
		// Initial value for department is 'select department'
		if ($education == "Select Department")
			$error .= "* Please select your department!   ";
		// Initial value for nationality is 'select nationality'
		if ($nationality == "Select Nationality")
			$error .= "* Please select your nationality!   ";
		// Initial date of birth is null
		if ($birthday == "" || (strtotime($birthday) >= strtotime('today')))
			$error .= "* Please select your date of birth!   ";
		// Initial last visit date is null
		if ($lastvisit == "" || (strtotime($lastvisit) > strtotime('today')))
			$error .= "* Please select your last visit date!   ";
		// Initial cafe is null
		if ($service_5 == "")
			$error .= "* Please select the cafe!";
		
		/* If there is no error, continue to next page, otherwise, show an pop-up window */
		if (strlen($error) == 0)
		{
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false)
			{
				/* Dates change format */
				$arr= explode("/", $birthday);
				$birthday = $arr[2]."-".$arr[1]."-".$arr[0];
				$arr= explode("/", $lastvisit);
				$lastvisit = $arr[2]."-".$arr[1]."-".$arr[0];
			}
			
			/* Store the details */
			$_SESSION["upi"] = $upi;
			$_SESSION["gender"] = $gender;
			$_SESSION["education"] = $education;
			$_SESSION["department"] = $department;
			$_SESSION["nationality"] = $nationality;
			$_SESSION["birthday"] = $birthday;
			$_SESSION["last_visit"] = $lastvisit;
			$_SESSION["cafe"] = $service_5;
			$_SESSION["refresh"] = TRUE;
			
			$birthdaynew = new DateTime($birthday);
			$birthday = $birthdaynew->format('Y/m/d');
			//$birthday = DateTime::createFromFormat('Y-m-d', $_REQUEST["birthday"]);
			if ($access == 1) { $birthday = $birthdaynew->format('Y-m-d'); }
			$lastvisitnew = new DateTime($lastvisit);
			$lastvisit = $lastvisitnew->format('Y/m/d');
			//$lastvisit = DateTime::createFromFormat('Y-m-d', $_REQUEST["lastvisit"]);
			
			/* Batch - how many times a participant do this survey*/
			$batch = odbc_exec($db, "SELECT Count(*) AS counter FROM SurveyForPersonCafe WHERE Participant_ID = '" . $upi . "'");
			$res = odbc_fetch_array($batch);
			$_SESSION["batch"] = $res['counter'] + 1;
						
			/* Randomly Generated number 
				- Odd: Do all the questions in database
				- Even: Do CAS (category, subcategory, choices)
			*/
			//$_SESSION["randomnum"] = rand(1, 2);
			/*
				1. Compare with the participant's UPI if he/she has done mode 1, then do mode 2 this time, or vice versa
				2. if it is the participant's first attempt, then, compare the count of random_number 1 and random_number 2 in Participant table
			*/
			$_SESSION["randomnum"] = 2;
			$random1 = odbc_exec($db, "SELECT Count(*) AS counter FROM Participant WHERE Participant_ID = '" . $upi . "' AND random_number = 1");
			$res1 = odbc_fetch_array($random1);
			$count1 = $res1['counter'];

			$random2 = odbc_exec($db, "SELECT Count(*) AS counter FROM Participant WHERE Participant_ID = '" . $upi . "' AND random_number = 2");
			$res2 = odbc_fetch_array($random2);
			$count2= $res2['counter'];
			if ($count1 < $count2)
				$_SESSION["randomnum"] = 1;
			if ($count1 == 0 && $count2 == 0)
			{
				$find1 = odbc_exec($db, "SELECT Count(*) AS counter FROM Participant WHERE random_number = 1");
				$res1 = odbc_fetch_array($find1);
				$find2 = odbc_exec($db, "SELECT Count(*) AS counter FROM Participant WHERE random_number = 2");
				$res2 = odbc_fetch_array($find2);
				if ($res1['counter'] < $res2['counter'])
					$_SESSION["randomnum"] = 1;
			}
			
			/* Check if the participant data exists in the database */
			$qry = "SELECT Count(*) AS counter FROM Participant 
					WHERE Participant_ID like '".$upi."'";
			$queryresult = odbc_exec($db,$qry);
			$res = odbc_fetch_array($queryresult);
			$random = $_SESSION["randomnum"];
			if ($res['counter'] > 0)
			{
				// Update Participant Table
				$qry = "UPDATE Participant
						SET Gender='$gender', Date_of_Birth='$birthday', random_number=$random 
						WHERE Participant_ID like '".$upi."'";
						
				$queryresult = odbc_exec($db,$qry);
			}
			else
			{			
				// Insert data to Participant Table
				$qry = "INSERT INTO Participant
						(Participant_ID, Gender, Date_of_Birth, random_number)
						VALUES ('$upi', '$gender', '$birthday', $random)";
				$queryresult = odbc_exec($db,$qry);
			}
		
			/* Redirect to next survey page */
			header("Location:cassurvey.php");
		}
		else
		{
			/* Show a validation error message in a pop up window */
			/* This is done by JavaScript instead */
		}
	}
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Customer Satisfaction Survey</title>
<meta name="Keywords" content=""/>
<meta name="Description" content="" />
<link href="default.css" rel="stylesheet" type="text/css" />
<!-- Connect to JavaScript file in order to use functions -->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.2.min.js"></script>
<script src="scripts/myscript.js"></script>
</head>

<body>
<form name="survey3" method="post" action="casperson.php" onsubmit="return input_validation_tf();">
<div id="header">
	<h1>Customer Satisfaction for Cafés</h1>
	<h2>&nbsp;</h2>
</div>
	<div style="clear: both;">&nbsp;</div>
<div id="content">
	<div><img src="images/main.jpg" alt=""/></div>
	<div align="left"></div>
	<div align="left" id="colTwo">
	  <h2>Welcome to Our Survey</h2>
	  <p>&nbsp;</p>
	  <p><strong>Please fill in the  blanks:</strong>          </p>
<p>&nbsp;</p>
        <p><strong>Student UPI</strong><br />
          <input type="text" size="25" id="upi" name="studentupi" value="<?php echo $upi;?>" />
		  
          <br />
      </p>
<p><strong>Gender</strong><br />
          <select name="gender" id="gender">
            <option value="select gender">Select Gender</option>
			<option value="male" <?php if ($gender == 'male') echo ("selected");?>> Male </option>
			<option value="female" <?php if ($gender == 'female') echo ("selected");?>> Female </option>
          </select>
		  
      </p>
	  <!-- EDUCATION -->
      <p><strong>Education</strong></strong><br />
          <select name="education" id="education" selected="<?php echo $education;?>">
            <option value="select education">Select Education</option>
			<?php
				$qry = "SELECT ID, Education_Description FROM Education";
				$queryresult = odbc_exec($db,$qry);
				
				while ($data = odbc_fetch_array($queryresult))
				{
					echo "
						<option value='".$data["Education_Description"]."'>
						".$data["Education_Description"]."
						</option>
						";
					$i++;
				}
			?>
          </select>
		  
      </p>
	  <!-- DEPARTMENT -->
      <p><strong>Department</strong><br />
          <select name="department" id="department" selected="<?php echo $department;?>">
            <option value="select department">Select Department</option>
			<?php
				$qry = "SELECT ID, Department_Description FROM Department";
				$queryresult = odbc_exec($db,$qry);
				
				while ($data = odbc_fetch_array($queryresult))
				{
					echo "
						<option value='".$data["Department_Description"]."'>
						".$data["Department_Description"]."
						</option>
						";
					$i++;
				}
			?>
          </select>
		 
      </p>
	  <!-- Nationality -->
      <p><strong>Nationality </strong><br />
          <select name="nationality" id="nationality" selected="<?php echo $nationality;?>">
            <option value="select nationality">Select Nationality</option>
			<?php
				$qry = "SELECT ID, Nationality_Description FROM Nationality";
				$queryresult = odbc_exec($db,$qry);
				
				while ($data = odbc_fetch_array($queryresult))
				{
					echo "
						<option value='".$data["Nationality_Description"]."'>
						".$data["Nationality_Description"]."
						</option>
						";
					$i++;
				}
			?>
          </select>
		  
      </p>
	  <!-- Date of Birth (Date) -->
	  <p><strong>Date of Birth: Day/Month/Year (eg. 25/12/1995)</strong><br />
          <input type="date" size="25" name="birthday" id="birthday" value="<?php echo $birthday;?>"/>
		  
      </p>
	  
	  <!-- Last Visit (Date) -->
      <p><strong>Last Visit (Date): Day/Month/Year (eg. 25/12/2014)</strong><br />
      <input type="date" size="25" name="lastvisit" id="lastvisit" value="<?php echo $lastvisit;?>"/>
	  
      </p>
  <p>&nbsp;</p>
        <p>&nbsp;</p>
<p>&nbsp;</p>
  </div>
	<div style="clear: both;">
	<!-- Select Cafe -->
	  <p>Please select a Café :
	  
	  </p>
      <p>
		<?php
			$qry = "SELECT Cafe_ID, Cafe_Name FROM Cafe";
			$queryresult = odbc_exec($db,$qry);
			
			while ($data = odbc_fetch_array($queryresult))
			{
				echo "
					<input type='radio' name='Service_5' id='Service_5' value='".$data["Cafe_Name"]."'/>
					".$data["Cafe_Name"];
			}
		 ?>
      </p>
<p>&nbsp; </p>
<p>
  <input type="submit" name="submitinfo" value="Submit" onclick="input_validation();"/> 
    </p>
      <p align="right"></p>
      <p></p>
	</div>
</div>
<div id="footer">
	<p>Copyright 2015</p>
</div>
</form>
</body>
</html>
