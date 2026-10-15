/* eslint-disable no-console */
// This file is part of Moodle - http://moodle.org/
// Copyright (C) 2026 Pablo Gallego. Licensed under the GNU GPL v3 or later.

define([], function() {
    'use strict';

    let registration;
    let lastConfig;

    const request = async(endpoint, action, payload = {}, signal) => {
        const body = new URLSearchParams({ action, sesskey: M.cfg.sesskey, ...payload });
        const response = await fetch(endpoint, {
            method: 'POST', credentials: 'same-origin', redirect: 'error', body, signal,
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            const error = new Error(result.error || 'MoodlIA WebMCP request failed.');
            error.name = 'MoodlIARequestError';
            throw error;
        }
        return result.result;
    };

    const init = async config => {
        lastConfig = config;
        const modelContext = document.modelContext || navigator.modelContext;
        if (!window.isSecureContext || !modelContext?.registerTool || registration) {
            return;
        }
        registration = new AbortController();
        try {
            const endpoint = new URL(config.endpoint, window.location.href);
            if (endpoint.origin !== window.location.origin) {
                throw new Error('MoodlIA WebMCP requires a same-origin endpoint.');
            }
            const tools = await request(endpoint.href, 'list', {}, registration.signal);
            const currentContext = config.context || {};
            const registrations = tools.map(async tool => {
                const description = `${tool.description} Current page context (not a scope restriction): ${JSON.stringify(currentContext)}.`;
                await modelContext.registerTool({
                    ...tool,
                    description,
                    execute: async(args = {}, execution = {}) => {
                        let result;
                        try {
                            result = await request(endpoint.href, 'execute', {
                                tool: tool.name, arguments: JSON.stringify(args),
                            }, execution.signal);
                        } catch (error) {
                            // Preserve Moodle's error details across native WebMCP invocation boundaries.
                            if (error.name === 'MoodlIARequestError') {
                                return { isError: true, content: [{ type: 'text', text: error.message }] };
                            }
                            throw error;
                        }
                        if (tool.name === 'moodlia_open_page') {
                            const target = new URL(result.url, window.location.href);
                            const loopback = ['localhost', '127.0.0.1', '[::1]'].includes(target.hostname);
                            if (target.origin !== window.location.origin
                                    || (target.protocol !== 'https:' && !(target.protocol === 'http:' && loopback))) {
                                throw new Error('Moodle returned an invalid navigation destination.');
                            }
                            // Let the browser receive the tool result before unloading this document.
                            window.setTimeout(() => window.location.assign(target.href), 0);
                        }
                        return result;
                    },
                }, { signal: registration.signal });
            });
            await Promise.all(registrations);
        } catch (error) {
            registration.abort();
            registration = null;
            // Optional browser support must never break the Moodle page.
            console.warn('MoodlIA WebMCP could not be initialized.', error.message);
        }
    };

    window.addEventListener('pagehide', event => {
        if (!event.persisted) {
            registration?.abort();
        }
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            registration?.abort();
            registration = null;
            void init(lastConfig);
        }
    });

    return { init };
});
