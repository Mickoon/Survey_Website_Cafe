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
	
	date_default_timezone_set('NZ');
	
	/* Refresh session - This is required for verifying the user is really in this page.*/
	if (!isset($_SESSION["refresh"]))
	{
		$_SESSION["refresh"] = FALSE;
	}
	if ($_SESSION["refresh"])
	{
		header("Location:cassurvey.php");
		$_SESSION["refresh"] = FALSE;
	}
	
	/* Randomly Generated number - initially it is even number -> Do CAS */
	$randomnum = 2;	$oddeven = 2;	
	if (isset($_SESSION["randomnum"]))
	{
		$randomnum = $_SESSION["randomnum"];
	}
	else
	{
		$_SESSION["randomnum"] = $randomnum;
	}
	/* Find whether the randomly generated number is ODD number -> Do all questions */
	if ($randomnum % 2 == 1) 
	{
		$oddeven = 1;	
		/* Depth to 180, because all questions will be in a ONE page */
		$_SESSION["depth"] = 180;	
	}
	
	/* Batch number  */
	$batch = 1;
	if (isset($_SESSION["batch"]))
	{
		$batch = $_SESSION["batch"];
	}
	else
	{
		$_SESSION["batch"] = $batch;
	}
	
	/* Get a name of Cafe from the previous page, 
		so we can show an appropriate cafe image in this page */
	$cafe = $_SESSION["cafe"];	
	
	/* Depth of subcategory */
	$depth = 0;
	if (isset($_SESSION["depth"]))
	{
		$depth = $_SESSION["depth"];
	}
	else
	{
		$_SESSION["depth"] = $depth;
	}
	
	/* Constructs - in Array */
	if ($_SESSION["depth"] == 0)
	{
		$_SESSION["req_num"] = 2;	// 2 Constucts as default
		$qry = "SELECT ID, Constructs FROM Constructs 
			WHERE ID = 1";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$dataresult = $data["Constructs"];
		if ($dataresult != null && $dataresult != 0)
			$_SESSION["req_num"] = $dataresult;
	}
	else
		$_SESSION["req_num"] = 1;	// only need 1 answer in subcategories
	if (!isset($_SESSION["ctrs"]))
	{
		$_SESSION["ctrs"] = array();
	}
	
	/* Choice Questions variables */
	$_SESSION["choicequestions"] = "";
	$_SESSION["howmanytochoose"] = "";
	
	/* Survey_Result (format in "Q A Q A") */
	if (!isset($_SESSION["result"]))
	{
		$_SESSION["result"] = "";
	}
	
	/* Current processing construct */
	if (!isset($_SESSION["current_constr"]))
	{
		$_SESSION["current_constr"] = "";
	}
	
	/* Variables for saving/updating in the database*/
	// CafeID
	if (!isset($_SESSION["cafe"]))
		$_SESSION["cafe"] = "Excel";
	$qry = "SELECT Cafe_ID, Cafe_Name FROM Cafe 
			WHERE Cafe_Name like '".$_SESSION["cafe"]."'";
	$queryresult = odbc_exec($db,$qry);
	$data = odbc_fetch_array($queryresult);
	$cafeID = $data["Cafe_ID"];
	// Current Date
	$current = new DateTime();
	$currentnew = $current->format('Y/m/d');
	if ($access == 1) { $currentnew = $current->format('Y/m/d');}
	// Last Visit
	if (!isset($_SESSION["last_visit"]))
		$_SESSION["last_visit"] = "2015-01-01";
	$lastvisit = new DateTime($_SESSION['last_visit']);
	//$lastvisitnew = DateTime::createFromFormat('d/m/Y', $_SESSION['last_visit']);
	$lastvisitnew = $lastvisit->format('Y/m/d');
	if ($access == 1) { $lastvisitnew = $lastvisit->format('Y-m-d'); }
	// Participant ID
	if (!isset($_SESSION["upi"]))
		$_SESSION["upi"] = "temp123";
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
	//$birthday = DateTime::createFromFormat('d/m/Y', $_SESSION['birthday']);
	$birthday = $birthdaynew->format('Y/m/d');
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

	/*	NOTES:
		- Save User's results per this page
		
		- When User clicks a submit button:
			- Check all questions are answered
			- FIRST CATEGORY: FIND x number of questions with the lowest answer
			- SECOND ~ : FIND THE LOWEST AND CONTINUE TO THE NEXT SUBCATEGORY
			
		- What will be happened when the user clicks a back button in 2nd or 3rd category? 
			: Shows a Warning message, then if yes, go back to HOME page
	*/
	
	/* Get a relevant dataset of questions from the database, depending on the value of DEPTH */
	$queryresult = "";
	$colName = 0;
	
	/* Name ID variable (used in HTML) */
	$i = 1; 
	
	/********************/
	/* Do all questions */
	/********************/
	if ($depth == 180)	
	{
		$qry = "SELECT ID, Question FROM Survey_Questions 
				order by ID";
		$queryresult = odbc_exec($db,$qry);
	}
	/******************************************/
	/* Category - Get Questions from database */
	/******************************************/
	else if ($depth == 0)	
	{
		$belongs_to_survey = 0;
		$qry = "SELECT ID, Question FROM Survey_Questions 
				WHERE belongs_to_survey like ".$belongs_to_survey." 
				order by ID";
		$queryresult = odbc_exec($db,$qry);
	}
	/************************************************/
	/* SubCategories - Get Questions from database  */
	/************************************************/
	else if ($depth >= 1)	
	{
		$construct = $_SESSION["current_constr"];
		/* 1. Find Subcategory ID of this Question_ID in the Survey_Questions table */
		$qry = "SELECT ID, Subcategory_ID FROM Survey_Questions 
			WHERE ID like ".$construct."";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$subcategory = $data["Subcategory_ID"];
		
		/* 2. Find belongs_to_survey = $subcategory in the Survey_Questions table */
		$qry = "SELECT ID, Subcategory_ID, Question, belongs_to_survey FROM Survey_Questions 
			WHERE belongs_to_survey like ".$subcategory."";
		$queryresult = odbc_exec($db,$qry);
	}
	
	/* Check if next query is empty or not - For proceeding to Depth 1*/
	function goNextPage_depth1($db,$construct)
	{
		/* 1. Find Subcategory ID of this Question_ID in the Survey_Questions table */
		$qry = "SELECT ID, Subcategory_ID FROM Survey_Questions 
			WHERE ID like ".$construct."";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$subcategory = $data["Subcategory_ID"];
		
		/* 2. Find belongs_to_survey = $subcategory in the Survey_Questions table */
		$qry = "SELECT Subcategory_ID, belongs_to_survey FROM Survey_Questions 
			WHERE belongs_to_survey like ".$subcategory."";
		$queryresult = odbc_exec($db,$qry);
		$isempty = TRUE;
		while ($data = odbc_fetch_array($queryresult))
		{
			$isempty = FALSE;
			break;
		}
		
		if ($isempty)
		{
			/* If it is empty, delete the first construct in the array.
			   Then, check if the $_SESSION["ctrs"][0] is null or "",
			   if it is, End this survey
			*/
			$construct = array_shift($_SESSION["ctrs"]);	
			$_SESSION["depth"] = 1; 
			if ($construct == "" or $construct == null)
			{
				header("Location:casexit.php");
			}
			else
			{
				$_SESSION["current_constr"] = $construct;
				goNextPage_depth1($db,$construct);
			}
		}
		else
		{
			/* If it is not empty, Go to next page (which is still the same page, 
			   but the value of depth will be different) */
			$_SESSION["current_constr"] = $construct;
			$_SESSION["refresh"] = TRUE;
			header("Location:cassurvey.php");
		}
	}
	
	/* ************************************* */
	/* Category Submit - creating constructs */
	/* ************************************* */
	
	/* Check if the participant data exists in the results database */
	$resqry = "SELECT result_ID FROM results 
			WHERE Participant_ID = '".$part_ID."' AND Gender = '".$gender."' 
			AND Batch_number = ".$batch." AND Education_ID = ".$educationID." 
			AND Department_ID = ".$departmentID." AND CafeID = ".$cafeID;
	$resqueryresult = odbc_exec($db,$resqry);
	$res = odbc_fetch_array($resqueryresult);
	$resID = $res["result_ID"];
	
	if($_REQUEST["submitbutt"] == "Submit" && $depth == 0)
	{
		/* PHP is server-sided. Below function is conducted after JavaScript onclick function.
		   Constructs are saved in this format "c1 c2" each is divided by a space.
		   We save this set of constructs in Session Array.
		*/
		/* Save constructs in the session */
		$questions = explode(" ", $_REQUEST["questionid_construct"]);
		foreach($questions as $ques) {
			$ques = trim($ques);
			if ($ques != " " && $ques != "")
				$_SESSION["ctrs"][] = $ques;
		}
		
		/* Save each questions' answers in the session in a format of "Q A Q A " */
		$_SESSION["result"] .= $_REQUEST["results"];		
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
			
			//$_SESSION["test1"] = $qry;
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
		
		/* If you found less number of Constructs than requirement, go to choice question page */
		if (count($_SESSION["ctrs"]) < $_SESSION["req_num"]
			&& $_REQUEST["choiceques"] != ""
			&& $_REQUEST["anstochoose"] != 0)
		{
			$_SESSION["choicequestions"] = $_REQUEST["choiceques"];
			$_SESSION["howmanytochoose"] = $_REQUEST["anstochoose"];
			header("Location:caschoice_cat.php");
		}
		/* Otherwise, you should already have got enough number of constructs */
		else
		{
			/* Assign the first element of Construct, and delete it from the array */
			$construct = array_shift($_SESSION["ctrs"]);	// e.g. 71 = Question_ID
			$_SESSION["depth"] = 1; 

			goNextPage_depth1($db,$construct);
		}
	}
	/* ****************** */
	/* SubCategories Submit */
	/* ****************** */
	else if ($_REQUEST["submitbutt"] == "Submit" && $depth >= 1)
	{
		/* NOTE:
		SubCategories: depth >= 1, becomes += 1
		- Choice Question direction -> to caschoice_subcat.php
		- If only one number is found in ID:questionid_construct,
			1. Check its Subcategory_ID value in Survey_Questions table
			2. Find belongs_to_survey = $subcategory in the Survey_Questions table 
				- If nothing found, then the table is empty
		*/
		
		/* Save each questions' answers in the session in a format of "Q A Q A " */
		$_SESSION["result"] .= $_REQUEST["results"]; 
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
			
			//$_SESSION["test1"] = $qry;
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
		
		/* Go to choice question page - choice_question_subcategory */
		if ($_REQUEST["choiceques"] != ""
			&& $_REQUEST["anstochoose"] != 0
			&& $_REQUEST["questionid_construct"] == "")
		{
			$_SESSION["choicequestions"] = $_REQUEST["choiceques"];
			$_SESSION["howmanytochoose"] = $_REQUEST["anstochoose"];
			header("Location:caschoice_subcat.php");
		}
		/* Otherwise, you should already have got enough number of answer to proceed to next step */
		else
		{
			/* In this subcategory, we only have one Question ID and it must be saved in Id "quesitonid_construct" */
			$quesid = $_REQUEST["questionid_construct"];	// e.g. 72 = Question_ID
			$_SESSION["depth"]++; 
			
			/* Check if next query is empty or not */
			/* 1. Check its Subcategory_ID value in Survey_Questions table */
			$qry = "SELECT ID, Subcategory_ID, belongs_to_survey FROM Survey_Questions 
				WHERE ID like ".$quesid."";
			$queryresult = odbc_exec($db,$qry);
			$data = odbc_fetch_array($queryresult);
			$subcategoryid = $data["Subcategory_ID"];
			
			/* 2. Find belongs_to_survey = $subcategory in the Survey_Questions table */
			$qry = "SELECT ID, Subcategory_ID, belongs_to_survey FROM Survey_Questions 
				WHERE belongs_to_survey like ".$subcategoryid."";
			$queryresult = odbc_exec($db,$qry);
			$isempty = TRUE;
			while ($data = odbc_fetch_array($queryresult))
			{
				$isempty = FALSE;
				break;
			}
			
			if ($isempty)
			{
				/* If it is empty, delete the first construct in the array.
				   Then, check if the $_SESSION["ctrs"][0] is null or "",
				   if it is, End this survey
				*/
				$quesid = array_shift($_SESSION["ctrs"]);	
				$_SESSION["depth"] = 1; 
				if ($quesid == "" or $quesid == null)
				{
					header("Location:casexit.php");
				}
				else
				{
					$_SESSION["current_constr"] = $quesid;
					goNextPage_depth1($db, $quesid);
				}
			}
			else
			{
				/* If it is not empty, Go to next page (which is still the same page, 
				   but the value of depth will be different) */
				$_SESSION["current_constr"] = $quesid;
				$_SESSION["refresh"] = TRUE;
				header("Location:cassurvey.php");
			}
		}
	}
	/* ****************** */
	/* All questions Submit */
	/* ****************** */
	else if ($_REQUEST["submitbutt"] == "Submit" && $depth == 180)
	{
		/* Save each questions' answers in the session in a format of "Q A Q A " */
		$_SESSION["result"] .= $_REQUEST["results"];
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
		
		header("Location:casexit.php");
	}
