import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

// Explicit opt-in is required because this suite creates and removes a temporary course.
const baseUrl = new URL(process.env.MOODLE_BASE_URL ?? 'http://127.0.0.1:8000');
if (!['127.0.0.1', 'localhost', '[::1]'].includes(baseUrl.hostname)
    && !process.argv.includes('--allow-live-site')) {
  throw new Error('Live-site qualification requires --allow-live-site.');
}
const token = process.env.MOODLE_REST_TOKEN;
if (!token) throw new Error('MOODLE_REST_TOKEN is required.');
const contract = JSON.parse(await fs.readFile(new URL('../contract/operations.json', import.meta.url), 'utf8'));
const results = [];
const modules = new Map();
const regressionOnly = process.argv.includes('--regression-only');
let courseId;

async function call(name, parameters = {}, expectedError = null) {
  assert.ok(contract.operations.some((entry) => entry.name === name), `Unknown operation ${name}`);
  const body = new URLSearchParams({ wstoken: token, wsfunction: `local_moodlia_${name}`, moodlewsrestformat: 'json' });
  for (const [key, value] of Object.entries(parameters)) {
    body.set(key, typeof value === 'object' ? JSON.stringify(value) : typeof value === 'boolean' ? String(Number(value)) : String(value));
  }
  const response = await fetch(new URL('webservice/rest/server.php', `${baseUrl.href.replace(/\/$/, '')}/`), {
    method: 'POST', body, redirect: 'error', signal: AbortSignal.timeout(120000)
  });
  const data = await response.json();
  if (expectedError) {
    assert.ok(data.exception, `${name} must reject an unavailable feature`);
    assert.match(data.message, expectedError);
    results.push({ operation: name, status: 'expected-capability-gap' });
    return data;
  }
  if (!response.ok || data.exception) {
    // Moodle errors sometimes contain request details. Never log authentication material.
    const message = String(data.message ?? response.status).replaceAll(token, '[redacted]');
    const type = parameters.module_type ?? [...modules].find(([, id]) => id === Number(parameters.module_id))?.[0] ?? '';
    throw new Error(`${name}${type ? ` (${type})` : ''}: ${data.errorcode ?? 'http'}: ${message}`);
  }
  results.push({ operation: name, status: 'passed' });
  return data;
}

async function upload(filename, content) {
  const body = new FormData();
  body.set('token', token);
  body.set('file', new Blob([content], { type: 'application/pdf' }), filename);
  const response = await fetch(new URL('webservice/upload.php', `${baseUrl.href.replace(/\/$/, '')}/`), {
    method: 'POST', body, redirect: 'error', signal: AbortSignal.timeout(120000)
  });
  const data = await response.json();
  assert.ok(response.ok && Array.isArray(data) && data[0]?.itemid, 'Draft upload must succeed');
  return Number(data[0].itemid);
}

