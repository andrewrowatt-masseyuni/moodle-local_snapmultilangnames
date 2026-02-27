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

namespace theme_snap;

/**
 * Class util_sections
 *
 * @package    local_snapmultilangnames
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util_sections {

    /**
     * Check if the multilang names feature is enabled for the current course.
     *
     * Both the system-wide setting and the course-level custom field must be
     * enabled. The system-wide setting acts as a global gate.
     *
     * @return bool True if enabled at both system and course level, false otherwise.
     */
    private static function is_enabled(): bool {
        global $COURSE;

        // Check system-wide setting first.
        if (!get_config('local_snapmultilangnames', 'enablemultilangnames')) {
            return false;
        }

        // Check course-level custom field.
        $handler = \core_course\customfield\course_handler::create();
        $data = $handler->export_instance_data_object($COURSE->id, true);
        return !empty($data->enablemultilangnames);
    }

    /**
     * Get the value of the "usemultilanguagesectionnames" course custom field.
     * Field definition: Use multi-language section names | usemultilanguagesectionnames | Dropdown menu | No, Yes, Auto
     *
     * @return string The custom field value, or 'No' if not set.
     */
    private static function get_use_multilanguage_section_names(): string {
        global $COURSE;

        $handler = \core_course\customfield\course_handler::create();
        $data = $handler->export_instance_data_object($COURSE->id, true);
        return $data->usemultilanguagesectionnames ?? 'No';
    }

    public static function format_multilanguage_text(string $text): string {
        if (!self::is_enabled()) {
            return ''; /* Feature is not enabled for this course */
        }

        if (self::get_use_multilanguage_section_names() === 'No') {
            return ''; /* Return blank to short-curcuit downstream rendering processes */
        }

        $parts = explode('|', $text);
        $spans = [];

        foreach ($parts as $index => $part) {
            $trimmed = trim($part);
            $language = self::detect_language($trimmed);
            $spans[] = '<span class="n' . ($index + 1) . '"' . ($language ? " lang=\"$language\"" : '') . '>' . $trimmed . '</span>';
        }

        return '<span class="mlnc">' . implode('', $spans) . '</span>';
    }

    /**
     * Test if a string appears to be in Māori.
     *
     * Checks for Māori language indicators such as macrons (tohutō) and
     * whether the text uses only characters from the Māori alphabet.
     * The Māori alphabet consists of: a, e, h, i, k, m, n, o, p, r, t, u, w
     * and the digraphs ng and wh, plus vowels with macrons (ā, ē, ī, ō, ū).
     *
     * @param string $text The text to test.
     * @return bool True if the text appears to be in Māori, false otherwise.
     */
    public static function is_maori(string $text): bool {
        if (empty(trim($text))) {
            return false;
        }

        // Māori vowels with macrons (tohutō).
        $macrons = ['ā', 'ē', 'ī', 'ō', 'ū', 'Ā', 'Ē', 'Ī', 'Ō', 'Ū'];

        // Check if text contains macrons - strong indicator of Māori.
        foreach ($macrons as $macron) {
            if (mb_strpos($text, $macron) !== false) {
                return true;
            }
        }

        // Valid Māori characters: a, e, h, i, k, m, n, o, p, r, t, u, w
        // Plus macron vowels, spaces, and common punctuation.
        // Pattern matches text that contains ONLY valid Māori characters.
        $pattern = '/^[aehikmnoprtuwāēīōūAEHIKMNOPRTUWĀĒĪŌŪ\s\-\'\.,!?]+$/u';

        return preg_match($pattern, $text) === 1;
    }

    /**
     * Detect the language of a string.
     *
     * Attempts to identify if the text is in Māori, Mandarin Chinese (Putonghua),
     * Japanese, French, or English based on character patterns.
     *
     * @param string $text The text to analyse.
     * @return string The HTML language code: 'mi' (Māori), 'zh' (Mandarin),
     *                'ja' (Japanese), 'fr' (French), or 'en' (English).
     */
    public static function detect_language(string $text): string {
        $text = trim($text);

        if (empty($text)) {
            return 'en';
        }

        // Check for Chinese characters (CJK Unified Ideographs).
        // Chinese uses Han characters without hiragana/katakana.
        $hasChinese = preg_match('/[\x{4e00}-\x{9fff}]/u', $text);

        // Check for Japanese-specific characters (Hiragana and Katakana).
        $hasJapanese = preg_match('/[\x{3040}-\x{309f}\x{30a0}-\x{30ff}]/u', $text);

        // Japanese: Has hiragana/katakana (may also have kanji).
        if ($hasJapanese) {
            return 'ja';
        }

        // Chinese (Putonghua): Has Chinese characters but no Japanese kana.
        if ($hasChinese) {
            return 'zh';
        }

        // Check for Māori - macrons are a strong indicator.
        if (self::is_maori($text)) {
            return 'mi';
        }

        // Check for French-specific characters and patterns.
        // French uses accented characters: é, è, ê, ë, à, â, ù, û, ô, î, ï, ç, œ, æ.
        $frenchPattern = '/[éèêëàâùûôîïçœæÉÈÊËÀÂÙÛÔÎÏÇŒÆ]/u';
        if (preg_match($frenchPattern, $text)) {
            return 'fr';
        }

        // Default to none.
        return '';
    }
}
