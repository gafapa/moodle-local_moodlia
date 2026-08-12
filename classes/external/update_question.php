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
 * Update question external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\question_tools;
use local_moodlia\operation\update_question as update_question_operation;

/**
 * External API adapter for update_question.
 */
class update_question extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'name' => new external_value(PARAM_TEXT, 'Question name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'question_text' => new external_value(PARAM_RAW, 'Question text', VALUE_DEFAULT, null, NULL_ALLOWED),
            'options' => new external_value(PARAM_RAW, 'JSON-encoded question options', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $questionid Question id.
     * @param string|null $name Question name.
     * @param string|null $questiontext Question text.
     * @param string $options JSON-encoded question options.
     * @return array
     */
    public static function execute(
        int $questionid,
        ?string $name = null,
        ?string $questiontext = null,
        string $options = '{}'
    ): array {
        [
            'question_id' => $questionid,
            'name' => $name,
            'question_text' => $questiontext,
            'options' => $options,
        ] = self::validate_parameters(self::execute_parameters(), [
            'question_id' => $questionid,
            'name' => $name,
            'question_text' => $questiontext,
            'options' => $options,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $question = question_tools::get_question((int) $questionid);
        question_require_capability_on($question, 'edit');

        return update_question_operation::execute(
            (int) $questionid,
            $name,
            $questiontext,
            question_tools::decode_options($options)
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return create_question::question_returns();
    }
}
