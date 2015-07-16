/* Login popup */
function login(showhide){
	if(showhide == "show")
	{
		document.getElementById('popupbox').style.visibility="visible";
	}
	else if(showhide == "hide")
	{
		document.getElementById('popupbox').style.visibility="hidden"; 
	}
}

/* Redirect to Home page */
function home(){
	window.location.href = "cashome.php";
}

/* Redirect to About page */
function about(){
	window.location.href = "casabout.html";
}

/* Redirect to Contact Us page */
function contactus(){
	window.location.href = "cascontact.html";
}

/* Redirect to the page including the consent form */
function entersurvey(){
	window.location.href = "casconsent.php";
}

/* If user agrees the consent form, Redirect to the Participant details page */
function consentagree(){
	window.location.href = "casperson.php";
}

/* Redirect to the End of the survey - Case1: when participant disagrees the consent form */
function quitsurvey(){
	window.location.href = "casexit.php";
}

/* Report any errors found to the user */
function errorinpage(errormsg){
	alert(errormsg);
	return false;
}

/* casperson.php: Check whether all questions were correctly filled or not */
function input_validation(){
	/* get all details */
	var upi = document.getElementById("upi").value;
	var gender = document.getElementById("gender").value;
	var education = document.getElementById("education").value;
	var department = document.getElementById("department").value;
	var nationality = document.getElementById("nationality").value;
	var lastvisit = document.getElementById("lastvisit").value;
	var birthday = document.getElementById("birthday").value;
	var service_5_all = document.getElementsByName("Service_5");
	var service_5="";
	for (var i = 0; i < service_5_all.length; i++) 
	{
		if (service_5_all[i].checked)
		{
			service_5 = service_5_all[i].value;
		}
	}
	var error = "";
	
	/* validate if all participant's details are entered */
	if (upi.length < 5)
		error += "* Your upi is incorrect!\n";
	if (gender.toLowerCase() === "select gender")
		error += "* Please select your gender!\n";
	if (education.toLowerCase() === "select education")
		error += "* Please select your education!\n";
	if (department.toLowerCase() === "select department")
		error += "* Please select your department!\n";
	if (nationality.toLowerCase() === "select nationality")
		error += "* Please select your nationality!\n";
	if (lastvisit === "")
		error += "* Please select your last visit date!\n";
	else if (!validateDate(lastvisit))
		error += "* Please select your last visit date correctly!\n";
	if (birthday === "")
		error += "* Please select your date of birth!\n";
	else if (!validateDate(birthday))
		error += "* Please select your birthday correctly!\n";
	if (service_5 === "")
		error += "* Please select the cafe!\n";
	
	if (error === "")
	{	
		return true;
	}
	else
	{
		errorinpage(error);
		return false;
	}
}

/* casperson.php: Just returning true or false when submitting the page*/
function input_validation_tf(){
	/* get all details */
	var upi = document.getElementById("upi").value;
	var gender = document.getElementById("gender").value;
	var education = document.getElementById("education").value;
	var department = document.getElementById("department").value;
	var nationality = document.getElementById("nationality").value;
	var lastvisit = document.getElementById("lastvisit").value;
	var birthday = document.getElementById("birthday").value;
	var newdate = new Date(lastvisit);
	var newbirthday = new Date(birthday);
	var today = new Date();
	var service_5_all = document.getElementsByName("Service_5");
	var service_5="";
	for (var i = 0; i < service_5_all.length; i++) 
	{
		if (service_5_all[i].checked)
		{
			service_5 = service_5_all[i].value;
		}
	}
	var error = "";
		
	/* validate if all participant's details are entered */
	if (upi.length < 5)
		error += "* Your upi is incorrect!\n";
	if (gender.toLowerCase() === "select gender")
		error += "* Please select your gender!\n";
	if (education.toLowerCase() === "select education")
		error += "* Please select your education!\n";
	if (department.toLowerCase() === "select department")
		error += "* Please select your department!\n";
	if (nationality.toLowerCase() === "select nationality")
		error += "* Please select your nationality!\n";
	if (lastvisit === "")
		error += "* Please select your last visit date!\n";
	else if (newdate >= today && !validateDate(lastvisit))
		error += "* Please select your last visit date correctly!\n";
	if (birthday === "")
		error += "* Please select your date of birth!\n";
	else if (newbirthday >= today && !validateDate(birthday))
		error += "* Please select your birthday correctly!\n";
	if (service_5 === "")
		error += "* Please select the cafe!\n";
	
	if (error === "")
	{
		return true;
	}
	else
	{
		return false;
	}
}

/* jQuery section - Back button is pressed */
$(document).ready(function($) {
	/* When the back button is clicked or pressed, this jQuery function will be triggered. 
	- Show a kind of warning message to the participant that who will be sent to the start HOME page
	- If yes, send the participant to the HOME page, destroy all sessions at the entrance of that page
	- If no, then stay in the same page
	*/
	if (window.history && window.history.pushState) {
		/* There should be at least one state in Window.history to proceed the function below */
		$(window).on('popstate', function() {
			if (confirm('Back button was pressed. \nYou will be sent to the START page. Do you still want to proceed?')) {
				window.location.href = "./cashome.php";
			}
			else
				window.history.pushState('back', null, window.location.href);
		});

		window.history.pushState('back', null, window.location.href);
	}
});

function quitdelete(){
	if (confirm("Are you sure to quit this survey?"))
	{
		var reason = prompt("Please write down why do you want to quit the survey?");
		while (reason == "")
			reason = prompt("Please write down why do you want to quit the survey?");
		if (reason != null)
		{
			$.ajax({
				   type: 'POST',
				   data: {reason : reason},
				   url: 'casexit.php',
				   success: function(data) {},
				   error: function() {}
				});
			quitsurvey();
		}
	}
}

function exitsave(){
	if (confirm("Are you sure to exit this survey?"))
	{
		var reason = prompt("Please write down why do you want to quit the survey?");
		while (reason == "")
			reason = prompt("Please write down why do you want to quit the survey?");
		if (reason != null)
		{
			$.ajax({
				   type: 'POST',
				   data: {reason : reason},
				   url: 'casexit.php',
				   success: function(data) {},
				   error: function() {}
				});
			quitsurvey();
		}
	}
}

function validateDate(mydatestr){
	var chromeparts = mydatestr.split("-");
	var chromeoccur = chromeparts.length;
	if (chromeoccur == 3)
	{
		var newdate = new Date(mydatestr);
		var today = new Date();
		if (newdate >= today) return false;
		
		return true;
	}
	else
	{
		// First check for the pattern
		if(!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(mydatestr))
			return false;

		// Parse the date parts to integers
		var parts = mydatestr.split("/");
		var occur = parts.length;
		if (occur != 3) return false;
		var day = parseInt(parts[0], 10);
		var month = parseInt(parts[1], 10);
		var year = parseInt(parts[2], 10);

		// Check the ranges of month and year
		if(year < 1000 || year > 3000 || month == 0 || month > 12)
			return false;

		var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

		// Adjust for leap years
		if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
			monthLength[1] = 29;
		
		// Compare the date with today
		var daystr = parts[0];
		var monthstr = parts[1];
		var yearstr = parts[2];
		var value = yearstr + "-" + monthstr + "-" + daystr;
		var newdate = new Date(value);
		var today = new Date();
		if (newdate >= today) return false;

		// Check the range of the day
		return day > 0 && day <= monthLength[month - 1];
	}
	return true;
}












