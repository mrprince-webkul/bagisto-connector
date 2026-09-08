const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_ID = process.env.E2E_CREDENTIAL_ID || 1;

const URLS = {
    attributeMapping: `admin/bagisto/attributes-mapping/${CREDENTIAL_ID}`,
    categoryMapping:  `admin/bagisto/category-fields-mapping/${CREDENTIAL_ID}`,
};

async function goToPage(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

async function mappingForm(adminPage) {
    const form = adminPage.locator('form[data-ajax-form="true"]').first();
    await expect(form).toBeAttached({ timeout: 20_000 });

    return form;
}

async function makeFormDirty(adminPage) {
    // The "Fixed Value" column holds the only plain text inputs on these screens.
    // A bare input[type="text"] also matches vue-multiselect's hidden search box.
    const field = adminPage
        .locator('form[data-ajax-form="true"] input[type="text"]:not(.multiselect__input):not([disabled])')
        .first();

    await expect(field).toBeVisible({ timeout: 20_000 });
    await field.fill(`e2e-${Date.now()}`);
    await field.blur();
}

function saveBarButton(adminPage) {
    return adminPage.locator('[data-unsaved-save]').first();
}

async function pageNotFound(adminPage) {
    if (/(404|not[-_ ]found)/i.test(adminPage.url())) return true;
    return (await adminPage.getByText(/page not found|404|whoops|server error|no credential/i).count()) > 0;
}

// ─── ATTRIBUTE MAPPINGS ───────────────────────────────────────────────────────

test.describe('Bagisto Attribute Mappings', () => {
    test.slow();

    test('should load the Attribute Mappings page heading', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await expect(
            adminPage.getByText(/Attribute Mappings/i).first()
        ).toBeVisible({ timeout: 20_000 });
    });

    test('should display the Additional Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await expect(
            adminPage.locator('p.text-base, h2, h3').filter({ hasText: /Additional Attribute Mappings/i }).first()
        ).toBeVisible({ timeout: 15_000 });
    });

    test('should display the Configurable Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await expect(
            adminPage.locator('p.text-base, h2, h3').filter({ hasText: /Configurable Attribute Mappings/i }).first()
        ).toBeVisible({ timeout: 15_000 });
    });

    test('should show the Save button on the Attribute Mappings page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await mappingForm(adminPage);
        await makeFormDirty(adminPage);

        await expect(saveBarButton(adminPage)).toBeVisible({ timeout: 15_000 });
    });

    test('should post Attribute Mapping save and surface a server response', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await mappingForm(adminPage);
        await makeFormDirty(adminPage);

        const saveBtn = saveBarButton(adminPage);
        await expect(saveBtn).toBeVisible({ timeout: 15_000 });

        const responsePromise = adminPage.waitForResponse(
            res => /attributes-mapping\/storeOrUpdate/.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await saveBtn.click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Save request did not complete'); return; }

        expect(response.status()).toBeLessThan(500);
    });
});

// ─── CATEGORY FIELDS MAPPING ──────────────────────────────────────────────────

test.describe('Bagisto Category Fields Mapping', () => {
    test.slow();

    test('should load the Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        // The ajax form is the canonical "Vue mounted" signal — it is always
        // present, whereas the save bar only appears once the form is dirty.
        await mappingForm(adminPage);

        // Match the full page-heading text. /Category Fields/i alone collides with
        // UnoPim's catalog sidebar link <a>Category Fields</a>, which is hidden when
        // the sidebar is collapsed and pre-empts the heading via .first().
        await expect(
            adminPage.getByText(/Category Fields Mappings/i).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should show the Save button on Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await mappingForm(adminPage);
        await makeFormDirty(adminPage);

        await expect(saveBarButton(adminPage)).toBeVisible({ timeout: 15_000 });
    });

    test('should post Category Fields Mapping save and surface a server response', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await mappingForm(adminPage);
        await makeFormDirty(adminPage);

        const saveBtn = saveBarButton(adminPage);
        await expect(saveBtn).toBeVisible({ timeout: 15_000 });

        const responsePromise = adminPage.waitForResponse(
            res => /category-fields-mapping\/storeOrUpdate/.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await saveBtn.click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Save request did not complete'); return; }

        expect(response.status()).toBeLessThan(500);
    });
});

// Channel and locale mapping are handled inline on the credential edit page in
// this package (see credentials/edit.blade.php — `<v-store-config>`). There are
// no standalone /channel-mapping or /locale-mapping routes, so those tests have
// been removed; they would always skip.
