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
 * Course section language settings management page.
 *
 * URL: /local/snapmultilangnames/course_sections.php?courseid=X
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// Load course and require login.
$course = get_course($courseid);
require_login($course);
$coursecontext = context_course::instance($courseid);

// Access checks.

if (!get_config('local_snapmultilangnames', 'enablemultilangnames')) {
    throw new \moodle_exception('error_notenabledglobally', 'local_snapmultilangnames');
}

if (!\local_snapmultilangnames\util_sections::is_enabled_for_course($courseid)) {
    throw new \moodle_exception('error_notenabledcourse', 'local_snapmultilangnames');
}

require_capability('local/snapmultilangnames:managemultilangsettings', $coursecontext);

// Page setup.

$pageurl = new moodle_url('/local/snapmultilangnames/course_sections.php', ['courseid' => $courseid]);
$PAGE->set_url($pageurl);
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('managemultilangsettings', 'local_snapmultilangnames'));
$PAGE->set_heading($course->fullname);

// Load data.

// All sections for this course ordered by section number.
$sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');

// Existing lang-settings rows, re-keyed by sectionid for O(1) lookup.
$rawlangrows = $DB->get_records('local_snapmultilangnames_sec', ['courseid' => $courseid]);
$langdata = [];
foreach ($rawlangrows as $row) {
    $langdata[$row->sectionid] = $row;
}

// Build and process form.

$form = new \local_snapmultilangnames\form\course_sections_form($pageurl, [
    'courseid' => $courseid,
    'sections' => $sections,
    'langdata' => $langdata,
]);

// The language selects are raw HTML (not registered moodleform elements) so
// submission is handled directly via optional_param() rather than get_data().
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if (data_submitted()) {
    require_sesskey();

    $allowed  = array_keys(\local_snapmultilangnames\util_sections::get_language_options());
    $fallback = $allowed[0] ?? 'en';
    $now      = time();

    foreach ($sections as $section) {
        $sid = (int) $section->id;

        $lang1 = optional_param('lang1_' . $sid, '', PARAM_NOTAGS);
        $lang2 = optional_param('lang2_' . $sid, '', PARAM_NOTAGS);
        $lang3 = optional_param('lang3_' . $sid, '', PARAM_NOTAGS);

        // Restrict to known values; treat anything unexpected as the first configured language.
        $lang1 = in_array($lang1, $allowed, true) ? $lang1 : $fallback;
        $lang2 = in_array($lang2, $allowed, true) ? $lang2 : $fallback;
        $lang3 = in_array($lang3, $allowed, true) ? $lang3 : $fallback;

        $existing = $langdata[$sid] ?? null;

        if ($existing) {
            $existing->lang1        = $lang1;
            $existing->lang2        = $lang2;
            $existing->lang3        = $lang3;
            $existing->timemodified = $now;
            $DB->update_record('local_snapmultilangnames_sec', $existing);
        } else {
            // Section existed before the plugin was installed or before the
            // system setting was enabled — create the row on first save.
            $DB->insert_record('local_snapmultilangnames_sec', (object) [
                'courseid'     => $courseid,
                'sectionid'    => $sid,
                'lang1'        => $lang1,
                'lang2'        => $lang2,
                'lang3'        => $lang3,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }
    }

    redirect(
        $pageurl,
        get_string('saved', 'local_snapmultilangnames'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Render page.

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursesections', 'local_snapmultilangnames'));
echo $OUTPUT->box(get_string('coursesections_desc', 'local_snapmultilangnames'));
$form->display();
echo $OUTPUT->footer();
