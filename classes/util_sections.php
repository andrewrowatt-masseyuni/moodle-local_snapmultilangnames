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
    /** @var array<int,array<int,\stdClass>> Section lang records keyed by [courseid][sectionid]. */
    private static array $sectioncache = [];

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
        self::$sectioncache[$courseid] = [];
        foreach ($records as $rec) {
            self::$sectioncache[$courseid][(int)$rec->sectionid] = $rec;
        }
    }

    /**
     * Check if the multilang names feature is enabled for a specific course.
     *
     * Only checks the course-level custom field; does NOT check the system-wide gate.
     * Use is_enabled() for the full two-gate check.
     *
     * @param int $courseid
     * @return bool
     */
    public static function is_enabled_for_course(int $courseid): bool {
        $handler = \core_course\customfield\course_handler::create();
        $data = $handler->export_instance_data_object($courseid, true);
        return !empty($data->enablemultilangnames);
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
        if (!self::is_enabled_for_course($COURSE->id)) {
            return false;
        }

        // Warm the section cache for this course if not already loaded.
        if (!isset(self::$sectioncache[$COURSE->id])) {
            self::load_section_cache($COURSE->id);
        }

        return true;
    }

    /**
     * Returns BCP47 code => label pairs from the configured language list.
     *
     * Reads the 'languages' admin setting. Each line must be in "code|label"
     * format. Returns an empty array if the setting is empty or unparseable.
     *
     * @return array<string,string>
     */
    /** @var string Built-in language list used when the admin setting has not been saved yet. */
    private const DEFAULT_LANGUAGES = "en|English (en)\nmi|M\u{0101}ori (mi)\nfr|French (fr)\nzh|Chinese (zh)\nja|Japanese (ja)";

    public static function get_language_options(): array {
        $raw = get_config('local_snapmultilangnames', 'languages');
        if ($raw === false || trim($raw) === '') {
            $raw = self::DEFAULT_LANGUAGES;
        }
        $options = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode('|', $line, 2);
            if (\count($parts) === 2) {
                $code  = trim($parts[0]);
                $label = trim($parts[1]);
                if ($code !== '') {
                    $options[$code] = $label;
                }
            }
        }
        return $options;
    }

    /**
     * Returns the HTML for a multilang section name, or '' if not applicable.
     *
     * @param \stdClass $section A record from course_sections (must have id and name).
     * @return string
     */
    public static function get_multilang_html($section): string {
        global $COURSE;

        if (!self::is_enabled()) {
            return ''; /* Feature is not enabled for this course */
        }

        $secdata = self::$sectioncache[$COURSE->id][(int)$section->id] ?? null;

        $html = '';

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