?>

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
<script>
/* This function is used to confirm that the participant answers all questions */
function validation(){
	var datasize = <?php if(isset($_SESSION["datasize"])) echo $_SESSION["datasize"]; else echo 0;?>;	// datasize is 5 if there are 5 questions
	var errormsg = "Question ";

	for (var d = 1; d < datasize; d++)
	{
		// Find which radio button is selected in each question
		var name = "Service_" + d;
		var answer_options = document.getElementsByName(name);	// Radio buttons
		var answer="";
		var questionid = document.getElementById(name).value;	// Get a Question ID of the name "Service_x"
		for (var i = 0; i < answer_options.length; i++) 
		{
			if (answer_options[i].checked)
				break;
			// If we find that the user hasn't answered a particular question, show an error message
			if (i == answer_options.length-1)
			{
				if (errormsg.length > 9)
					errormsg += ", " + d;
				else
					errormsg += d;
			}	
		}
	}
	if (errormsg.length < 10)
		return true;
	else
	{
		alert(errormsg + ": Please choose your answer.");
		return false;
	}
}

function answers(){
	/* This Function aims to create the constructs */

	// CONSTRUCT number should be pre-defined
	var CONSTRUCT = <?php echo $_SESSION["req_num"];?>; 
	var constructs = [];
	document.getElementById("questionid_construct").value = "";
	var arr1 = []; // Store questions which has an answer of 1
	var arr2 = []; // Store questions which has an answer of 2
	var arr3 = []; // Store questions which has an answer of 3
	var arr4 = []; // Store questions which has an answer of 4
	var arr5 = []; // Store questions which has an answer of 5
	
	// Find a total number of questions in the page
	var datasize = <?php echo $_SESSION["datasize"]; ?>;	// datasize is 5 if there are 5 questions
	document.getElementById("results").value = "";
	for (var d = 1; d < datasize; d++)
	{
		// Find which radio button is selected in each question
		var name = "Service_" + d;
		var answer_options = document.getElementsByName(name);	// Radio buttons
		var answer="";
		var questionid = document.getElementById(name).value;	// Get a Question ID of the name "Service_x"

		for (var i = 0; i < answer_options.length; i++) 
		{
			// When we find an answer which is a checked radio button
			if (answer_options[i].checked)
			{
				answer = answer_options[i].value;	// 1, 2, 3, 4 or 5
				// Save each question's result into hidden value, and later it will be saved in Session
				document.getElementById("results").value += questionid + " " + answer + " ";
					
				// Save the checked value to its appropriate array
				switch (parseInt(answer)) 
				{
					case 1:
						arr1.push(name);	// e.g. Service_2
						break;
					case 2:
						arr2.push(name);
						break;
					case 3:
						arr3.push(name);
						break;
					case 4:
						arr4.push(name);
						break;
					case 5:
						arr5.push(name);
						break;
				}
			}
		}
	}
	
	// We find a required number of constructs
	var i = 0; 
	// Array1
	if (arr1.length == CONSTRUCT)
	{
		for (var j = 0; j < arr1.length; j++)
			constructs.push(arr1[j]);
		CONSTRUCT = 0;
	}
	else if (arr1.length < CONSTRUCT)
	{
		for (var j = 0; j < arr1.length; j++)
		{
			constructs.push(arr1[j]);
			CONSTRUCT--;
		}
		
		// Array2
		if (CONSTRUCT != 0 && arr2.length == CONSTRUCT)
		{
			for (var j = 0; j < arr2.length; j++)
				constructs.push(arr2[j]);
			CONSTRUCT = 0;
		}
		else if (CONSTRUCT != 0 && arr2.length < CONSTRUCT)
		{
			for (var j = 0; j < arr2.length; j++)
			{
				constructs.push(arr2[j]);
				CONSTRUCT--;
			}
			
			// Array3
			if (CONSTRUCT != 0 && arr3.length == CONSTRUCT)
			{
				for (var j = 0; j < arr3.length; j++)
					constructs.push(arr3[j]);
				CONSTRUCT = 0;
			}
			else if (CONSTRUCT != 0 && arr3.length < CONSTRUCT)
			{
				for (var j = 0; j < arr3.length; j++)
				{
					constructs.push(arr3[j]);
					CONSTRUCT--;
				}
				
				// Array4
				if (CONSTRUCT != 0 && arr4.length == CONSTRUCT)
				{
					for (var j = 0; j < arr4.length; j++)
						constructs.push(arr4[j]);
					CONSTRUCT = 0;
				}
				else if (CONSTRUCT != 0 && arr4.length < CONSTRUCT)
				{
					for (var j = 0; j < arr4.length; j++)
					{
						constructs.push(arr4[j]);
						CONSTRUCT--;
					}
					
					// Array5
					if (CONSTRUCT != 0 && arr5.length == CONSTRUCT)
					{
						for (var j = 0; j < arr5.length; j++)
							constructs.push(arr5[j]);
						CONSTRUCT = 0;
					}
					else if (CONSTRUCT != 0 && arr5.length < CONSTRUCT)
					{
						for (var j = 0; j < arr5.length; j++)
						{
							constructs.push(arr5[j]);
							CONSTRUCT--;
						}
					}
					else if (CONSTRUCT != 0 && arr5.length > CONSTRUCT)
					{
						var choiceques = "";
						for (var j = 0; j < arr5.length; j++)
						{
							var questionid = document.getElementById(arr5[j]).value;
							choiceques += questionid + " ";
						}
						document.getElementById("choiceques").value = choiceques;	
						document.getElementById("anstochoose").value = CONSTRUCT;	
					}
				}
				else if (CONSTRUCT != 0 && arr4.length > CONSTRUCT)
				{
					var choiceques = "";
					for (var j = 0; j < arr4.length; j++)
					{
						var questionid = document.getElementById(arr4[j]).value;
						choiceques += questionid + " ";
					}
					document.getElementById("choiceques").value = choiceques;	
					document.getElementById("anstochoose").value = CONSTRUCT;	
				}
			}
			else if (CONSTRUCT != 0 && arr3.length > CONSTRUCT)
			{
				var choiceques = "";
				for (var j = 0; j < arr3.length; j++)
				{
					var questionid = document.getElementById(arr3[j]).value;
					choiceques += questionid + " ";
				}
				document.getElementById("choiceques").value = choiceques;	
				document.getElementById("anstochoose").value = CONSTRUCT;	
			}
		}
		else if (CONSTRUCT != 0 && arr2.length > CONSTRUCT)
		{
			var choiceques = "";
			for (var j = 0; j < arr2.length; j++)
			{
				var questionid = document.getElementById(arr2[j]).value;
				choiceques += questionid + " ";
			}
			document.getElementById("choiceques").value = choiceques;	
			document.getElementById("anstochoose").value = CONSTRUCT;	
		}
	}
	else if (arr1.length > CONSTRUCT)
	{
		/* Do Choice Question
		- Give a signal that this function is over, and need to do Choice Question
			1. Choice Question includes all questions in Array1 
				<input type="hidden" id="choiceques" name="choiceques" value="1 53 71"/>
			2. A participant needs to select x number of answers in that page
				<input type="hidden" id="anstochoose" name="anstochoose" value="2"/>
			3. Signal to PHP that we need to go to Choice Question Page 
				<input type="hidden" id="ischoice" name="ischoice" value="y"/>
		- (PHP) Save constructs that are already found in this function
			// Submit function that is already existed in PHP
		- (PHP) Go to Choice Question page and save other constructs there, depth++
				header("Location:choice_question.php");
				$_SESSION["depth"]++; 
		*/
		var choiceques = "";
		for (var j = 0; j < arr1.length; j++)
		{
			var questionid = document.getElementById(arr1[j]).value;
			choiceques += questionid + " ";
		}
		document.getElementById("choiceques").value = choiceques;	// e.g. 1 53 91 questions
		document.getElementById("anstochoose").value = CONSTRUCT;	// e.g. 2 constructs, or, 1 subcat answer
	}
	
	// Save Contructs! or Subcategory!
	for (var i = 0; i < constructs.length; i++)
	{
		var questionid = document.getElementById(constructs[i]).value;	// Question ID
		document.getElementById("questionid_construct").value += questionid + " ";
		// After all, Constructs will be saved in Session cookie in PHP side
	}
	
	// alert("Question ID: " + document.getElementById("questionid_construct").value);
	// alert("choiceques: " + document.getElementById("choiceques").value);
	// alert("Answer to choose: " + document.getElementById("anstochoose").value );
}
</script>

