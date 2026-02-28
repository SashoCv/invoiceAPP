# Fynvo Desktop App + Магацинско Водење + Shopify Интеграција

## Контекст
Fynvo е Laravel + Inertia.js + React веб апликација за фактурирање. Три цели:
1. Standalone десктоп апликација (без админ панел, без надворешен сервер)
2. Магацинско водење (залиха, движења) за артиклите
3. Shopify интеграција - автоматско одземање на залиха при продажба

**Пристап: NativePHP + Electron** за десктоп. Shopify Admin API (REST) за синхронизација со polling (бидејќи десктоп апп не може да прима webhooks).

---

## ФАЗА 1: Десктоп Апликација (NativePHP)

### 1.1 Инсталирај NativePHP

```bash
composer require nativephp/electron
php artisan native:install
```

Конфигурирај `app/Providers/NativeAppServiceProvider.php` (ново, од `native:install`):
- Прозорец: 1400x900, наслов "Fynvo", без DevTools
- На прво подигање: креирај default корисник и логирај го
- На boot: повикај `rates:fetch` и `expenses:generate-recurring`

Конфигурирај `config/nativephp.php` (ново, од `native:install`):
- `app_id`: `com.fynvo.desktop`, `version`: `1.0.0`

### 1.2 Database и Environment

