const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_URL   = 'admin/bagisto/credentials';

const PAGE_HEADING_RE = /^\s*Credentials\s*$/i;
const CREATE_BTN_RE   = /Create Credential/i;
const SAVE_BTN_RE     = /^\s*Save\s*$/i;

async function goToCredentials(adminPage) {
    await adminPage.goto(CREDENTIAL_URL);
    await adminPage.waitForLoadState('networkidle');
}

async function openCreateModal(adminPage) {
    await adminPage.getByRole('button', { name: CREATE_BTN_RE })
        .or(adminPage.getByRole('link', { name: CREATE_BTN_RE }))
        .first()
        .click();

    await expect(
        adminPage.locator('input[name="shop_url"]').first()
    ).toBeVisible({ timeout: 10_000 });
}

test.describe('Bagisto Credentials', () => {
    test.slow();

    test('should load and display the credentials listing page', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(adminPage).toHaveURL(/\/admin\/bagisto\/credentials/);

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: PAGE_HEADING_RE }).first()
        ).toBeVisible();
    });

    test('should show a "Create Credential" button', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(
            adminPage.getByRole('button', { name: CREATE_BTN_RE })
                .or(adminPage.getByRole('link', { name: CREATE_BTN_RE }))
                .first()
        ).toBeVisible();
    });

    test('should expose shop_url, email and password fields in the create modal', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        await expect(adminPage.locator('input[name="shop_url"]').first()).toBeVisible();
        await expect(adminPage.locator('input[name="email"]').first()).toBeVisible();
        await expect(adminPage.locator('input[name="password"]').first()).toBeVisible();
    });

    test('should show validation errors when saving an empty credential form', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        await expect(
            adminPage.locator('[class*="error"], .text-red-600, [class*="invalid"], .help-block').first()
        ).toBeVisible({ timeout: 8_000 });
    });
});