try {
  const status = await call('get_moodlia_status');
  const user = await call('get_current_user');
  await call('get_sync_capabilities');
  const created = await call('create_course', {
    fullname: 'MoodlIA compatibility qualification', shortname: `moodlia-compatibility-${Date.now()}`,
    visible: false, enable_completion: true
  });
  courseId = Number(created.course_id ?? created.id);
  assert.ok(courseId > 0);
  if (!regressionOnly) await call('enrol_user', { course_id: courseId, user_id: user.id, role_archetype: 'student' });
  const section = await call('create_section', { course_id: courseId, name: 'Compatibility fixtures' });
  const sectionNumber = Number(section.section_number ?? section.section);
  assert.ok(sectionNumber > 0);
  const original = '%PDF-1.4\nMoodlIA original fixture\n%%EOF\n';
  const replacement = '%PDF-1.4\nMoodlIA replacement fixture\n%%EOF\n';
  const moduleOptions = {
    label: { content: '<p>Qualification text</p>' }, page: { content: '<p>Original page</p>' },
    url: { external_url: 'https://example.invalid/qualification' },
    lti: { tool_url: 'https://example.invalid/lti' },
    choice: { choices: ['First', 'Second'] },
    glossary: { display_format: 'fullwithauthor' },
    resource: { filename: 'original.pdf', upload_reference: Buffer.from(original).toString('base64'), display: 'popup', popup_width: 777, popup_height: 555, show_size: true, show_type: true, show_date: true },
    wiki: { first_page_title: 'Qualification home' }
  };
  const moduleTypes = regressionOnly ? ['page', 'resource'] : contract.operations.find((entry) => entry.name === 'create_module').parameters.module_type.enum;
  for (const type of moduleTypes) {
    const parameters = { course_id: courseId, section_number: type === 'qbank' ? 0 : sectionNumber, module_type: type, name: `Qualification ${type}`, options: moduleOptions[type] ?? {} };
    if (type === 'qbank' && /^4\.5/.test(status.moodle_release)) {
      await call('create_module', parameters, /standalone question bank/i);
      continue;
    }
    const module = await call('create_module', parameters);
    const moduleId = Number(module.course_module_id);
    assert.ok(moduleId > 0, `${type} must return its course module id`);
    modules.set(type, moduleId);
    await call('get_module_details', { course_id: courseId, module_id: moduleId });
    const updated = await call('update_module', { course_id: courseId, module_id: moduleId, name: `Renamed ${type}`, visible: false });
    assert.equal(Number(updated.course_module_id), moduleId);
    if (!['qbank', 'subsection'].includes(type)) {
      await call('update_module', { course_id: courseId, module_id: moduleId, options: { completion_tracking: 'manual', reset_completion_states: true } });
    }
  }

  const resourceId = modules.get('resource');
  if (!regressionOnly) {
    await call('save_assignment_grade', { course_id: courseId, module_id: modules.get('assign'), user_id: user.id, grade: 72.5 });
  }
  const before = await call('get_module_details', { course_id: courseId, module_id: resourceId });
  const draftId = await upload('replacement.pdf', replacement);
  const resource = await call('update_resource', {
    course_id: courseId, module_id: resourceId, filename: 'replacement.pdf', draft_item_id: draftId,
    name: 'Replaced resource', intro: '<p>Updated PDF</p>', intro_format: 'html'
  });
  assert.equal(Number(resource.course_module_id), resourceId);
  assert.equal(resource.files.length, 1);
  assert.equal(resource.files[0].filename, 'replacement.pdf');
  const after = await call('get_module_details', { course_id: courseId, module_id: resourceId });
  assert.equal(Number(after.instance_id), Number(before.instance_id));
  const beforeActivity = JSON.parse(before.extra_json).activity;
  const afterActivity = JSON.parse(after.extra_json).activity;
  for (const key of ['display', 'popup_width', 'popup_height', 'show_size', 'show_type', 'show_date']) {
    assert.equal(afterActivity[key], beforeActivity[key], `Resource replacement must preserve ${key}`);
  }
  const file = await call('download_resource_file', { course_id: courseId, module_id: resourceId, path: '/replacement.pdf' });
  const downloadUrl = new URL(file.url);
  downloadUrl.pathname = downloadUrl.pathname.replace('/pluginfile.php', '/webservice/pluginfile.php');
  downloadUrl.searchParams.set('token', token);
  const downloaded = await fetch(downloadUrl, { redirect: 'error', signal: AbortSignal.timeout(120000) });
  assert.equal(downloaded.status, 200);
  assert.equal(await downloaded.text(), replacement);

  // These calls run in separate HTTP requests; a create callback cannot preload an update dependency.
  await call('update_page', { course_id: courseId, module_id: modules.get('page'), content: '<p>Updated page</p>' });
  if (!regressionOnly) {
    await call('update_label', { course_id: courseId, module_id: modules.get('label'), content: '<p>Updated text</p>' });
    await call('update_url', { course_id: courseId, module_id: modules.get('url'), external_url: 'https://example.invalid/updated' });
    await call('update_module', { course_id: courseId, module_id: modules.get('forum'), options: { group_mode: 'separate_groups' } });
  }

  // Qualify every read that needs only a course/module/user and documented optional defaults.
  const fixtureTypes = ['assign', 'book', 'choice', 'data', 'feedback', 'folder', 'forum', 'glossary', 'lesson', 'resource', 'wiki', 'workshop', 'quiz'];
  const specialParameters = { time_from: 1, time_to: Math.floor(Date.now() / 1000), grade: 50, term: 'fixture', query: 'fixture', author_id: user.id, user_id: user.id };
  for (const operation of regressionOnly ? [] : contract.operations.filter((entry) => entry.type === 'read')) {
    if (['check_plugin_updates', 'download_folder_file', 'download_resource_file'].includes(operation.name)) continue;
    const required = Object.entries(operation.parameters).filter(([, value]) => value.required).map(([key]) => key);
    if (required.some((key) => !['course_id', 'module_id', 'quiz_module_id', 'choice_module_id', ...Object.keys(specialParameters)].includes(key))) continue;
    const fixtureType = fixtureTypes.find((type) => operation.name.includes(type === 'assign' ? 'assignment' : type));
    const parameters = {};
    for (const key of required) {
      if (key === 'course_id') parameters[key] = courseId;
      else if (key === 'module_id') parameters[key] = modules.get(fixtureType);
      else if (key === 'quiz_module_id') parameters[key] = modules.get('quiz');
      else if (key === 'choice_module_id') parameters[key] = modules.get('choice');
      else parameters[key] = specialParameters[key];
    }
    if (required.includes('module_id') && !fixtureType) continue;
    await call(operation.name, parameters);
  }

  // A rejected cross-course deletion must not damage the selected module.
  await call('delete_module', { course_id: 1, module_id: modules.get('page') }, /module|course|invalid/i);
  await call('get_module_details', { course_id: courseId, module_id: modules.get('page') });
  for (const [type, moduleId] of modules) {
    const deleted = await call('delete_module', { course_id: courseId, module_id: moduleId });
    assert.ok(deleted.deleted, `${type} must be deleted synchronously`);
    const contents = await call('get_course_contents', { course_id: courseId });
    assert.ok(!contents.sections.flatMap((section) => section.modules).some((module) => Number(module.module_id) === moduleId), `${type} must disappear from course contents`);
  }
  console.log(JSON.stringify({ moodle: status.moodle_release, plugin: status.plugin_release, calls: results.length, operations: [...new Set(results.map((entry) => entry.operation))].sort(), results }, null, 2));
} finally {
  if (courseId) await call('delete_course', { course_id: courseId });
}
