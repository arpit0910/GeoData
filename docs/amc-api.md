# AMC list API

`GET /api/v1/market/amcs`

Returns all saved AMCs alphabetically by name. No authentication or query parameters are required.

The JSON response contains `success: true` and a `data` array. Each AMC includes its `id`, `amfi_id`, `name`, `company_name`, `website`, `favicon_url`, `details`, and synchronization timestamps. `favicon_url` is an absolute URL, or `null` when no favicon is saved. An empty directory returns `data: []`.

Populate or refresh the directory after running migrations:

```sh
php artisan amcs:sync-icons
```
