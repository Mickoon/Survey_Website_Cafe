# Survey Website Cafe
Using PHP, JavaScript, jQuery, HTML, CSS 

This is a sample code I have used to develop a survey website for my client who wants to take students' survey results for her academic research.

NOTE: Home page includes some confidential information, so this page could not be uploaded.

<h2>Background Information:</h2>
The database stores a table which includes 180 questions. These questions will be appeared in the website according to their "Subcategory" ID.
Those questions are grouped and retrieved according to their individual subcategory ID. 
Each question has a data called "belongs to survey" which indicates a kind of group number that the question belongs to.
e.g. if question with belongs to survey id 1 is selected, all question with subcategory id 1 will be retrieved and shown in the following page.
So when there will be no more questions, the survey finishes, and whole data will be saved in the database.

The participant can quit this survey whenever he/she wants, however before the participant has to provide a reason of why he/she wants to quit before the exit. 

<h2>How this survey site works?</h2>
0. Admin page // Only admin can access to this page
1. Consent form // this is missing in this repository
2. Personal details page (casperson.php)
3. Survey page (cassurvey.php)
3.1. Choice Question Category page (caschoice_cat.php)
3.2. Choice Question Subcategory page (caschoice_subcat.php)
4. Exit page (casexit.php)

<h2>More details will be available upon a request</h2>