<!-- cassurvey.php: EXIT Confirmation	// Mostly copied from JS function validation() -->
<script>		
$(document).ready(function($) {
	window.addEventListener("beforeunload", function (e) {
		/* Check whether all questions were correctly filled or not */
		var datasize = <?php if($_SESSION["datasize"] == 0) echo 0; else echo $_SESSION["datasize"]; ?>;	// datasize is 5 if there are 5 questions
		var errormsg = "";

		for (var d = 1; d < datasize; d++)
		{
			// Find which radio button is selected in each question
			var name = "Service_" + d;
			var answer_options = document.getElementsByName(name);	// Radio buttons
			var answer="";
			for (var i = 0; i < answer_options.length; i++) 
			{
				if (answer_options[i].checked)
					break;
				// If we find that the user hasn't answered a particular question, show an error message
				if (i == answer_options.length-1)
				{
					if (errormsg.length > 9)
						errormsg += ", " + d;
					else
						errormsg += d;
				}	
			}
		}
		
		if (errormsg.length > 0)
		{	
			// *** Browsers never give us an option to do something when the page is confirmed to be unloaded
			// Confirm the exit
			var confirmationMessage = "Your answers will be lost.";
			(e || window.event).returnValue = confirmationMessage; 
			return confirmationMessage;
		}
	});
});
</script>

