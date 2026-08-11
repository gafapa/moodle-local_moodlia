import { getEnv, getTimeout } from './env.mjs';
import { createMoodleRestClient } from '../../client/moodle-rest-client.mjs';

export async function callRestFunction(functionName, parameters = {}) {
  const client = createMoodleRestClient({
    baseUrl: getEnv('MOODLE_BASE_URL'),
    token: getEnv('MOODLE_REST_TOKEN'),
    timeoutMs: getTimeout()
  });

  return client.callFunction(functionName, parameters);
}
