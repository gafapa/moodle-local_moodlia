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
 * Create question external function.
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
use local_moodlia\operation\create_question as create_question_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for create_question.
 */
class create_question extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'context_id' => new external_value(PARAM_INT, 'Question bank context id'),
            'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'question_text' => new external_value(PARAM_RAW, 'Question text'),
            'options' => new external_value(PARAM_RAW, 'JSON-encoded question options'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $categoryid Question category id.
     * @param int $contextid Question bank context id.
     * @param string $questiontype Question type.
     * @param string $name Question name.
     * @param string $questiontext Question text.
     * @param string $options JSON-encoded question options.
     * @return array
     */
    public static function execute(
        int $categoryid,
        int $contextid,
        string $questiontype,
        string $name,
        string $questiontext,
        string $options
    ): array {
        [
            'category_id' => $categoryid,
            'context_id' => $contextid,
            'question_type' => $questiontype,
            'name' => $name,
            'question_text' => $questiontext,
            'options' => $options,
        ] = self::validate_parameters(self::execute_parameters(), [
            'category_id' => $categoryid,
            'context_id' => $contextid,
            'question_type' => $questiontype,
            'name' => $name,
            'question_text' => $questiontext,
            'options' => $options,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $categorycontext = question_tools::require_question_category_context((int) $categoryid, (int) $contextid);
        self::validate_context($categorycontext);
        require_capability('moodle/question:add', $categorycontext);

        return create_question_operation::execute(
            (int) $categoryid,
            $questiontype,
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
        return self::question_returns();
    }

    /**
     * Shared question return structure.
     *
     * @return external_single_structure
     */
    public static function question_returns(): external_single_structure {
        return new external_single_structure([
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
        ]);
    }
}
