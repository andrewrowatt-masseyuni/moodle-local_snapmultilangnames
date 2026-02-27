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

namespace local_snapmultilangnames\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for managing per-section language settings.
 *
 * Expected customdata keys:
 *   - courseid  (int)   The course ID.
 *   - sections  (array) Records from course_sections ordered by section number,
 *                       indexed by id.
 *   - langdata  (array) Existing lang-settings records keyed by sectionid,
 *                       each a stdClass with lang1/lang2/lang3.
 *
 * Field naming convention: lang1_{sectionid}, lang2_{sectionid}, lang3_{sectionid}.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_sections_form extends \moodleform {

    /**
     * Returns the select options for a language dropdown.
     * Empty string represents "auto-detect".
     *
     * @return array
     */
    private static function lang_options(): array {
        return [
            ''   => get_string('lang_auto', 'local_snapmultilangnames'),
            'en' => get_string('lang_en',   'local_snapmultilangnames'),
            'mi' => get_string('lang_mi',   'local_snapmultilangnames'),
            'fr' => get_string('lang_fr',   'local_snapmultilangnames'),
            'zh' => get_string('lang_zh',   'local_snapmultilangnames'),
            'ja' => get_string('lang_ja',   'local_snapmultilangnames'),
        ];
    }

    /**
     * Returns a human-readable language label for a BCP47 code, e.g. "Māori (MI)".
     * Empty string (auto-detect with no resolved language) returns "Auto-detect".
     *
     * @param string $code BCP47 language code or '' for unresolved auto-detect.
     * @return string
     */
    private static function lang_display_label(string $code): string {
        if ($code === '') {
            return get_string('lang_auto', 'local_snapmultilangnames');
        }
        $names = [
            'en' => 'English',
            'mi' => 'Māori',
            'fr' => 'French',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
        ];
        $name = $names[$code] ?? $code;
        return $name . ' (' . strtoupper($code) . ')';
    }

    /**
     * Build the form definition.
     */
    public function definition(): void {
        $mform      = $this->_form;
        $courseid   = (int) $this->_customdata['courseid'];
        $sections   = $this->_customdata['sections'];
        $langdata   = $this->_customdata['langdata'];
        $langopts   = self::lang_options();
        $langkeys   = ['lang1', 'lang2', 'lang3'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        foreach ($sections as $section) {
            $sid        = (int) $section->id;
            $sectionnum = (int) $section->section;
            $rawname    = isset($section->name) ? (string) $section->name : '';

            // Parse name into pipe-delimited components (at most 3).
            $parts    = array_map('trim', explode('|', $rawname));
            $numparts = min(count($parts), 3);

            // Retrieve saved lang values for this section.
            $existing = $langdata[$sid] ?? null;
            $defaults = [
                'lang1' => $existing->lang1 ?? '',
                'lang2' => $existing->lang2 ?? '',
                'lang3' => $existing->lang3 ?? '',
            ];

            // Section header.
            $headerlabel = get_string('section_number', 'local_snapmultilangnames', $sectionnum);
            if ($rawname !== '') {
                $headerlabel .= ': ' . s($rawname);
            }
            $mform->addElement('header', 'section_hdr_' . $sid, $headerlabel);

            // Static preview of the current language markup.
            $previewhtml = $this->build_preview_html($parts, $numparts, $defaults);
            $mform->addElement('html',
                '<div class="mlnc_preview">' . $previewhtml . '</div>');

            // One language select per name component.
            for ($i = 0; $i < $numparts; $i++) {
                $fieldname = $langkeys[$i] . '_' . $sid;
                $label     = get_string('component', 'local_snapmultilangnames', $i + 1);
                if ($parts[$i] !== '') {
                    $label .= ': ' . s($parts[$i]);
                }
                $mform->addElement('select', $fieldname, $label, $langopts);
                $mform->setType($fieldname, PARAM_NOTAGS);
                $mform->setDefault($fieldname, $defaults[$langkeys[$i]]);
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Build the preview HTML for one section.
     *
     * For each component, if the stored language is '' (auto-detect) the
     * existing detect_language() utility is called to determine the lang
     * attribute. A blank detect result (default English) produces no lang
     * attribute so the browser inherits the page language.
     *
     * @param array  $parts    Pipe-split name components (already trimmed).
     * @param int    $numparts Number of components to render (max 3).
     * @param array  $defaults Assoc: lang1/lang2/lang3 => BCP47 code or ''.
     * @return string HTML snippet (span.mlnc with inner span.n1/n2/n3).
     */
    private function build_preview_html(array $parts, int $numparts, array $defaults): string {
        if ($numparts === 0) {
            return '';
        }

        $langkeys = ['lang1', 'lang2', 'lang3'];
        $spans    = '';

        for ($i = 0; $i < $numparts; $i++) {
            $text     = $parts[$i];
            $override = $defaults[$langkeys[$i]];

            $lang     = $override ?: \local_snapmultilangnames\util_sections::detect_language($text);
            $datalang = self::lang_display_label($lang);

            $langattr     = ($lang !== '') ? ' lang="' . s($lang) . '"' : '';
            $datalangattr = ' data-language="' . s($datalang) . '"';
            $spans       .= '<span class="n' . ($i + 1) . '"' . $langattr . $datalangattr . '>'
                          . s($text) . '</span>';
        }

        return '<span class="mlnc">' . $spans . '</span>';
    }

    /**
     * Validate submitted language values.
     *
     * @param array $data  Submitted form data.
     * @param array $files Submitted files (unused).
     * @return array Validation errors keyed by field name.
     */
    public function validation($data, $files): array {
        $errors  = parent::validation($data, $files);
        $allowed = array_keys(self::lang_options());

        foreach ($data as $fieldname => $value) {
            if (preg_match('/^lang[123]_\d+$/', $fieldname)) {
                if (!in_array($value, $allowed, true)) {
                    $errors[$fieldname] = get_string('invaliddata', 'error');
                }
            }
        }

        return $errors;
    }
}
