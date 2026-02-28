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
 * Custom Behat step definitions for local_snapmultilangnames.
 *
 * @package    local_snapmultilangnames
 * @category   test
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL check here — Behat context files are not loaded through Moodle bootstrap.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Step definitions for the local_snapmultilangnames plugin.
 */
class behat_local_snapmultilangnames extends behat_base {
    /**
     * Enables the course-level "Enable multilang names" custom field for a course
     * by writing directly to the customfield_data table.
     *
     * This avoids navigating through the course-edit form, keeping test scenarios lean.
     *
     * @Given the multilang names feature is enabled for course :shortname
     * @param string $shortname Course shortname.
     */
    public function the_multilang_names_feature_is_enabled_for_course(string $shortname): void {
        global $DB;

        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $fieldid  = $DB->get_field('customfield_field', 'id', ['shortname' => 'enablemultilangnames'], MUST_EXIST);
        $context  = context_course::instance($courseid);

        $existing = $DB->get_record('customfield_data', ['fieldid' => $fieldid, 'instanceid' => $courseid]);
        if ($existing) {
            $DB->update_record('customfield_data', (object)[
                'id'           => $existing->id,
                'intvalue'     => 1,
                'value'        => '1',
                'timemodified' => time(),
            ]);
        } else {
            $DB->insert_record('customfield_data', (object)[
                'fieldid'      => $fieldid,
                'instanceid'   => $courseid,
                'intvalue'     => 1,
                'value'        => '1',
                'valueformat'  => FORMAT_HTML,
                'timecreated'  => time(),
                'timemodified' => time(),
                'contextid'    => $context->id,
            ]);
        }
    }

    /**
     * Navigates directly to the course sections management page.
     *
     * @Given I am on the multilang settings page for course :shortname
     * @param string $shortname Course shortname.
     */
    public function i_am_on_multilang_settings_page_for_course(string $shortname): void {
        global $DB;

        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $url = new moodle_url('/local/snapmultilangnames/course_sections.php', ['courseid' => $courseid]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url()));
        $this->wait_for_pending_js();
    }

    /**
     * Asserts the dropdown toggle button for a specific section and component
     * displays the expected label text.
     *
     * @Then section :secnum component :comp dropdown should show :label
     * @param int    $secnum Section number shown in the card header.
     * @param int    $comp   Component index, 1-based.
     * @param string $label  Expected button label, e.g. "English (en)".
     */
    public function dropdown_should_show(int $secnum, int $comp, string $label): void {
        $js = sprintf(
            '(function () {
                var card = document.querySelector("[data-section-num=\'%1$d\']");
                if (!card) return null;
                var rows = card.querySelectorAll(".mlnc-form-preview > .d-flex");
                var row  = rows[%2$d];
                if (!row)  return null;
                var toggle = row.querySelector(".dropdown-toggle");
                return toggle ? toggle.textContent.trim() : null;
            }());',
            $secnum,
            $comp - 1
        );
        $actual = $this->getSession()->evaluateScript($js);
        \PHPUnit\Framework\Assert::assertSame(
            $label,
            $actual,
            sprintf('Section %d component %d: expected dropdown label "%s", got "%s"', $secnum, $comp, $label, $actual)
        );
    }
}
