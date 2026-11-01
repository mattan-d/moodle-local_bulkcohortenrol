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
 * English strings.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bulk cohort assignment';
$string['privacy:metadata'] = 'The bulk cohort assignment plugin does not store personal data. Cohort membership is stored by Moodle.';
$string['bulkcohortenrol:manage'] = 'Create a system cohort from a CSV file and assign users to it';

$string['intro'] = 'Upload a CSV file of existing users. The plugin creates one system cohort, or reuses a cohort with the same ID number, and adds the users to it. Accounts that are not already in Moodle are listed and are not created.';
$string['csvformat'] = 'CSV columns';
$string['csvformatdesc'] = 'The first row is a header. Required: username or email, depending on the match field. Optional: firstname, lastname, cohort. When the ID number is left empty and every row has the same cohort value, that value becomes the cohort ID number. Example: username,firstname,lastname,email,cohort';
$string['downloadsample'] = 'Download a sample CSV';
$string['cohortname'] = 'Cohort name';
$string['cohortname_help'] = 'Name of the system cohort to create. If a system cohort with the ID number already exists, users are added to that cohort and its name is left unchanged.';
$string['cohortidnumber'] = 'Cohort ID number';
$string['cohortidnumber_help'] = 'Optional unique ID. If a cohort with this ID already exists in the system context, users are added to it. If you leave this empty and the CSV cohort column contains a single value, that value is used.';
$string['csvfile'] = 'CSV file';
$string['csvfile_help'] = 'UTF-8 is the usual encoding. Files saved by Excel in Hebrew are often Windows-1255, and the separator is often a semicolon.';
$string['matchfield'] = 'Match users by';
$string['matchfield_help'] = 'Each row is matched to one existing account. Other accounts are reported as not found.';
$string['matchusername'] = 'Username';
$string['matchemail'] = 'Email address';
$string['delimiter'] = 'CSV separator';
$string['delimitercomma'] = 'Comma (,)';
$string['delimitersemicolon'] = 'Semicolon (;)';
$string['delimitercolon'] = 'Colon (:)';
$string['delimitertab'] = 'Tab';
$string['encoding'] = 'Encoding';
$string['upload'] = 'Upload and preview';
$string['previewheading'] = 'Preview';
$string['summary'] = 'Summary';
$string['cohortstatusnew'] = 'A new system cohort will be created.';
$string['cohortstatusexisting'] = 'Users will be added to the existing system cohort "{$a}".';
$string['rows'] = 'Rows in the file';
$string['toadd'] = 'Users to assign';
$string['already'] = 'Already members';
$string['missing'] = 'Users not found';
$string['duplicate'] = 'Duplicate rows skipped';
$string['invalid'] = 'Invalid rows';
$string['suspended'] = 'Suspended users included';
$string['missingusers'] = 'Users not found';
$string['invalidrows'] = 'Invalid rows';
$string['line'] = 'Line';
$string['fullname'] = 'Name';
$string['email'] = 'Email';
$string['username'] = 'Username';
$string['reason'] = 'Reason';
$string['andmore'] = 'and {$a} more';
$string['emptyidentifier'] = 'The match column is empty';
$string['invalidemail'] = 'The email address is not valid';
$string['nousers'] = 'No users from this file can be assigned, so nothing will be changed.';
$string['nopending'] = 'The upload expired. Upload the CSV file again.';
$string['cannotapply'] = 'Nothing can be assigned from this file.';
$string['applyresult'] = 'Cohort "{$a->name}" {$a->status}. Assigned {$a->added} users. Already members: {$a->already}. Not found: {$a->missing}.';
$string['statuscreated'] = 'was created';
$string['statusexisting'] = 'already existed';
$string['namenote'] = 'The existing cohort is named "{$a->existing}". The name you entered ("{$a->requested}") will not replace it.';
$string['idnumbersourcecsv'] = 'ID number taken from the cohort column: {$a}';
$string['idnumbersourcenone'] = 'No ID number was entered, and the file does not contain a single cohort value. The cohort will be matched by name only.';
$string['csvmany'] = 'The cohort column contains more than one value ({$a}). One cohort is created from the form, and those values are not used as ID numbers.';
$string['notsystem'] = 'A cohort with this ID number already exists outside the system context ("{$a}"). Choose another ID number.';
$string['ambiguousname'] = 'More than one system cohort already uses this name. Enter an ID number so the right cohort can be chosen.';
$string['duplicateidnumber'] = 'More than one cohort already uses this ID number.';
$string['samenameexists'] = 'A system cohort with this name already exists, with a different ID number. A separate cohort will be created.';
$string['missingcolumn'] = 'The CSV file must include a {$a} column.';
$string['csvnodata'] = 'The CSV file has a header but no data rows.';
$string['nametoolong'] = 'The cohort name must be 254 characters or fewer.';
$string['idnumbertoolong'] = 'The cohort ID number must be 100 characters or fewer.';
$string['filetoobig'] = 'The CSV file is too large.';
$string['invalidencoding'] = 'Choose a valid encoding.';
$string['invaliddelimiter'] = 'Choose a valid separator.';
$string['invalidmatchfield'] = 'Choose how users should be matched.';
$string['invalidcsv'] = '{$a}';
$string['missingassigncap'] = 'You need the capability to assign cohort members.';
$string['missingmanagecap'] = 'You need the capability to create cohorts.';
$string['externalcohort'] = 'This cohort is managed by {$a}. Members can still be added here, but another plugin may change the membership later.';
$string['confirmcreate'] = 'Create the cohort and assign users';
$string['confirmexisting'] = 'Assign users to the cohort';
$string['back'] = 'Back';
$string['csvcohorts'] = 'Cohort values in the file';
