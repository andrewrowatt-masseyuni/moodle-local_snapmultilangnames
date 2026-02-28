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
 * Plugin hooks for local_snapmultilangnames.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a "Manage multilang section names" link to the course navigation.
 *
 * Three conditions must all be true before the link is shown:
 *   1. The system-wide setting local_snapmultilangnames/enablemultilangnames is on.
 *   2. The course-level custom field "enablemultilangnames" (checkbox) is checked.
 *   3. The current user has local/snapmultilangnames:managemultilangsettings in the
 *      course context.
 *
 * @param navigation_node $coursenode    The course navigation node to extend.
 * @param stdClass        $course        The course record.
 * @param context         $coursecontext The course context.
 * @return void
 */
function local_snapmultilangnames_extend_navigation_course(
    navigation_node $coursenode,
    stdClass $course,
    context $coursecontext
): void {
    global $PAGE;

    // 1. System-wide gate (cheap config lookup, no DB query).
    if (!get_config('local_snapmultilangnames', 'enablemultilangnames')) {
        return;
    }

    // 2. Course-level custom field gate.
    if (!\local_snapmultilangnames\util_sections::is_enabled_for_course($course->id)) {
        return;
    }

    // 3. Capability check (most expensive, so checked last).
    if (!has_capability('local/snapmultilangnames:managemultilangsettings', $coursecontext)) {
        return;
    }

    $url = new moodle_url(
        '/local/snapmultilangnames/course_sections.php',
        ['courseid' => $course->id]
    );

    $node = navigation_node::create(
        get_string('managemultilangsettings', 'local_snapmultilangnames'),
        $url,
        navigation_node::NODETYPE_LEAF,
        'local_snapmultilangnames',
        'local_snapmultilangnames',
        new pix_icon('i/settings', '')
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $coursenode->add_node($node);
}
