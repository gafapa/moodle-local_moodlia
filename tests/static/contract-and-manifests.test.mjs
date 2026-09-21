import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import test from 'node:test';

import { buildManifests } from '../../tools/generate-manifests.mjs';
import {
  assertSameSet,
  assertValidContract,
  getOperationsByTransport,
  loadContract,
  readJson,
  toRestFunctionName
} from '../helpers/contract.mjs';
import { fromRoot } from '../helpers/paths.mjs';

test('canonical operation contract is structurally valid', async () => {
  assertValidContract(await loadContract());
});

test('generated manifests are current', async () => {
  const expected = buildManifests(await loadContract());

  for (const [relativePath, manifest] of Object.entries(expected)) {
    assert.deepEqual(await readJson(relativePath), manifest, `${relativePath} must be generated from the contract.`);
  }
});

test('REST declarations match the canonical contract', async () => {
  const contract = await loadContract();
  const source = await fs.readFile(fromRoot('db/services.php'), 'utf8');
  const declared = [...source.matchAll(/^\s*'(local_moodlia_[a-z0-9_]+)'\s*=>\s*\[/gm)].map((match) => match[1]);
  const expected = getOperationsByTransport(contract, 'rest').map((operation) => toRestFunctionName(contract, operation.name));

  assertSameSet(declared, expected, 'Moodle REST declarations');
});

test('every REST operation has operation and external PHP classes', async () => {
  const contract = await loadContract();

  for (const operation of getOperationsByTransport(contract, 'rest')) {
    await fs.access(fromRoot('classes/operation', `${operation.name}.php`));
    await fs.access(fromRoot('classes/external', `${operation.name}.php`));
  }
});

test('plugin management writes require the dedicated capability', async () => {
  const contract = await loadContract();
  const operation = contract.operations.find((entry) => entry.name === 'set_plugin_enabled');

  assert.ok(operation);
  assert.equal(operation.type, 'write');
  assert.ok(operation.capabilities.includes('local/moodlia:manageplugins'));
  assert.ok(operation.tests.includes('parity'));
});

test('section summary inputs preserve HTML through the REST adapters', async () => {
  for (const operation of ['create_section', 'update_section']) {
    const source = await fs.readFile(fromRoot('classes/external', `${operation}.php`), 'utf8');
    assert.match(
      source,
      /'summary'\s*=>\s*new external_value\(PARAM_RAW,/,
      `${operation} must preserve HTML summary input before Moodle stores it.`
    );
  }
});

test('section updates preserve the stored format and resolve section-file URLs', async () => {
  const [contract, operationSource, sectionToolsSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/operation/update_section.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/section_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);
  const updateSection = contract.operations.find((operation) => operation.name === 'update_section');

  assert.equal(updateSection.files, 'upload');
  assert.equal(updateSection.parameters.filename.type, 'string');
  assert.equal(updateSection.parameters.upload_reference.type, 'string');
  assert.equal(updateSection.parameters.draft_item_id.type, 'integer');
  assert.ok(Array.isArray(updateSection.returns.uploaded_files));
  assert.ok(Array.isArray(updateSection.returns.summary_files));
  assert.equal(updateSection.returns.summary_raw, 'string');
  assert.equal(updateSection.returns.uploaded_files[0].content_hash, 'string');
  assert.match(operationSource, /\$section->summaryformat/);
  assert.doesNotMatch(operationSource, /format_to_constant\(\$summaryformat \?\? 'plain'\)/);
  assert.match(sectionToolsSource, /file_rewrite_pluginfile_urls\(/);
  assert.match(sectionToolsSource, /'course',\s*'section'/);
  assert.match(sectionToolsSource, /'clean'\s*=>\s*false/);
  assert.match(sectionToolsSource, /attach_summary_files/);
  assert.match(sectionToolsSource, /get_area_files\(/);
  assert.match(mcpManifestSource, /'name'\s*=>\s*'update_section'[\s\S]*?'upload_reference'/);
  assert.match(mcpManifestSource, /'name'\s*=>\s*'update_section'[\s\S]*?'draft_item_id'/);
});

test('assignment updates expose authoring fields and normalise Moodle form defaults', async () => {
  const [contract, externalSource, operationSource, assignmentToolsSource, moduleToolsSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/external/update_assignment.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/update_assignment.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/assignment_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/module_common_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);
  const operation = contract.operations.find((entry) => entry.name === 'update_assignment');

  assert.equal(operation.files, 'upload');
  assert.deepEqual(operation.parameters.intro_format.enum, ['html', 'plain']);
  assert.deepEqual(operation.parameters.activity_format.enum, ['html', 'plain']);
  assert.deepEqual(operation.parameters.file_area.enum, ['intro', 'activity']);
  assert.equal(operation.parameters.draft_item_id.type, 'integer');
  assert.ok(Array.isArray(operation.returns.uploaded_files));
  assert.ok(Array.isArray(operation.returns.intro_files));
  assert.ok(Array.isArray(operation.returns.activity_files));
  assert.equal(operation.returns.uploaded_files[0].content_hash, 'string');
  assert.match(externalSource, /'intro'\s*=>\s*new external_value\(PARAM_RAW,/);
  assert.match(externalSource, /'activity'\s*=>\s*new external_value\(/);
  assert.match(operationSource, /update_moduleinfo\(/);
  assert.match(operationSource, /copy_upload_to_editor_draft/);
  assert.match(assignmentToolsSource, /get_editor_files_response/);
  assert.match(assignmentToolsSource, /get_area_files\(/);
  assert.match(assignmentToolsSource, /normalise_numeric_form_fields\(\$moduledata\)/);
  assert.match(moduleToolsSource, /unformat_float\(\$value, true\)/);
  assert.match(mcpManifestSource, /'name'\s*=>\s*'update_assignment'[\s\S]*?'draft_item_id'/);
});

test('resource updates replace files without recreating the Moodle module', async () => {
  const [contract, externalSource, operationSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/external/update_resource.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/update_resource.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);
  const operation = contract.operations.find((entry) => entry.name === 'update_resource');

  assert.equal(operation.files, 'upload');
  assert.equal(operation.parameters.filename.required, true);
  assert.equal(operation.parameters.upload_reference.type, 'string');
  assert.equal(operation.parameters.draft_item_id.type, 'integer');
  assert.deepEqual(operation.parameters.intro_format.enum, ['html', 'plain']);
  assert.ok(Array.isArray(operation.returns.files));
  assert.match(externalSource, /'draft_item_id'\s*=>\s*new external_value\(PARAM_INT,/);
  assert.match(operationSource, /get_moduleinfo_data\(/);
  assert.match(operationSource, /update_moduleinfo\(/);
  assert.match(operationSource, /get_resource_files\(/);
  assert.match(mcpManifestSource, /'name'\s*=>\s*'update_resource'[\s\S]*?'draft_item_id'/);
});

test('Book chapter mutations expose native editor uploads on every transport', async () => {
  const [contract, externalCreate, externalUpdate, operationSource, bookToolsSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/external/create_book_chapter.php'), 'utf8'),
    fs.readFile(fromRoot('classes/external/update_book_chapter.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/book_chapter_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/book_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);

  for (const name of ['create_book_chapter', 'update_book_chapter']) {
    const operation = contract.operations.find((entry) => entry.name === name);
    assert.equal(operation.files, 'upload');
    assert.equal(operation.parameters.filename.type, 'string');
    assert.equal(operation.parameters.upload_reference.type, 'string');
    assert.equal(operation.parameters.draft_item_id.type, 'integer');
    assert.ok(Array.isArray(operation.returns.uploaded_files));
    assert.match(mcpManifestSource, new RegExp(`'name'\\s*=>\\s*'${name}'[\\s\\S]*?'draft_item_id'`));
  }

  assert.match(externalCreate, /'draft_item_id'\s*=>\s*new external_value\(PARAM_INT,/);
  assert.match(externalUpdate, /'draft_item_id'\s*=>\s*new external_value\(PARAM_INT,/);
  assert.match(operationSource, /'mod_book',\s*'chapter',\s*\$chapterid/);
  assert.match(operationSource, /file_save_draft_area_files\(/);
  assert.match(bookToolsSource, /file_rewrite_pluginfile_urls\(/);
});

test('sync reads expose grouping membership, Book assets, and grading-form options', async () => {
  const [contract, groupingTools, groupingExternal, bookTools, gradingTools] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/operation/group_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/external/get_groupings.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/book_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/assignment_grading_tools.php'), 'utf8')
  ]);
  const operations = Object.fromEntries(contract.operations.map((operation) => [operation.name, operation]));

  assert.deepEqual(operations.get_groupings.returns.groupings[0].group_ids, ['integer']);
  assert.match(groupingTools, /groups_get_all_groups\(/);
  assert.match(groupingExternal, /'group_ids'\s*=>\s*new external_multiple_structure/);
  for (const operationName of ['get_book_chapters', 'create_book_chapter', 'update_book_chapter']) {
    assert.ok(JSON.stringify(operations[operationName].returns).includes('files'));
  }
  assert.match(bookTools, /'content_hash'/);
  for (const operationName of [
    'get_assignment_grading_form', 'set_assignment_rubric',
    'set_assignment_marking_guide', 'set_assignment_checklist'
  ]) {
    assert.ok(JSON.stringify(operations[operationName].returns).includes('options_json'));
  }
  assert.match(gradingTools, /'options_json'/);
});

test('sync capability discovery is contextual and returns authorization evidence', async () => {
  const [contract, externalSource, operationSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/external/get_sync_capabilities.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/get_sync_capabilities.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);
  const operation = contract.operations.find((entry) => entry.name === 'get_sync_capabilities');

  assert.equal(operation.parameters.course_id.required, false);
  assert.equal(operation.parameters.category_id.required, false);
  assert.equal(operation.returns.capabilities_json, 'string');
  assert.match(externalSource, /'category_id'\s*=>\s*new external_value\(PARAM_INT,/);
  for (const evidence of [
    'course_create', 'course_view', 'course_update', 'group_manage', 'book_edit',
    'activity_manage', 'assignment_grade', 'grading_form_manage', 'workshop_form_manage',
    'question_view', 'question_manage', 'question_bank_module_available',
    'database_field_manage', 'feedback_item_manage', 'completion_manage'
  ]) {
    assert.match(
      operationSource,
      new RegExp(`(?:'${evidence}'\\s*=>|\\$evidence\\['${evidence}'\\])`)
    );
  }
  assert.match(mcpManifestSource, /'name'\s*=>\s*'get_sync_capabilities'[\s\S]*?'category_id'/);
});

test('Workshop grading forms expose a portable definition and unrestricted rubric level arrays', async () => {
  const [contract, operationSource, toolsSource, mcpManifestSource] = await Promise.all([
    loadContract(),
    fs.readFile(fromRoot('classes/operation/get_workshop_grading_form.php'), 'utf8'),
    fs.readFile(fromRoot('classes/operation/workshop_tools.php'), 'utf8'),
    fs.readFile(fromRoot('classes/mcp/manifest.php'), 'utf8')
  ]);
  const operations = Object.fromEntries(contract.operations.map((operation) => [operation.name, operation]));

  assert.equal(operations.get_workshop_grading_form.returns.definition_json, 'string');
  assert.equal(operations.set_workshop_grading_form.parameters.definition.type, 'object');
  assert.doesNotMatch(JSON.stringify(operations.set_workshop_grading_form), /max(?:imum)?_levels/i);
  assert.match(operationSource, /export_grading_form_definition/);
  assert.match(toolsSource, /workshopform_rubric_levels/);
  assert.match(mcpManifestSource, /'name'\s*=>\s*'get_workshop_grading_form'/);
});

test('gradebook and course completion operations expose the global configuration contract', async () => {
  const contract = await loadContract();
  const operations = Object.fromEntries(contract.operations.map((operation) => [operation.name, operation]));

  assert.equal(operations.update_grade_item.parameters.weight.type, 'number');
  assert.equal(operations.update_grade_category.parameters.exclude_empty_grades.type, 'boolean');
  assert.equal(operations.get_grade_items.returns.items[0].item_type, 'string');
  assert.equal(operations.get_grade_items.returns.items[0].grade_pass, 'number');
  assert.equal(operations.get_grade_items.returns.items[0].weight, 'number');
  assert.equal(operations.set_course_grade_pass.type, 'write');
  assert.equal(operations.get_course_completion_criteria.type, 'read');
  assert.equal(operations.set_course_completion_criteria.parameters.required_module_ids.type, 'array');
});