Промени за `.env` (десктоп):
- `DB_CONNECTION=sqlite` (веќе default во `config/database.php:19`)
- `SESSION_DRIVER=file` (сега `database` во `config/session.php:21`)
- `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `DESKTOP_MODE=true`

Додај во `config/app.php`:
```php
'desktop_mode' => env('DESKTOP_MODE', false),
```

### 1.3 Отстрани Admin

**Бришење:**
- `resources/js/pages/Admin/` (цел директориум)
- `resources/js/components/AdminLayout.tsx`
- `app/Http/Controllers/Admin/` (цел директориум)
- `app/Http/Middleware/EnsureUserIsAdmin.php`
- `app/Models/AdminNotification.php`
- `app/Mail/AdminNotificationMail.php`
- `app/Console/Commands/CreateAdminUser.php`
- `resources/views/emails/admin-notification.blade.php`

**Промени:**
- `routes/web.php` - избриши admin routes (линии 143-157) и use imports (линии 3-5)
- `bootstrap/app.php:24-25` - избриши `'admin'` middleware alias
- `resources/js/components/AppLayout.tsx` - избриши admin линк и subscription банер

### 1.4 Bypass Subscription

**`app/Models/User.php:147`** - `hasActiveSubscription()` и `subscriptionStatus()`:
- Врати `true` / `'active'` кога `config('app.desktop_mode')` е true

**`app/Http/Middleware/HandleInertiaRequests.php`** - додај:
```php
'isDesktop' => config('app.desktop_mode', false),
```

**`resources/js/components/AppLayout.tsx`** - скриј Billing линк и subscription банер кога `isDesktop`.

### 1.5 Автентикација

Во `NativeAppServiceProvider::boot()`: auto-create + auto-login корисник.
Промени root route (`routes/web.php:24-26`) да редиректира на dashboard во десктоп режим.

### 1.6 PDF Генерација

**`app/Services/PdfService.php:161`** - во десктоп режим, конфигурирај Browsershot да користи Electron Chromium. Fallback: dompdf.

**`resources/views/pdf/browsershot.blade.php`** - замени CDN (Google Fonts, Tailwind) со локални ресурси.

### 1.7 Email Поставки (ново)

- `resources/js/pages/Settings/Email.tsx` - SMTP форма
- `app/Http/Controllers/Settings/EmailSettingsController.php`
- Миграција: `email_settings` табела (smtp_host, smtp_port, smtp_username, smtp_password, smtp_from)
- Рута во settings група во `routes/web.php`

---

## ФАЗА 2: Магацинско Водење (Inventory)

### 2.1 Нови миграции

**`create_stock_movements_table`:**
```
- id
- article_id (FK → articles)
- user_id (FK → users)
- type: enum('purchase', 'sale', 'adjustment', 'return', 'shopify_sale')
- quantity: decimal (позитивно за влез, негативно за излез)
- reference_type: nullable string (Invoice, Offer, Shopify Order)
- reference_id: nullable integer
- notes: nullable text
- timestamps
```

**`add_stock_fields_to_articles`:**
```
- stock_quantity: decimal(10,2) default 0 (вкупна залиха)
- low_stock_threshold: integer nullable (праг за предупредување)
- track_stock: boolean default false (дали се следи залиха за овој артикл)
- sku: string nullable (Stock Keeping Unit - за Shopify sync)
```

### 2.2 Нов Model

**`app/Models/StockMovement.php`:**
- BelongsTo Article, BelongsTo User
- Boot event: автоматски update на `article.stock_quantity` при create

### 2.3 Промени во Article Model

**`app/Models/Article.php`:**
- Додај `hasMany(StockMovement::class)` релација
- Додај нови полиња во `$fillable`
- Метод `adjustStock($quantity, $type, $reference = null)` - креира StockMovement и update-ира stock_quantity
- Scope `lowStock()` - филтер за артикли под прагот

### 2.4 Промени во InvoiceController

**`app/Http/Controllers/InvoiceController.php`** - `store()` и `updateStatus()`:
- Кога фактура се означи како "paid", одземи залиха за секој item што има `article_id` и `track_stock = true`
- Креирај StockMovement за секој одземен артикл

Исто за `ProformaInvoiceController` и `OfferController` при конверзија.

### 2.5 Нов контролер и рути

**`app/Http/Controllers/StockController.php`:**
- `index()` - преглед на залиха за сите артикли (stock dashboard)
- `adjust()` - рачна корекција на залиха (+ или -)
- `movements()` - историја на движења за артикл

**Рути во `routes/web.php`:**
```php
Route::get('stock', [StockController::class, 'index'])->name('stock.index');
Route::post('stock/{article}/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
Route::get('stock/{article}/movements', [StockController::class, 'movements'])->name('stock.movements');
```

### 2.6 Нови React страници

**`resources/js/pages/Stock/Index.tsx`:**
- Табела со сите артикли и нивната залиха
- Колони: Име, SKU, Залиха, Праг, Статус (ОК/Ниско/Нема)
- Филтер: сите / ниска залиха / нема залиха
- Копче за рачна корекција (dialog)

**`resources/js/pages/Stock/Movements.tsx`:**
- Историја на движења за конкретен артикл
- Табела: Датум, Тип, Количина, Референца, Белешки

### 2.7 Промени во Articles форми

**`resources/js/pages/Articles/Create.tsx` и `Edit.tsx`:**
- Додај поле `track_stock` (switch)
- Додај поле `sku` (text input, опционално)
- Додај поле `low_stock_threshold` (number, ако track_stock е вклучено)
- Додај поле `stock_quantity` (само на Edit, read-only приказ + копче за adjust)

### 2.8 Навигација

**`resources/js/components/AppLayout.tsx`:**
- Додај "Магацин" / "Stock" линк во sidebar (меѓу Articles и Expenses)

---

## ФАЗА 3: Shopify Интеграција

### 3.1 Нови миграции

**`create_shopify_settings_table`:**
```
- id
- user_id (FK → users)
- shop_domain: string (mystore.myshopify.com)
- access_token: string (encrypted)
- is_active: boolean default false
- last_synced_at: timestamp nullable
- sync_interval_minutes: integer default 15
- timestamps
```

**`add_shopify_fields_to_articles`:**
```
- shopify_product_id: string nullable
- shopify_variant_id: string nullable
```

### 3.2 Shopify Service

**`app/Services/ShopifyService.php`:**
- Конфигурација: shop domain + access token (Shopify Admin API)
- `getOrders($sinceId)` - повлечи нови нарачки од Shopify
- `getProducts()` - повлечи производи за sync
- `syncStock($article)` - испрати ажурирана залиха назад кон Shopify
- Користи Guzzle HTTP (веќе инсталиран) за API повици

### 3.3 Sync Command

**`app/Console/Commands/SyncShopifyOrders.php`:**
- Повикај `ShopifyService::getOrders()` за нови нарачки (од `last_synced_at`)
- За секоја нарачка: најди го артиклот по `shopify_product_id` или `sku`
- Одземи залиха преку `article->adjustStock(-qty, 'shopify_sale', order_id)`
- Ажурирај `last_synced_at`

### 3.4 Auto-sync на десктоп

Во `NativeAppServiceProvider::boot()`:
- На подигање, и на секои X минути (polling), повикај `SyncShopifyOrders`
- Користи NativePHP `MenuBar` или timer за периодичен sync
- Ако е offline, прескокни и пробај подоцна

### 3.5 Settings UI

**`resources/js/pages/Settings/Shopify.tsx`:**
- Поле за Shop Domain
- Поле за Access Token (password input)
- Sync interval (dropdown: 5/15/30/60 минути)
- Копче "Синхронизирај сега"
- Последна синхронизација: датум/време
- Статус: Connected/Disconnected

**`app/Http/Controllers/Settings/ShopifyController.php`:**
- `edit()` - прикажи Shopify settings
- `update()` - зачувај settings (encrypt token)
- `sync()` - мануелен trigger за sync
- `testConnection()` - тестирај дали API credentials работат

**Рути во settings група:**
```php
Route::get('/shopify', [ShopifyController::class, 'edit'])->name('shopify');
Route::put('/shopify', [ShopifyController::class, 'update'])->name('shopify.update');
Route::post('/shopify/sync', [ShopifyController::class, 'sync'])->name('shopify.sync');
Route::post('/shopify/test', [ShopifyController::class, 'testConnection'])->name('shopify.test');
```

### 3.6 Поврзување артикли со Shopify производи

**`resources/js/pages/Articles/Edit.tsx`:**
- Додај секција "Shopify" (ако Shopify е конфигуриран)
- Dropdown за поврзување со Shopify производ (fetch од API)
- Или рачно внеси `shopify_product_id`

### 3.7 Навигација

**`resources/js/components/AppLayout.tsx`:**
- Додај "Shopify" линк во Settings навигацијата (до Bank Accounts, Templates)

---

## Редослед на имплементација

1. **Фаза 1** (Десктоп) - прво, бидејќи е основата
2. **Фаза 2** (Магацин) - второ, бидејќи Shopify зависи од stock системот
3. **Фаза 3** (Shopify) - трето, се надоврзува на магацинот

---

## Верификација

### Десктоп
1. `php artisan native:serve` - апликацијата се отвора во Electron прозорец
2. SQLite база работи, CRUD за сите ентитети
3. PDF генерација работи offline
4. Нема admin линкови/рути, нема subscription банер
5. Email settings → конфигурирај SMTP → испрати фактура по email

### Магацин
6. Креирај артикл со `track_stock = true`, додај залиха
7. Креирај фактура со тој артикл, означи ја како "paid" → залиха се намалува
8. Stock dashboard покажува точна залиха и историја на движења
9. Low stock предупредување кога залихата е под прагот

### Shopify
10. Конфигурирај Shopify credentials во Settings
11. "Test Connection" покажува успешна врска
12. Поврзи артикл со Shopify производ
13. Направи нарачка на Shopify → sync → залиха се намалува во Fynvo
14. `php artisan native:build mac` генерира .dmg фајл
