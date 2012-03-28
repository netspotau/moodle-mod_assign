<?php 

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'assign', language 'en'
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowsubmissions'] = 'Allow the user to continue making submissions to this assignment.';
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['allowsubmissionsfromdatesummary'] = 'This assignment will accept submissions from <strong>{$a}</strong>';
$string['allowsubmissionsanddescriptionfromdatesummary'] = 'The assignment details and submission form will be available from <strong>{$a}</strong>';
$string['applychanges'] = 'Apply changes';
$string['alwaysshowdescription'] = 'Always show description';
$string['applytoteam'] = 'Apply grades and feedback to entire team';
$string['assign:exportownsubmission'] = 'Export own submission';
$string['assign:grade'] = 'Grade assignment';
$string['assign:grantextension'] = 'Grant extension';
$string['assignmentisdue'] = 'Assignment is due';
$string['assignmentname'] = 'Assignment name';
$string['assignmentplugins'] = 'Assignment plugins';
$string['assignmentsperpage'] = 'Assignments per page';
$string['assignsubmissionpluginname'] = 'Submission plugin';
$string['assign:revealidentities'] = 'Reveal student identities';
$string['assign:submit'] = 'Submit assignment';
$string['assign:view'] = 'View assignment';
$string['assignfeedback'] = 'Feedback plugin';
$string['assignsubmission'] = 'Submission plugin';
$string['assignfeedbackpluginname'] = 'Feedback plugin';
$string['availability'] = 'Availability';
$string['back'] = 'Back';
$string['backtoassignment'] = 'Back to assignment';
$string['blindmarking'] = 'Blind marking';
$string['cancel'] = 'Cancel';
$string['comment'] = 'Comment';
$string['conversionexception'] = 'Could not convert assignment. Exception was: {$a}.';
$string['confirmsubmission'] = 'Are you sure you want to submit your work for grading? You will not be able to make any more changes';
$string['couldnotconvertgrade'] = 'Could not convert assignment grade for user {$a}.';
$string['couldnotconvertsubmission'] = 'Could not convert assignment submission for user {$a}.';
$string['couldnotcreatecoursemodule'] = 'Could not create course module.';
$string['couldnotcreatenewassignmentinstance'] = 'Could not create new assignment instance.';
$string['couldnotfindassignmenttoupgrade'] = 'Could not find old assignment instance to upgrade.';
$string['defaultteam'] = 'Default team';
$string['deletepluginareyousure'] = 'Delete assignment plugin {$a}: are you sure?';
$string['deletepluginareyousuremessage'] = 'You are about to completely delete the assignment plugin {$a}. This will completely delete everything in the database associated with this assignment plugin. Are you SURE you want to continue?';
$string['deletingplugin'] = 'Deleting plugin {$a}.';
$string['description'] = 'Description';
$string['descriptionmaxfiles'] = 'You can upload up to {$a} file(s).';
$string['downloadallsubmissions'] = 'Download all submission files';
$string['downloadallgrades'] = 'Download all grades';
$string['downloadlatestgradingspreadsheet'] = 'Download latest grading spreadsheet';
$string['download all submissions'] = 'Download all submissions in a zip file.';
$string['duedate'] = 'Due date';
$string['duedateno'] = 'No due date';
$string['duedatereached'] = 'The due date for this assignment has now passed';
$string['duedatevalidation'] = 'Due date must be after the allow submissions from date.';
$string['editsubmission'] = 'Edit my submission';
$string['gradersubmissionupdatedtext'] = '{$a->username} has updated their assignment submission
for \'{$a->assignment}\' at {$a->timeupdated}

It is available here:

    {$a->url}';
$string['gradersubmissionupdatedhtml'] = '{$a->username} has updated their assignment submission
for <i>\'{$a->assignment}\'  at {$a->timeupdated}</i><br /><br />
It is <a href="{$a->url}">available on the web site</a>.';
$string['gradersubmissionupdatedsmall'] = '{$a->username} has updated their submission for assignment "{$a->assignment}".';
$string['enabled'] = 'Enabled';
$string['errornosubmissions'] = 'There are no submissions to download';
$string['extensionduedate'] = 'Extension due date';
$string['extensionnotafterduedate'] = 'Extension date must be after the due date';
$string['extensionnotafterfromdate'] = 'Extension date must be after the allow submissions from date';
$string['extensionnotbeforefinaldate'] = 'Extension date must be before the final date';
$string['feedbackcomments'] = 'Feedback Comments';
$string['feedback'] = 'Feedback';
$string['feedback'] = 'Feedback';
$string['feedbackfiles'] = 'Feedback files';
$string['feedbackavailabletext'] = '{$a->grader} has posted some feedback on your
assignment submission for \'{$a->assignment}\'

