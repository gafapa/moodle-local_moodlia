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
 * Shared helpers for Moodle quiz module settings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Handles module options for quiz activities.
 */
class module_quiz_tools {
    /**
     * Add quiz-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_quiz_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? '');
        $moduleinfo->timeopen = self::optional_int($options, 'time_open', 0);
        $moduleinfo->timeclose = self::optional_int($options, 'time_close', 0);
        if ($moduleinfo->timeopen > 0 && $moduleinfo->timeclose > 0 && $moduleinfo->timeclose <= $moduleinfo->timeopen) {
            throw new \invalid_parameter_exception('options.time_close must be greater than options.time_open.');
        }
        $moduleinfo->timelimit = self::optional_int($options, 'time_limit_seconds', 0);
        $moduleinfo->overduehandling = self::normalise_quiz_overdue_handling((string) ($options['overdue_handling'] ?? 'autosubmit'));
        $moduleinfo->graceperiod = self::optional_int($options, 'grace_period_seconds', 0);
        $moduleinfo->preferredbehaviour = self::normalise_quiz_behaviour((string) ($options['preferred_behaviour'] ?? 'deferredfeedback'));
        $moduleinfo->canredoquestions = self::optional_bool($options, 'can_redo_questions', 0);
        $moduleinfo->attempts = self::optional_int($options, 'attempts', 0);
        $moduleinfo->attemptonlast = self::optional_bool($options, 'attempt_on_last', 0);
        $moduleinfo->grademethod = self::normalise_quiz_grade_method((string) ($options['grade_method'] ?? 'highest'));
        $moduleinfo->decimalpoints = self::normalise_decimal_points($options['decimal_points'] ?? 2, false);
        $moduleinfo->questiondecimalpoints = self::normalise_decimal_points($options['question_decimal_points'] ?? -1, true);
        $moduleinfo->reviewattempt = 0x11110;
        $moduleinfo->reviewcorrectness = 0x10000;
        $moduleinfo->reviewmaxmarks = 0x11110;
        $moduleinfo->reviewmarks = 0x11110;
        $moduleinfo->reviewspecificfeedback = 0x10000;
        $moduleinfo->reviewgeneralfeedback = 0x10000;
        $moduleinfo->reviewrightanswer = 0x10000;
        $moduleinfo->reviewoverallfeedback = 0x11110;
        $moduleinfo->questionsperpage = self::optional_int($options, 'questions_per_page', 1);
        $moduleinfo->navmethod = self::normalise_quiz_navigation((string) ($options['navigation_method'] ?? 'free'));
        $moduleinfo->shuffleanswers = self::optional_bool($options, 'shuffle_answers', 1);
        $moduleinfo->sumgrades = 0;
        $moduleinfo->grade = (float) ($options['grade'] ?? 10);
        $password = (string) ($options['password'] ?? $options['quiz_password'] ?? '');
        $moduleinfo->quizpassword = $password;
        $moduleinfo->password = $password;
        $moduleinfo->subnet = clean_param((string) ($options['network_address'] ?? $options['subnet'] ?? ''), PARAM_RAW_TRIMMED);
        $moduleinfo->browsersecurity = self::normalise_quiz_browser_security((string) ($options['browser_security'] ?? 'none'));
        $moduleinfo->delay1 = self::optional_int($options, 'delay_first_second_seconds', 0);
        $moduleinfo->delay2 = self::optional_int($options, 'delay_later_seconds', 0);
        $moduleinfo->showuserpicture = self::normalise_quiz_user_picture((string) ($options['show_user_picture'] ?? 'none'));
        $moduleinfo->showblocks = self::optional_bool($options, 'show_blocks', 0);
        $moduleinfo->completionattemptsexhausted = 0;
        $moduleinfo->completionminattempts = 0;
        $moduleinfo->completionpass = 0;
        $moduleinfo->allowofflineattempts = self::optional_bool($options, 'allow_offline_attempts', 0);
        $moduleinfo->precreateattempts = 0;
    }

    /**
     * Return an optional boolean module option as an integer.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param int $default Default.
     * @return int
     */
    private static function optional_bool(array $options, string $name, int $default): int {
        return array_key_exists($name, $options) ? (int) (bool) $options[$name] : $default;
    }

