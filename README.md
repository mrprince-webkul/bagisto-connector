# UnoPim-Bagisto Connector

The **UnoPim-Bagisto Connector** enables seamless integration between **UnoPim 2.0** and **Bagisto**, allowing you to synchronize data effortlessly.

## ✨ Features

- 🗂 **Export Categories**: Export categories from UnoPim as collections in Bagisto.
- 🛠️ **Attributes Sync**: Transfer attributes from UnoPim to Bagisto with ease.
- 👨‍👩‍👧 **Export Families**: Export families from UnoPim to Bagisto.
- 📦 **Product Export**: Export products, including simple and configurable ones, from UnoPim to Bagisto.
- ⚡ **Bulk API**: Leverage a bulk API for faster product exports.
- 🗄️ **Media Sync**: Sync product images and videos from UnoPim to Bagisto.
- ☁️ **AWS S3 Compatibility**: Fully compatible with AWS S3 for image storage and management.

## ✅ Requirements

- **UnoPim**: `2.1.x`
- **PHP**: `8.3+`
- **Bagisto** with REST API installed (`2.x.x`)

---

## 🛠️ Installation with Composer (recommended)

UnoPim 2.0 ships with Laravel 12-style auto-discovery, so the service provider is registered automatically through `composer.json` (`extra.laravel.providers`). You only need to require the package and run the installer.

1. **Require the package**

    ```bash
    composer require unopim/bagisto-connector
    ```

2. **Run the package installer**

    The package ships an artisan command that runs the migrations, publishes assets, and clears caches in one step:

    ```bash
    php artisan bagisto-package:install
    ```

    > Pass `--no-interaction` (e.g. in CI) to accept the default for the migration prompt.

3. **(Optional) Verify the provider is registered**

    Auto-discovery should add the provider for you. If you want to confirm, check that `bootstrap/providers.php` resolves the package via `composer dump-autoload` — there is **no** entry to add by hand in UnoPim 2.0.

---

## ⚙️ Installation without Composer

Use this path only if you need to load the package from the local `packages/` directory (for example when forking or developing the connector).

1. **Place the package**

    Download and extract the connector. Rename the folder to `Bagisto` and move it into `packages/Webkul/` of your UnoPim 2.0 project, so the final path is:

    ```
    packages/Webkul/Bagisto
    ```

2. **Register the namespace**

    Open the project's root `composer.json` and add the package namespace under `autoload.psr-4`:

    ```json
    "autoload": {
        "psr-4": {
            "Webkul\\Bagisto\\": "packages/Webkul/Bagisto/src"
        }
    }
    ```

3. **Register the service provider**

    > UnoPim 2.0 follows the Laravel 12 bootstrap layout — providers live in `bootstrap/providers.php`, **not** in `config/app.php`.

    Open `bootstrap/providers.php` and add the provider to the returned array:

    ```php
    <?php

    return [
        // ...other providers,
        Webkul\Bagisto\Providers\BagistoServiceProvider::class,
    ];
    ```

4. **(Alternative) Register as a path repository**

    If you keep the package in `packages/Webkul/Bagisto` and still want to install it via Composer (so auto-discovery handles the provider), register a path repository in the project's `composer.json` and require it:

    ```bash
    composer config repositories.bagisto '{"type":"path","url":"packages/Webkul/Bagisto","options":{"symlink":true}}' --json
    composer require unopim/bagisto-connector:"*@dev"
    ```

5. **Refresh autoload and run the installer**

    ```bash
    composer dump-autoload
    php artisan bagisto-package:install
    php artisan optimize:clear
    ```

    The installer covers `migrate`, `vendor:publish --tag=unopim-bagisto-connector`, and `optimize:clear` for you. Run them individually only if you want fine-grained control:

    ```bash
    php artisan migrate
    php artisan vendor:publish --tag=unopim-bagisto-connector
    php artisan optimize:clear
    ```

---

## 🔁 Enable Queue Operations

The connector dispatches export jobs onto Laravel queues. Make sure a worker is running:

```bash
php artisan queue:work --queue=default,system,completeness
```

If `queue:work` is supervised by a process manager (Supervisor, systemd, etc.), restart it after upgrading the connector so the new code is picked up:

```bash
sudo service supervisor restart
```

---

## 🌐 Bagisto Requirement: REST API

The connector talks to Bagisto via its REST API. Install the API on the Bagisto side before configuring credentials in UnoPim.

1. **Reference repository** — [unopim/bagisto-rest-api](https://github.com/unopim/bagisto-rest-api)
2. **Installation docs** — [Bagisto REST API docs](https://devdocs.bagisto.com/api/rest-api.html)
3. **Compatibility** — install the `2.x.x` line that matches your Bagisto version.

---

## 📞 Support

For any issues or inquiries, please contact our support team.
