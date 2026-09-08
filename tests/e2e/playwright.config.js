const { defineConfig, devices } = require('@playwright/test');
const path = require('path');

const BASE_URL = process.env.UNOPIM_URL || process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

module.exports = defineConfig({
    testDir: './tests',
    fullyParallel: false,
    forbidOnly: !! process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',

    globalSetup: require.resolve('./global-setup.js'),

    use: {
        baseURL: BASE_URL,
        storageState: STORAGE_STATE,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        ignoreHTTPSErrors: true,
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
