const { test, expect } = require('../utils/fixtures');

const EXPORTS_URL = 'admin/exports';

async function goTo(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

test.describe('Bagisto Export – Listing', () => {
    test.slow();

    test('should load the Exports listing page', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        // The package contributes Bagisto exporter types to UnoPim's export config;
        // the canonical proof is that /admin/exports renders for the admin without
        // redirecting to /admin/login. UnoPim's page chrome varies between releases,
        // so don't try to assert on a specific layout class.
        await expect(adminPage).toHaveURL(/\/admin\/exports/);
    });
});
