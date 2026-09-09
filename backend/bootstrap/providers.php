<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Business modules (see ADR-0002). Order is load order.
    Modules\Identity\Providers\IdentityServiceProvider::class,
    Modules\Companies\Providers\CompaniesServiceProvider::class,
    Modules\Customers\Providers\CustomersServiceProvider::class,
    Modules\Suppliers\Providers\SuppliersServiceProvider::class,
    Modules\Products\Providers\ProductsServiceProvider::class,
    Modules\Inventory\Providers\InventoryServiceProvider::class,
    Modules\Sales\Providers\SalesServiceProvider::class,
    Modules\Purchases\Providers\PurchasesServiceProvider::class,
    Modules\Accounting\Providers\AccountingServiceProvider::class,
    Modules\Expenses\Providers\ExpensesServiceProvider::class,
    Modules\Reports\Providers\ReportsServiceProvider::class,
    Modules\Notifications\Providers\NotificationsServiceProvider::class,
    Modules\Audit\Providers\AuditServiceProvider::class,
];
