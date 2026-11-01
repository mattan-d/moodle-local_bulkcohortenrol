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
 * Hebrew strings.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'שיוך משתמשים לקבוצה מערכתית';
$string['privacy:metadata'] = 'תוסף השיוך לקבוצה מערכתית אינו שומר נתונים אישיים משלו. החברות בקבוצה נשמרת על ידי מוודל.';
$string['bulkcohortenrol:manage'] = 'יצירת קבוצה מערכתית מקובץ CSV ושיוך משתמשים אליה';

$string['intro'] = 'מעלים קובץ CSV של משתמשים שכבר קיימים במערכת. התוסף יוצר קבוצה מערכתית אחת, או משתמש בקבוצה קיימת עם אותו מזהה, ומשייך אליה את המשתמשים. חשבונות שלא נמצאים במוודל מופיעים בדוח ולא נוצרים.';
$string['csvformat'] = 'עמודות בקובץ';
$string['csvformatdesc'] = 'השורה הראשונה היא כותרת. חובה: username או email, לפי שדה ההתאמה. רשות: firstname, lastname, cohort. אם מזהה הקבוצה נשאר ריק וכל השורות חולקות אותו ערך בעמודת cohort, הערך הזה ישמש כמזהה הקבוצה. לדוגמה: username,firstname,lastname,email,cohort';
$string['downloadsample'] = 'הורדת קובץ CSV לדוגמה';
$string['cohortname'] = 'שם הקבוצה המערכתית';
$string['cohortname_help'] = 'השם של הקבוצה המערכתית שתיווצר. אם כבר קיימת קבוצה מערכתית עם המזהה הזה, המשתמשים ישויכו אליה והשם הקיים לא יוחלף.';
$string['cohortidnumber'] = 'מזהה הקבוצה';
$string['cohortidnumber_help'] = 'מזהה ייחודי, לא חובה. אם קיימת קבוצה עם המזהה הזה בהקשר המערכת, המשתמשים ישויכו אליה. אם השדה ריק ובעמודת cohort בקובץ יש ערך אחד, הערך הזה ישמש כמזהה.';
$string['csvfile'] = 'קובץ CSV';
$string['csvfile_help'] = 'קידוד UTF-8 הוא ברירת המחדל. קובץ שנשמר מאקסל בעברית הוא לעיתים בקידוד Windows-1255, והמפריד הוא לעיתים נקודה-פסיק.';
$string['matchfield'] = 'זיהוי משתמשים לפי';
$string['matchfield_help'] = 'כל שורה מותאמת לחשבון קיים אחד. חשבון שלא נמצא מופיע בדוח.';
$string['matchusername'] = 'שם משתמש';
$string['matchemail'] = 'כתובת דוא"ל';
$string['delimiter'] = 'מפריד CSV';
$string['delimitercomma'] = 'פסיק (,)';
$string['delimitersemicolon'] = 'נקודה-פסיק (;)';
$string['delimitercolon'] = 'נקודתיים (:)';
$string['delimitertab'] = 'טאב';
$string['encoding'] = 'קידוד';
$string['upload'] = 'העלאה ותצוגה מקדימה';
$string['previewheading'] = 'תצוגה מקדימה';
$string['summary'] = 'סיכום';
$string['cohortstatusnew'] = 'תיווצר קבוצה מערכתית חדשה.';
$string['cohortstatusexisting'] = 'המשתמשים ישויכו לקבוצה המערכתית הקיימת "{$a}".';
$string['rows'] = 'שורות בקובץ';
$string['toadd'] = 'משתמשים לשיוך';
$string['already'] = 'כבר חברים בקבוצה';
$string['missing'] = 'משתמשים שלא נמצאו';
$string['duplicate'] = 'שורות כפולות שדולגו';
$string['invalid'] = 'שורות לא תקינות';
$string['suspended'] = 'משתמשים מושעים שישויכו';
$string['missingusers'] = 'משתמשים שלא נמצאו';
$string['invalidrows'] = 'שורות לא תקינות';
$string['line'] = 'שורה';
$string['fullname'] = 'שם';
$string['email'] = 'דוא"ל';
$string['username'] = 'שם משתמש';
$string['reason'] = 'סיבה';
$string['andmore'] = 'ועוד {$a}';
$string['emptyidentifier'] = 'עמודת הזיהוי ריקה';
$string['invalidemail'] = 'כתובת הדוא"ל אינה תקינה';
$string['nousers'] = 'אי אפשר לשייך משתמשים מהקובץ הזה, ולכן לא יבוצע שינוי.';
$string['nopending'] = 'ההעלאה פגה. יש להעלות את קובץ ה-CSV מחדש.';
$string['cannotapply'] = 'אין מה לשייך מהקובץ הזה.';
$string['applyresult'] = 'הקבוצה "{$a->name}" {$a->status}. שויכו {$a->added} משתמשים. כבר היו חברים: {$a->already}. לא נמצאו: {$a->missing}.';
$string['statuscreated'] = 'נוצרה';
$string['statusexisting'] = 'כבר הייתה קיימת';
$string['namenote'] = 'שם הקבוצה הקיימת הוא "{$a->existing}". השם שהוזן ("{$a->requested}") לא יחליף אותו.';
$string['idnumbersourcecsv'] = 'המזהה נלקח מעמודת cohort: {$a}';
$string['idnumbersourcenone'] = 'לא הוזן מזהה, ואין בקובץ ערך cohort אחד. הקבוצה תאותר לפי השם בלבד.';
$string['csvmany'] = 'בעמודת cohort יש יותר מערך אחד ({$a}). תיווצר קבוצה אחת לפי הטופס, והערכים האלה לא ישמשו כמזהה.';
$string['notsystem'] = 'כבר קיימת קבוצה עם המזהה הזה מחוץ להקשר המערכת ("{$a}"). יש לבחור מזהה אחר.';
$string['ambiguousname'] = 'יותר מקבוצה מערכתית אחת כבר משתמשת בשם הזה. יש להזין מזהה כדי לבחור את הקבוצה הנכונה.';
$string['duplicateidnumber'] = 'יותר מקבוצה אחת כבר משתמשת במזהה הזה.';
$string['samenameexists'] = 'כבר קיימת קבוצה מערכתית בשם הזה, עם מזהה אחר. תיווצר קבוצה נפרדת.';
$string['missingcolumn'] = 'קובץ ה-CSV חייב לכלול עמודה {$a}.';
$string['csvnodata'] = 'לקובץ ה-CSV יש שורת כותרת אבל אין שורות נתונים.';
$string['nametoolong'] = 'שם הקבוצה יכול להכיל עד 254 תווים.';
$string['idnumbertoolong'] = 'מזהה הקבוצה יכול להכיל עד 100 תווים.';
$string['filetoobig'] = 'קובץ ה-CSV גדול מדי.';
$string['invalidencoding'] = 'יש לבחור קידוד תקין.';
$string['invaliddelimiter'] = 'יש לבחור מפריד תקין.';
$string['invalidmatchfield'] = 'יש לבחור איך לזהות משתמשים.';
$string['invalidcsv'] = '{$a}';
$string['missingassigncap'] = 'חסרה ההרשאה לשייך חברים לקבוצה מערכתית.';
$string['missingmanagecap'] = 'חסרה ההרשאה ליצור קבוצות מערכתיות.';
$string['externalcohort'] = 'הקבוצה הזו מנוהלת על ידי {$a}. עדיין אפשר לשייך אליה חברים כאן, אבל תוסף אחר עשוי לשנות את החברות אחר כך.';
$string['confirmcreate'] = 'יצירת הקבוצה ושיוך המשתמשים';
$string['confirmexisting'] = 'שיוך המשתמשים לקבוצה';
$string['back'] = 'חזרה';
$string['csvcohorts'] = 'ערכי הקבוצה בקובץ';
