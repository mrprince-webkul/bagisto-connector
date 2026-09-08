
const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASE_URL = process.env.UNOPIM_URL || process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.E2E_ADMIN_EMAIL || process.env.ADMIN_USERNAME || 'admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || process.env.ADMIN_PASSWORD || 'admin123';

const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

module.exports = async () => {
    fs.mkdirSync(path.dirname(STORAGE_STATE), { recursive: true });

    const browser = await chromium.launch();
    const context = await browser.newContext({ baseURL: BASE_URL, ignoreHTTPSErrors: true });
    const page = await context.newPage();

    await page.goto('admin/login');
    await page.waitForLoadState('networkidle').catch(() => {});

    await page.locator('input[name="email"], input[type="email"]').first().fill(ADMIN_EMAIL);
    await page.locator('input[name="password"], input[type="password"]').first().fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /sign in|login|log in/i }).first().click();

    await page.waitForURL(
        (url) => ! /\/admin\/login(?:$|\?|\/)/.test(url.toString()),
        { timeout: 30_000 }
    ).catch(() => {});

    if (/\/admin\/login(?:$|\?|\/)/.test(page.url())) {
        await browser.close();
        throw new Error(
            `globalSetup: admin login failed for "${ADMIN_EMAIL}". The seeded admin password ` +
            `must match E2E_ADMIN_PASSWORD — set INSTALLER_ADMIN_PASSWORD when seeding the DB ` +
            `(UnoPim master seeds a random admin password otherwise).`
        );
    }

    await context.storageState({ path: STORAGE_STATE });
    await browser.close();
};