<style type="text/css">
body,td,th {
	font-size: medium;
}
#buttons
{
    position:fixed;
    top:30px;
}
</style>
</head>

<body>
<form name="survey4" method="post" action="cassurvey.php" onsubmit="return validation();">
<div id="header">
	<h1>Customer Satisfaction for Cafés</h1>
</div>
	<div style="clear: both;"> </div>
	<!-- 
	Quit and Delete
		1. Confirm if the user wants to quit the survey without saving their answers
		2. The user writes down a reason of why he or she wants to quit
		3. Just Exit
	-->
		<div id="buttons">
			<p> 
				<input type="button" name="quit_delete" value="Quit and Delete" onclick="quitdelete();"/>
			</p>
		</div>
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
	
	<!-- --- -->
	<div align="left"></div>
	<div id="colThree">
	  <h2 align="center">Welcome to Our Survey</h2>
	  <p align="center"><strong></strong></p>
		<div align="center">
		  <table border="0" cellspacing="0" cellpadding="0" width="100%">
		    <tr>
		      <td height="91"><p align="left"><strong>Please indicate your level of agreement or disagreement with the following statements.</strong></p></td>
	        </tr>
	      </table>
		  
		  <table border="1" cellspacing="0" cellpadding="0" width="100%">
		    <tr>
		      <td><div align="center">
		        <table border="0" cellspacing="0" cellpadding="0" width="100%">
		          <tr>
		            <td width="43%"><p> </p></td>
		            <td width="20%"><p><strong> Strongly Disagree</strong></p></td>
		            <td width="1%"><p align="center"> </p></td>
		            <td width="36%"><p align="right"><strong>Strongly Agree </strong></p></td>
	              </tr>
	            </table>
		        </div>
		        <div align="center">
		          <table border="0" cellspacing="0" cellpadding="0" width="100%">
		            <tr>
		              <td> </td>
		              <td><p align="center"><strong>1</strong></p></td>
		              <td><p align="center"><strong>2</strong></p></td>
		              <td><p align="center"><strong>3</strong></p></td>
		              <td><p align="center"><strong>4</strong></p></td>
		              <td><p align="center"><strong>5</strong></p></td>
	                </tr>
					
					<!-- VERY IMPORTANT PART! - Making Questions according to the query -->
					<?php
					while ($data = odbc_fetch_array($queryresult))
					{
						echo "<tr>
							  <td><p align='left'> </p>
							  <p align='left' style='padding-left: 5px'>
							  <input type='hidden' id='Service_".$i."' value='".$data["ID"]."'/>
								".$i.". ".utf8_encode($data["Question"])."
							  </p>
							  <p align='left'> </p></td>
							  <td><p align='center'>
								<input type='radio' name='Service_".$i."' value='1' />
							  </p></td>
							  <td><p align='center'>
								<input type='radio' name='Service_".$i."' value='2' />
							  </p></td>
							  <td><p align='center'>
								<input type='radio' name='Service_".$i."' value='3' />
							  </p></td>
							  <td><p align='center'>
								<input type='radio' name='Service_".$i."' value='4' />
							  </p></td>
							  <td><p align='center'>
								<input type='radio' name='Service_".$i."' value='5' />
							  </p></td>
							</tr>";
						$i++;
						// At the end, it saves a total number of questions + 1
						$_SESSION["datasize"] = $i;
					}
					?>
	              </table>
	            </div></td>
	        </tr>
	      </table>
	  </div>
	  <p align="center"> </p>
	  <p align="center"> </p>
	  <p align="center">
	    <input type="submit" class="submitbutt" name="submitbutt" value="Submit" onclick="answers();"/>
		<!-- Hidden type objects -->
		<input type="hidden" id="questionid_construct" name="questionid_construct"/> 
		<input type="hidden" id="choiceques" name="choiceques" value=""/>
		<input type="hidden" id="anstochoose" name="anstochoose" value=""/>
		<input type="hidden" id="results" name="results" value=""/>
		<input type="hidden" id="reason" name="reason" value=""/>
      </p>
<td> </td>
<tr>
  
</div>
	<div style="clear: both;"> </div>
</div>
<div id="footer">
	<p>Copyright 2015 </p>
</div>
</form>
</body>
</html>