You can see it appended to your assignment submission:

    {$a->url}';
$string['feedbackavailablehtml'] = '{$a->grader} has posted some feedback on your
assignment submission for \'<i>{$a->assignment}</i>\'<br /><br />
You can see it appended to your <a href="{$a->url}">assignment submission</a>.';
$string['feedbackavailablesmall'] = '{$a->grader} has given feedback for assignment "{$a->assignment}"';
$string['feedbackplugins'] = 'Feedback plugins';
$string['feedbackpluginforgradebook'] = 'Feedback plugin that will push comments to the gradebook';
$string['feedbackpluginforgradebook_help'] = 'Only one assignment feedback plugin can push feedback into the gradebook.';
$string['feedbackplugin'] = 'Feedback plugin';
$string['feedbacksettings'] = 'Feedback settings';
$string['feedbacktextnumwords'] = 'Number of words in feedback text: {$a}. ';
$string['filesubmissions'] = 'File submissions';
$string['filter'] = 'Filter';
$string['filternone'] = 'No filter';
$string['filterrequiregrading'] = 'Requires grading';
$string['filtersubmitted'] = 'Submitted';
$string['grade'] = 'Grade';
$string['gradeabovemaximum'] = 'Grade must be less than or equal to {$a}.';
$string['gradebelowzero'] = 'Grade must be greater than or equal to zero.';
$string['finaldate'] = 'Final date';
$string['finaldatevalidation'] = 'Final date must be after the due date.';
$string['finaldatefromdatevalidation'] = 'Final date must be after the allow submissions from date.';
$string['gradedby'] = 'Graded by';
$string['graded'] = 'Graded';
$string['gradedon'] = 'Graded on';
$string['gradeoutof'] = 'Grade out of {$a}';
$string['graderecentlymodified'] = 'The grade has been modified in Moodle more recently than in the grading file for {$a}';
$string['gradesfor'] = 'Grades for {$a}';
$string['gradesfile'] = 'Grades file (csv format)';
$string['gradeupdatedbyimport'] = 'Grade for user {$a->fullname} updated by csv import';
$string['gradestudent'] = 'Grade student: (id={$a->id}, fullname={$a->fullname}). ';
$string['gradeupdate'] = 'Set grade for {$a->student} to {$a->grade}';
$string['grading'] = 'Grading';
$string['gradingoptions'] = 'Display options';
$string['gradingstatus'] = 'Grading status';
$string['gradingsummary'] = 'Grading summary';
$string['grantextension'] = 'Grant extension';
$string['hideshow'] = 'Hide/Show';
$string['hiddenuser'] = 'Participant {$a}';
$string['ignoremodifieddate'] = 'Ignore last modified date';
$string['importgrades'] = 'Import grades';
$string['importactions'] = 'Import actions';
$string['importgradeshelp'] = 'The list of grade updates found in the import file are listed below. You can apply all the changes or cancel the import.';
$string['instructionfiles'] = 'Instruction files';
$string['invalidgradeforscale'] = 'The grade supplied was not valid for the current scale';
$string['invalidgradeimport'] = 'The uploaded grades file could not be understood';
$string['invalidfloatforgrade'] = 'The grade provided could not be understood: {$a}';
$string['lastmodifiedsubmission'] = 'Last modified (submission)';
$string['lastmodifiedgrade'] = 'Last modified (grade)';
$string['latesubmissions'] = 'Late submissions';
$string['latesubmissionsaccepted'] = ' Only student(s) having been granted extension can still submit the assignment';
$string['nosubmissionsaccepted'] = 'No more submissions accepted';
$string['locksubmissionforstudent'] = 'Prevent any more submissions for student: (id={$a->id}, fullname={$a->fullname}).';
$string['manageassignfeedbackplugins'] = 'Manage assignment feedback plugins';
$string['manageassignsubmissionplugins'] = 'Manage assignment submission plugins';
$string['maximumsize'] = 'Max';
$string['messageprovider:assign_student_notifications'] = 'Assignment student notifications';
$string['messageprovider:assign_grader_notifications'] = 'Assignment grader notifications';
$string['minfilessubmission'] = 'Minimum number of uploaded files';
$string['modulename'] = 'Assignment';
$string['modulename_help'] = 'Assignments enable the teacher to specify a task either on or offline which can then be graded.';
$string['modulenameplural'] = 'Assignments';
$string['mysubmission'] = 'My submission: ';
$string['nofeedbackfiles'] = 'No feedback files. ';
$string['nofeedbacktext'] = 'No feedback text. ';
$string['nofiles'] = 'No files. ';
$string['nograde'] = 'No grade. ';
$string['nogradeinimport'] = 'No grade in grading file for {$a}';
$string['nomoresubmissionsaccepted'] = 'No more submissions accepted';
$string['noonlinesubmissions'] = 'This assignment does not require you to submit anything online';
$string['noonlinetext'] = 'No online text. ';
$string['nomatchinguserforid'] = 'No matching user for id {$a}';
$string['nosavebutnext'] = 'Next';
$string['nosubmissioncomment'] = 'No submission comment. ';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['notgraded'] = 'Not graded';
$string['notgradedyet'] = 'Not graded yet';
$string['notsubmittedyet'] = 'Not submitted yet';
$string['notifications'] = 'Notifications';
$string['notification'] = 'Notification';
$string['numberofdraftsubmissions'] = 'Drafts';
$string['numberofparticipants'] = 'Participants';
$string['numberofsubmittedassignments'] = 'Submitted';
$string['open'] = 'Open';
$string['offline'] = 'No online submissions required';
$string['overdue'] = '<font color="red">Assignment is overdue by: {$a}</font>';
$string['participant'] = 'Participant';
$string['pluginadministration'] = 'Assignment administration';
$string['pluginname'] = 'Assignment';
$string['preventlatesubmissions'] = 'Prevent late submissions';
$string['preventsubmissions'] = 'Prevent the user from making any more submissions to this assignment.';
$string['requiresubmissionstatement'] = 'Require students accept the submission statement';
$string['requiresubmissionstatement_desc'] = 'Require the submission statement for all assignment submissions for this entire Moodle installation';
$string['reverttodraftforstudent'] = 'Revert submission to draft for student: (id={$a->id}, fullname={$a->fullname}).';
$string['reason'] = 'Reason: {$a}';
$string['recordid'] = 'Identifier';
$string['revealidentities'] = 'Reveal student identities';
$string['revealidentitiesconfirm'] = 'Are you sure you want to reveal student identities for this assignment. This operation cannot be undone. Once the student identities have been revealed, the marks will be released to the gradebook.';
$string['reverttodraft'] = 'Revert the submission to draft status. This allows further changes to the submission.';
$string['reviewed'] = 'Reviewed';
$string['savechanges'] = 'Save changes';
$string['savecomments'] = 'Save submission comments';
$string['savefiles'] = 'Save files';
$string['savenext'] = 'Save and show next';
$string['saveonlinetext'] = 'Save changes';
$string['sendnotifications'] = 'Notify graders about submissions';
$string['sendlatenotifications'] = 'Notify graders about late submissions';
$string['sendsubmissionreceipts'] = 'Send submission receipt to students';
$string['sendsubmissionreceipts_help'] = 'This switch will enable submission receipts for students. Students will receive a notification every time they successfully submit an assignment';
$string['settings'] = 'Assignment settings';
$string['skiprecord'] = 'Skip record';
$string['skippedmodified'] = 'Records skipped because they have been updated more recently in Moodle than in the import file: {$a->count}';
$string['skippednograde'] = 'Records skipped because they have no value in the grade column: {$a->count}';
$string['skippedinvalidgrade'] = 'Records skipped because they have an invalid value in the grade column: {$a->count}';
$string['step'] = 'Step {$a}';
$string['submissioncommentnumwords'] = 'Number of words in submission comment: {$a}. ';
$string['submissioncomments'] = 'Enable submission comments';
$string['submissioncomment'] = 'Submission comments';
$string['submissiondrafts'] = 'Require students click submit button';
$string['submissioneditable'] = 'Student can edit this submission';
$string['submissionfiles'] = 'Submission files';
$string['submissionnoteditable'] = 'Student cannot edit this submission';
$string['submissionteam'] = 'Team';
$string['assignsubmissionpluginname'] = 'Submission plugin';
$string['submissionplugins'] = 'Submission plugins';
$string['submissionreceipts'] = 'Send submission receipts';
$string['teamsubmissionreceipttext'] = 'A member of your team has submitted an
assignment submission for \'{$a->assignment}\'

