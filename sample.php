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
 * Download a sample user CSV.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();
require_capability('local/bulkcohortenrol:manage', context_system::instance());

$writer = new csv_export_writer('comma', '"', 'text/csv', true);
$writer->set_filename('cohort-users-sample');
$writer->add_data(['username', 'firstname', 'lastname', 'email', 'cohort']);
$writer->add_data(['123456789', 'דנה', 'כהן', 'dana@example.com', 'Pl']);
$writer->add_data(['987654321', 'יוסי', 'לוי', 'yossi@example.com', 'Pl']);
$writer->download_file();
