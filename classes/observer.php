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

namespace local_snapmultilangnames;

/**
 * Event observer for local_snapmultilangnames.
 *
 * Maintains the local_snapmultilangnames_sec table in sync with course sections.
 * When a section is created, a row is inserted with all language fields set to ''
 * (auto-detect). When a section is deleted, the corresponding row is removed.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Triggered when a course section is created.
     *
     * Inserts a lang-settings row with all language fields set to '' (auto-detect).
     * Only acts when the system-wide feature gate is enabled; if the gate is off,
     * no row is inserted and the management form will create one on first save.
     *
     * @param \core\event\course_section_created $event
     */
    public static function course_section_created(\core\event\course_section_created $event): void {
        global $DB;

        // Guard: system-wide feature must be enabled.
        if (!get_config('local_snapmultilangnames', 'enablemultilangnames')) {
            return;
        }

        // Guard: do not duplicate rows (e.g. during course restore).
        if ($DB->record_exists('local_snapmultilangnames_sec', [
            'courseid'  => $event->courseid,
            'sectionid' => $event->objectid,
        ])) {
            return;
        }

        $now = time();
        $DB->insert_record('local_snapmultilangnames_sec', (object) [
            'courseid'     => $event->courseid,
            'sectionid'    => $event->objectid,
            'lang1'        => '',
            'lang2'        => '',
            'lang3'        => '',
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Triggered when a course section is deleted.
     *
     * Removes the corresponding lang-settings row if it exists.
     * This runs unconditionally (regardless of system setting) to ensure
     * no orphaned rows are left behind.
     *
     * @param \core\event\course_section_deleted $event
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event): void {
        global $DB;

        $DB->delete_records('local_snapmultilangnames_sec', [
            'courseid'  => $event->courseid,
            'sectionid' => $event->objectid,
        ]);
    }
}
