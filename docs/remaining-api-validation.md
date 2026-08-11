# Remaining API Validation

MoodlIA intentionally avoids activity subelement writes unless Moodle exposes a stable component API path that can be validated through smoke tests. This note records the current validation status for the remaining high-risk areas from the seven-point implementation plan.

## Feedback Item Creation And Update

Status: complete for Moodle 5.0 core Feedback item types.

Moodle 4.5 exposes item-type classes through `feedback_get_item_class()`. MoodlIA now exposes `create_feedback_item` and `update_feedback_item` for `textfield`, `textarea`, `numeric`, `multichoice`, `multichoicerated`, `label`, and `info`. Captcha creation is exposed as a narrow create-only operation through Moodle's `feedback_item_captcha` class; updates, duplicate captcha items, non-empty definitions, optional dependencies, and non-required captcha settings are rejected before Moodle can reach the item class `notice()/exit` paths. Pagebreak creation uses Moodle's `feedback_create_pagebreak()` helper, matching the component edit UI. The operation builds a narrow type-specific payload and delegates persistence to Moodle's item class via `build_editform()`, `set_data()`, and `save_item()` where the item type has a writer class. Position changes use Moodle's `feedback_move_item()` and `feedback_renumber_items()`. MoodlIA does not write `feedback_item` tables directly.

Primary sources:

- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/edit_item.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/feedback_item_class.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/textfield/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/textarea/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/numeric/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/multichoice/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/multichoicerated/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/label/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/info/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/feedback/item/captcha/lib.php

Implemented evidence:

- Supported item types are limited to `textfield`, `textarea`, `numeric`, `multichoice`, `multichoicerated`, `label`, `info`, captcha creation, and pagebreak creation.
- Mutation requires `mod/feedback:edititems`.
- Ownership validation verifies that referenced item ids belong to the selected Feedback activity.
- Persistence goes through Moodle item classes and type-specific `save_item()` methods.
- Static coverage verifies contract, REST, MCP, CLI, services, capabilities, and no direct `$DB` usage in MoodlIA's operation boundary.

Required evidence before extending beyond Moodle core:

- Type-specific payload contracts and smoke coverage for any third-party item type.
- Evidence that updating items after responses exist preserves Moodle response-value semantics.

## Lesson Page Mutation

Status: complete for content pages and Moodle 5.0 core question pages.

Moodle exposes `lesson_page::create(...)`, `lesson_page::update(...)`, and page `delete()` methods. MoodlIA uses those component APIs for content pages and all six core question types: truefalse, shortanswer, multichoice, numerical, essay, and matching. Essay pages expose their manual-grading jump and score settings. Matching pages expose correct/wrong feedback, scores, jumps, and at least two unique prompt/match pairs.

Primary sources:

- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/lesson/locallib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/lesson/pagetypes/truefalse.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/lesson/pagetypes/shortanswer.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/lesson/pagetypes/multichoice.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/lesson/pagetypes/numerical.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_500_STABLE/mod/lesson/pagetypes/essay.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_500_STABLE/mod/lesson/pagetypes/matching.php

Implemented evidence:

- Supported page types include content pages with branch definitions and every Moodle 5.0 core question type.
- Truefalse answer payloads validate non-empty unique answer text, response text, Moodle text formats, jump targets, and numeric scores before delegating to Moodle's page APIs.
- Shortanswer and numerical answer payloads validate non-empty unique answer text, response text, Moodle text formats, jump targets, numeric scores, optional regular-expression mode, and numerical range syntax before delegating to Moodle's page APIs.
- Essay payloads validate manual-grading jump and score settings. Matching payloads validate unique non-empty prompts, unique plain-text matches, correct/wrong feedback, text formats, scores, jumps, and the Lesson maximum-answer limit.
- Ownership checks verify that every target page belongs to the selected Lesson module before update or delete.
- Static coverage requires contract, REST, MCP, CLI, services, helper APIs, and smoke syntax for supported content and question page create/update/delete paths.

Required evidence before extending beyond core question pages:

- Navigation and ordering invariants for structural cluster and end-marker pages.
- Smoke tests for any future structural page mutation.

## Workshop Grading Form Mutation

Status: complete for Moodle 5.0 core Workshop grading strategies.

Moodle Workshop grading-form subplugins expose strategy-specific `save_edit_strategy_form(...)` methods. MoodlIA exposes `set_workshop_grading_form` for every Moodle 5.0 core strategy—`accumulative`, `comments`, `numerrors`, and `rubric`—in setup phase only. The operation builds the form-shaped data required by Moodle and delegates persistence to the Workshop strategy instance instead of writing strategy tables directly. Number-of-errors writes include the dimension assertions and error-count grade mapping expected by Moodle's strategy form. Rubric replacement includes existing dimension and level ids from the Moodle strategy object so the subplugin can delete old rows through its own save path. Third-party strategies remain blocked until their payload contracts are narrowed and smoke tested.

Primary sources:

- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/workshop/form/rubric/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/workshop/form/numerrors/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/workshop/form/accumulative/lib.php
- https://raw.githubusercontent.com/moodle/moodle/MOODLE_405_STABLE/mod/workshop/form/comments/lib.php

Implemented evidence:

- Supported strategies are limited to `accumulative`, `comments`, `numerrors`, and `rubric`.
- Mutation requires setup phase and `mod/workshop:editdimensions`.
- Persistence goes through `grading_strategy_instance()->save_edit_strategy_form(...)`.
- Static coverage verifies contract, REST, MCP, CLI, services, capabilities, and no direct `$DB` usage in the operation.

Required evidence before extending beyond Moodle core:

- Exact public payload schema per third-party strategy.
- Capability and phase rules for mutating the form after submissions or assessments exist.
- A smoke test that writes a form definition, reads it back through `get_workshop_assessment_form_definition`, and verifies assessment updates still work.