You can see the status of the assignment submission:

    {$a->url}';
$string['submissionreceipttext'] = 'You have submitted an
assignment submission for \'{$a->assignment}\'

You can see the status of your assignment submission:

    {$a->url}';
$string['submissionreceipthtml'] = 'You have submitted an
assignment submission for \'<i>{$a->assignment}</i>\'<br /><br />
You can the status of your <a href="{$a->url}">assignment submission</a>.';
$string['teamsubmissionreceipthtml'] = 'A member of your team has submitted an
assignment submission for \'<i>{$a->assignment}</i>\'<br /><br />
You can the status of the <a href="{$a->url}">assignment submission</a>.';
$string['submissionreceiptsmall'] = 'You have submitted your assignment submission for "{$a->assignment}"';
$string['teamsubmissionreceiptsmall'] = 'A member of your team has submitted an assignment submission for "{$a->assignment}"';
$string['submissionslocked'] = 'This assignment is not accepting submissions';
$string['submissions'] = 'Submissions';
$string['submissionsnotgraded'] = 'Submissions not graded: {$a}';
$string['submissionsclosed'] = 'Submissions closed';
$string['submissionstatement'] = 'Submission statement';
$string['submissionstatement_desc'] = 'Assignment submission confirmation statement';
$string['submissionstatementacceptedlog'] = 'Submission statement accepted by user {$a}';
$string['submissionsettings'] = 'Submission settings';
$string['submissionstatus_draft'] = 'Draft (not submitted)';
$string['submissionstatusheading'] = 'Submission status';
$string['submissionstatus_marked'] = 'Graded';
$string['submissionstatus_new'] = 'New submission';
$string['submissionstatus_'] = 'No submission';
$string['submissionstatus'] = 'Submission status';
$string['submissionstatus_submitted'] = 'Submitted for grading';
$string['submission'] = 'Submission';
$string['submitassignment_help'] = 'Once this assignment is submitted you will not be able to make any more changes';
$string['submitassignment'] = 'Submit assignment';
$string['submittedearly'] = 'Assignment was submitted {$a} early';
$string['submittedlate'] = 'Assignment was submitted {$a} late';
$string['submittedlateshort'] = '{$a} late';
$string['submitted'] = 'Submitted';
$string['teamsubmission'] = 'Students submit in teams';
$string['teamsubmissionstatus'] = 'Team submission status';
$string['teamsubmissiongrouping'] = 'Grouping for student teams';
$string['requireallteammemberssubmit'] = 'Require all team members submit';
$string['textinstructions'] = 'Assignment instructions';
$string['timemodified'] = 'Last modified';
$string['timeremaining'] = 'Time remaining';
$string['unlocksubmissionforstudent'] = 'Allow submissions for student: (id={$a->id}, fullname={$a->fullname}).';
$string['updatetable'] = 'Save and update table';
$string['updatedgrade'] = 'Number of grades updated: {$a->count}';
$string['updaterecord'] = 'Update record';
$string['upgradenotimplemented'] = 'Upgrade not implemented in plugin ({$a->type} {$a->subtype})';
$string['uploadfiles'] = 'Upload files';
$string['uploadgrades'] = 'Upload grades';
$string['userextensiondate'] = 'Extension granted until: {$a}';
$string['uploadgradeshelp'] = 'This function lets you upload a spreadsheet of student grades and update the grade for each student with a changed grade in the file. The spreadsheet must have been downloaded from this assignment and must contain three columns: <ul><li>Grade - the column with the updated grade information</li><li>Identifier - the column with the unique participant identifier for the student</li><li>Last modified (grade) - column with the last modified time for the students grade</li></ul>';
$string['userswhohavenotsubmitted'] = '<br/>This assignment requires all students to submit. <br/><br/>The following students have not submitted yet: <br/><br/>{$a}';
$string['userswhohavenotsubmittedsummary'] = '<br/>This assignment requires all students to submit. <br/><br/>{$a->count} of {$a->total} students have submitted.';
$string['viewfeedback'] = 'View feedback';
$string['viewfeedbackforuser'] = 'View feedback for user: {$a}';
$string['viewgradebook'] = 'View gradebook';
$string['viewgradingformforstudent'] = 'View grading page for student: (id={$a->id}, fullname={$a->fullname}).';
$string['viewgrading'] = 'Grade assignment';
$string['viewownsubmissionform'] = 'View own submit assignment page.';
$string['viewownsubmissionstatus'] = 'View own submission status page.';
$string['viewsubmissionforuser'] = 'View submission for user: {$a}';
$string['viewrevealidentitiesconfirm'] = 'View reveal student identities confirmation page.';
$string['viewsubmission'] = 'View submission';
$string['viewsubmissiongradingtable'] = 'View submission grading table.';
$string['viewuploadgradesform'] = 'View upload grades form.';
$string['viewplugingradingpage'] = 'View plugin grading page: (plugin={$a->plugin}, action={$a->action}).';


