<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	session_start();
	//ini_set("error_reporting", E_ALL & ~E_NOTICE);
	error_reporting(E_ERROR | E_PARSE);	// Ignore warning messages
	ini_set("display_errors", 1);
	
	/* Setting up the connection to SQL server*/
	$user = '';
	$pass = '';
	$server = 'SERVER_NAME';
	$database = 'DATABASE_NAME';
	$connection_string = "DRIVER={SQL Server};SERVER=$server;DATABASE=$database"; 
	$db = odbc_connect($connection_string,$user,$pass);
	$access = 0;
	
	date_default_timezone_set('NZ');
	
	/* Get a name of Cafe from the previous page, 
		so we can show an appropriate cafe image in this page */
	$cafe = $_SESSION["cafe"];
	
	/* We need to push all results to the database */
	/* The results of each question answered */
	$inarray = array();
	$breakup = explode(" ", $_SESSION["result"]);
	foreach($breakup as $element) {
		$element = trim($element);
		if ($element != " " && $element != "")
			$inarray[] = $element;
	}
	
	/* Exit Message */
	if (isset($_POST['reason'])) { 
	   $_SESSION["exit_message"] = $_POST["reason"];
	}
	
	
	/* Other data */
	// Check with the database and PUSH!
	else if ($_SESSION["cafe"] != null && $_SESSION['last_visit'] != null
		&& $_SESSION["upi"] != null && $_SESSION["batch"] != null
		&& $_SESSION["education"] != null && $_SESSION["nationality"] != null
		&& $_SESSION["department"] != null)
	{
		// CafeID
		$qry = "SELECT Cafe_ID, Cafe_Name FROM Cafe 
				WHERE Cafe_Name like '".$_SESSION["cafe"]."'";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$cafeID = $data["Cafe_ID"];
		// Current Date
		$current = new DateTime();
		$currentnew = $current->format('Y/m/d');
		if ($access == 1) {$currentnew = $current->format('Y-m-d');}
		// Last Visit
		$lastvisit = new DateTime($_SESSION['last_visit']);
		//$lastvisitnew = DateTime::createFromFormat('d/m/Y', $_SESSION['last_visit']);
		$lastvisitnew = $lastvisit->format('Y/m/d');
		if ($access == 1) {$lastvisitnew = $lastvisit->format('Y-m-d');}
		// Participant ID
		$part_ID = $_SESSION["upi"];
		// Batch Number
		$batch = 0;
		if (isset($_SESSION["batch"]))
			$batch = $_SESSION["batch"];
		// Gender
		if (!isset($_SESSION["gender"]))
			$_SESSION["gender"] = "male";
		$gender = $_SESSION["gender"];
		// Date of Birth
		if (!isset($_SESSION["birthday"]))
			$_SESSION["birthday"] = "2015-01-01";
		$birthdaynew = new DateTime($_SESSION["birthday"]);
		$birthday = $birthdaynew->format('Y/m/d');
		//$birthday = DateTime::createFromFormat('d/m/Y', $_SESSION['birthday']);
		if ($access == 1) { $birthday = $birthdaynew->format('Y-m-d'); }
		// Education
		if (!isset($_SESSION["education"]))
			$_SESSION["education"] = "undergraduate";
		$qry = "SELECT ID, Education_Description FROM Education 
				WHERE Education_Description like '".$_SESSION["education"]."'";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$educationID = $data["ID"];
		// Nationality
		if (!isset($_SESSION["nationality"]))
			$_SESSION["nationality"] = "Bahrain";
		$qry = "SELECT ID, Nationality_Description FROM Nationality 
				WHERE Nationality_Description like '".$_SESSION["nationality"]."'";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$nationalityID = $data["ID"];
		if ($nationalityID == null) $nationalityID = 1;
		// Department
		if (!isset($_SESSION["department"]))
			$_SESSION["department"] = "Information_System";
		$qry = "SELECT ID, Department_Description FROM Department 
				WHERE Department_Description like '".$_SESSION["department"]."'";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$departmentID = $data["ID"];
		// Reason for quiting
		$reason = $_SESSION["exit_message"];
		// Random Number
		$random = 0;
		if (isset($_SESSION["randomnum"]))
			$random = $_SESSION["randomnum"];
		
		/* Check if the participant data exists in the database */
		$qry = "SELECT Count(*) AS counter FROM Participant 
				WHERE Participant_ID like '".$part_ID."'";
		$queryresult = odbc_exec($db,$qry);
		$res = odbc_fetch_array($queryresult);
		if ($res['counter'] > 0)
		{
			// Update Participant Table
			$qry = "UPDATE Participant
					SET Gender='$gender', Date_of_Birth='$birthday', Education_ID=$educationID,
					Nationality_ID=$nationalityID, CafeID=$cafeID, Department_ID=$departmentID,
					Last_visit='$lastvisitnew', Reason_for_quiting='$reason', random_number=$random 
					WHERE Participant_ID like '".$part_ID."'";
					
			$queryresult = odbc_exec($db,$qry);
		}
		else
		{			
			// Insert data to Participant Table
			$qry = "INSERT INTO Participant
					(Participant_ID, Gender, Date_of_Birth, Education_ID, Nationality_ID,
						CafeID, Department_ID, Last_visit, Reason_for_quiting, random_number)
					VALUES ('$part_ID', '$gender', '$birthday', $educationID, $nationalityID,
						$cafeID, $departmentID, '$lastvisitnew', '$reason', $random)";
			$queryresult = odbc_exec($db,$qry);
		}
		// Insert data to SurveyForPersonCafe table
		$qry = "INSERT INTO SurveyForPersonCafe
				(Date_Completed, Last_visit, CafeID, Participant_ID, Batch_number)
				VALUES ('$currentnew', '$lastvisitnew', $cafeID, '$part_ID', $batch)";
		$queryresult = odbc_exec($db,$qry);

		// Create an empty array
		$result_arr = array_fill(0, 200, NULL);
		$quesandresults = explode(" ", $_SESSION["result"]);
		$arr_cnt = 1; $quesnum = 0;
		foreach($quesandresults as $item) {
			$item = trim($item);
			if ($item != " " && $item != "")
			{
				if ($arr_cnt % 2 == 1)
					$quesnum = $item;
				else
				{
					$result_arr[$quesnum] = $item;
				}
			}
			$arr_cnt++;
		}
		
		/* Check if the participant data exists in the results database */
		$resqry = "SELECT result_ID FROM results 
				WHERE Participant_ID = '".$part_ID."' AND Gender = '".$gender."' 
				AND Batch_number = ".$batch." AND Education_ID = ".$educationID." 
				AND Department_ID = ".$departmentID." AND CafeID = ".$cafeID;
		$resqueryresult = odbc_exec($db,$resqry);
		$res = odbc_fetch_array($resqueryresult);
		$resID = $res["result_ID"];
	
		if ($resID != null && $resID != 0)
		{
			/* Update results Table - 1st: 1 to 100, 2nd: 101 to 200 */
			$qry = "UPDATE results
					SET Participant_ID='$part_ID', Gender='$gender', Date_of_Birth='$birthday', 
					Last_visit='$lastvisitnew', Education_ID=$educationID,
					Nationality_ID=$nationalityID, Department_ID=$departmentID, CafeID=$cafeID,
					Date_Completed='$currentnew', Reason_for_quiting='$reason', Batch_number=$batch";
			for ($j = 1; $j < 101; $j++)
			{
				if ($result_arr[$j] == null)
					$result_arr[$j] = 0;
				$qry .= ", question".$j."=".$result_arr[$j];
			}
			$qry .=	" WHERE result_ID = ".$resID;
			$queryresult = odbc_exec($db,$qry);
			
			$qry = "UPDATE results
					SET Participant_ID='$part_ID', Gender='$gender', Date_of_Birth='$birthday', 
					Last_visit='$lastvisitnew', Education_ID=$educationID,
					Nationality_ID=$nationalityID, Department_ID=$departmentID, CafeID=$cafeID,
					Date_Completed='$currentnew', Reason_for_quiting='$reason', Batch_number=$batch";
			for ($j = 101; $j < 201; $j++)
			{
				if ($result_arr[$j] == null)
					$result_arr[$j] = 0;
				$qry .= ", question".$j."=".$result_arr[$j];
			}
			$qry .=	" WHERE result_ID = ".$resID;
			$queryresult = odbc_exec($db,$qry);
		}
		else
		{			
			// Insert data to results Table
			$qry = "INSERT INTO results
					(Participant_ID, Gender, Date_of_Birth, Last_visit, Education_ID, Nationality_ID,
						Department_ID, CafeID, Date_Completed, Reason_for_quiting, Batch_number";
			for ($j = 1; $j < 201; $j++)
			{
				$qry .= ", question".$j;
			}
			$qry .= ")
					VALUES ('$part_ID', '$gender', '$birthday', '$lastvisitnew', $educationID, $nationalityID,
						 $departmentID, $cafeID, '$currentnew', '$reason', $batch";
			for ($j = 1; $j < 201; $j++)
			{
				if ($result_arr[$j] == null)
					$result_arr[$j] = 0;
				$qry .= ", ".$result_arr[$j];
			}
			$qry .= ")";
			$queryresult = odbc_exec($db,$qry);
		}

		session_destroy();
	}
?>
<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 2.5 License
-->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Customer Satisfaction Survey</title>
<meta name="Keywords" content="" />
<meta name="Description" content="" />
<link href="default.css" rel="stylesheet" type="text/css" />
<!-- Connect to JavaScript file in order to use functions -->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.2.min.js"></script>
<script src="scripts/myscript.js"></script>
</head>
<body>
<div id="header">
	<h1>Customer Satisfaction for Cafés</h1>
</div>
	<div style="clear: both;">&nbsp;</div>
<div id="content">
	<!-- Change a cafe image depending on what the participant chose previously -->
	<div>
		<?php if (strpos(($cafe), "Excel") !== false) { ?>
		<img src="images/excel_cafe.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "Strata") !== false) { ?>
		<img src="images/strata1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe),"kebab") !== false) { ?>
		<img src="images/unikebab1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "sushi") !== false) { ?>
		<img src="images/sushi.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "Slurp") !== false) { ?>
		<img src="images/slurp1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else { ?>
		<img src="images/main.jpg" alt="" name="coverimage" width="100%"/>
		<?php } ?>
	</div>
	
	<div id="colOne">
		<div id="menu1">
			<ul>
				<li id="menu-01"><a href="javascript:home();">Home</a></li>
				<li id="menu-04"><a href="javascript:about();">About Survey</a></li>
				<li id="menu-05"><a href="javascript:contactus();">Contact Us</a></li>
			</ul>
		</div>
		<div class="margin-news">
			<h2>&nbsp;</h2>
</div>
	</div>

  <div id="colTwo">
  <h2>&nbsp;</h2>
		<p>&nbsp;</p>
		<h2>Thank you for your time!</h2>
	<tr>
  <p>&nbsp;</p>
</div>
	<div style="clear: both;">&nbsp;</div>
</div>
<div id="footer">
	<p>Copyright &copy; 2015 </p>
</div>
</body>
</html>
