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
 * Class util_sections
 *
 * @package    local_snapmultilangnames
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util_sections {

    /** @var array<int,\stdClass>|null Section lang records keyed by sectionid, null until loaded. */
    private static ?array $section_cache = null;

    /**
     * Bulk-load all local_snapmultilangnames_sec rows for a course into the static cache.
     *
     * @param int $courseid
     */
    private static function load_section_cache(int $courseid): void {
        global $DB;
        $records = $DB->get_records(
            'local_snapmultilangnames_sec',
            ['courseid' => $courseid],
            '',
            'sectionid, lang1, lang2, lang3'
        );
        self::$section_cache = [];
        foreach ($records as $rec) {
            self::$section_cache[(int)$rec->sectionid] = $rec;
        }
    }

    /**
     * Check if the multilang names feature is enabled for the current course.
     *
     * Both the system-wide setting and the course-level custom field must be
     * enabled. The system-wide setting acts as a global gate.
     *
     * @return bool True if enabled at both system and course level, false otherwise.
     */
    public static function is_enabled(): bool {
        global $COURSE;

        // Check system-wide setting first.
        if (!get_config('local_snapmultilangnames', 'enablemultilangnames')) {
            return false;
        }

        // Check course-level custom field.
        $handler = \core_course\customfield\course_handler::create();
        $data = $handler->export_instance_data_object($COURSE->id, true);
        $enabled = "Yes" === ($data->enablemultilangnames ?? 'No');
        if ($enabled && self::$section_cache === null) {
            self::load_section_cache($COURSE->id);
        }
        return $enabled;
    }

    public static function get_multilang_html($section): string {
        if (!self::is_enabled()) {
            return ''; /* Feature is not enabled for this course */
        }

        $secdata = self::$section_cache[(int)$section->id] ?? null;

        $html ='';

        if ($secdata) {
            $html = '<span class="mlnc">';

            $parts = explode('|', $section->name ?? '', 4);
            if (isset($parts[3])) {
                $parts[2] = "{$parts[2]}|{$parts[3]}";
                unset($parts[3]);
            }
            $spans = [];

            foreach ($parts as $index => $part) {
                $trimmed = trim($part);
                $langindex = $index + 1;
                $langfield = "lang$langindex";
                $language = $secdata->$langfield ?? '';
                $spans[] = "<span class=\"n$langindex\"" . ($language ? " lang=\"$language\"" : '') . '>' . $trimmed . '</span>';
            }

            $html .= implode('', $spans);

            $html .= '</span>';
        }

        return $html;
    }

}
