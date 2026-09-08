const { test: base, expect } = require('@playwright/test');

const ADMIN_LOGIN_PATH = 'admin/login';

const ADMIN_EMAIL    = process.env.E2E_ADMIN_EMAIL    || process.env.ADMIN_USERNAME || 'admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || process.env.ADMIN_PASSWORD || 'admin123';

async function loginAsAdmin(page) {
    await page.goto(ADMIN_LOGIN_PATH);
    await page.waitForLoadState('networkidle');

    // Already authenticated — login redirects away from /admin/login.
    if (! /\/admin\/login/.test(page.url())) {
        return;
    }

    await page.locator('input[name="email"], input[type="email"]').first().fill(ADMIN_EMAIL);
    await page.locator('input[name="password"], input[type="password"]').first().fill(ADMIN_PASSWORD);

    // Click first, then wait — Promise.all() can register the wait after the
    // navigation kicks off and resolve immediately on slow CI.
    await page.getByRole('button', { name: /sign in|login|log in/i }).first().click();

    await page.waitForURL(
        (url) => ! /\/admin\/login(?:$|\?|\/)/.test(url.toString()),
        { timeout: 20_000 }
    ).catch(() => {});

    await page.waitForLoadState('networkidle').catch(() => {});

    if (/\/admin\/login(?:$|\?|\/)/.test(page.url())) {
        throw new Error(
            `Admin login failed for "${ADMIN_EMAIL}". The seeded admin password must ` +
            `match E2E_ADMIN_PASSWORD — set INSTALLER_ADMIN_PASSWORD when seeding the DB.`
        );
    }
}

const test = base.extend({
    adminPage: async ({ page }, use) => {
        await loginAsAdmin(page);
        await use(page);
    },
});

module.exports = { test, expect };