    /**
     * Return an optional positive integer module option.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param int $default Default.
     * @param int $minimum Minimum.
     * @return int
     */
    private static function optional_int(array $options, string $name, int $default, int $minimum = 0): int {
        $value = array_key_exists($name, $options) ? (int) $options[$name] : $default;
        if ($value < $minimum) {
            throw new \invalid_parameter_exception("options.$name must be at least $minimum.");
        }

        return $value;
    }

    /**
     * Map public quiz grade method values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_quiz_grade_method(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'highest' => QUIZ_GRADEHIGHEST,
            'average' => defined('QUIZ_GRADEAVERAGE') ? QUIZ_GRADEAVERAGE : 2,
            'first' => defined('QUIZ_ATTEMPTFIRST') ? QUIZ_ATTEMPTFIRST : 3,
            'last' => defined('QUIZ_ATTEMPTLAST') ? QUIZ_ATTEMPTLAST : 4,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.grade_method must be one of: highest, average, first, last.');
        }

        return $map[$key];
    }

    /**
     * Map public quiz navigation values.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_quiz_navigation(string $value): string {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'free' => QUIZ_NAVMETHOD_FREE,
            'sequential' => defined('QUIZ_NAVMETHOD_SEQ') ? QUIZ_NAVMETHOD_SEQ : 'sequential',
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.navigation_method must be one of: free, sequential.');
        }

        return $map[$key];
    }

    /**
     * Validate a public quiz question behaviour.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_quiz_behaviour(string $value): string {
        $behaviour = clean_param($value, PARAM_PLUGIN);
        $allowed = [
            'adaptive',
            'adaptivenopenalty',
            'deferredcbm',
            'deferredfeedback',
            'immediatecbm',
            'immediatefeedback',
            'interactive',
        ];
        if (!in_array($behaviour, $allowed, true)) {
            throw new \invalid_parameter_exception(
                'options.preferred_behaviour must be one of: adaptive, adaptivenopenalty, deferredcbm, deferredfeedback, immediatecbm, immediatefeedback, interactive.'
            );
        }

        return $behaviour;
    }

    /**
     * Validate quiz overdue handling.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_quiz_overdue_handling(string $value): string {
        $key = clean_param($value, PARAM_ALPHA);
        $allowed = ['autosubmit', 'graceperiod', 'autoabandon'];
        if (!in_array($key, $allowed, true)) {
            throw new \invalid_parameter_exception('options.overdue_handling must be one of: autosubmit, graceperiod, autoabandon.');
        }

        return $key;
    }

    /**
     * Validate decimal point settings.
     *
     * @param mixed $value Value.
     * @param bool $allowinherit Allowinherit.
     * @return int
     */
    private static function normalise_decimal_points($value, bool $allowinherit): int {
        $points = (int) $value;
        $minimum = $allowinherit ? -1 : 0;
        if ($points < $minimum || $points > 7) {
            $message = $allowinherit
                ? 'options.question_decimal_points must be between -1 and 7.'
                : 'options.decimal_points must be between 0 and 7.';
            throw new \invalid_parameter_exception($message);
        }

        return $points;
    }

    /**
     * Map public browser security settings.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_quiz_browser_security(string $value): string {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'none' => '-',
            'popup' => 'popup',
            'securewindow' => 'securewindow',
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.browser_security must be one of: none, popup, securewindow.');
        }

        return $map[$key];
    }

    /**
     * Map public show user picture settings.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_quiz_user_picture(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'none' => 0,
            'small' => 1,
            'large' => 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.show_user_picture must be one of: none, small, large.');
        }

        return $map[$key];
    }
}
