# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-09-14T11:24:35.889Z
> Files: 266 tracked | Anatomy hits: 0 | Misses: 0

> Project structure index. Auto-maintained by OpenWolf hooks and daemon.
> Run `openwolf scan` to generate, or wait for the first Claude Code session.
> Status: Pending initial scan

## ./

- `.dockerignore` — Keep the PHP image build context small and secret-free. (~67 tok)
- `.editorconfig` — https://editorconfig.org (~104 tok)
- `.gitattributes` — Normalize line endings: LF in the repo, regardless of host OS. (~220 tok)
- `.gitignore` — Git ignore rules (~336 tok)
- `.gitleaks.toml` — Extends the default gitleaks rule set. (~74 tok)
- `.nvmrc` (~1 tok)
- `AGENTS.md` — OpenWolf (~75 tok)
- `CLAUDE.md` — OpenWolf (~389 tok)
- `docker-compose.yml` — Docker Compose services (~1012 tok)
- `GEMINI.md` — OpenWolf (~75 tok)
- `Makefile` — Nexora — developer command surface. (~1002 tok)
- `README.md` — Project documentation (~829 tok)

## .github/workflows/

- `ci.yml` — CI: CI (~1128 tok)

## backend/

- `.gitignore` — Git ignore rules (~84 tok)
- `composer.json` — PHP package manifest (~782 tok)
- `phpstan.neon` — Declares past (~600 tok)
- `phpunit.xml` (~566 tok)
- `pint.json` (~169 tok)
- `README.md` — Project documentation (~232 tok)

## backend/app/Console/Commands/

- `MakeModuleCommand.php` — Scaffolds a business module (see ADR-0002) and registers its service (~1229 tok)

## backend/app/Http/Controllers/

- `HealthController.php` — Liveness / readiness probe. (~431 tok)

## backend/app/Http/Middleware/

- `ForceJsonResponse.php` — Guarantees API clients are treated as JSON consumers even when they omit the (~153 tok)

## backend/app/Http/Responses/

- `ApiResponse.php` — Builds the API's canonical success envelope (see docs/API.md): (~683 tok)

## backend/app/Models/

- `User.php` — Model — 3 fields (~356 tok)

## backend/app/Providers/

- `AppServiceProvider.php` — Fail loudly in non-production on lazy loading, bad mass-assignment, and (~417 tok)

## backend/app/Support/Authorization/

- `GrantedPermissions.php` — Resolves the effective permission slugs for a role. Owner implicitly holds (~212 tok)
- `Permissions.php` — Central registry of every permission slug in the system. (~883 tok)

## backend/app/Support/Http/

- `QueryFilter.php` — Whitelisted filter / search / sort for list endpoints (docs/API.md §6–8). (~666 tok)

## backend/app/Support/Modules/

- `ModuleServiceProvider.php` — Base provider for every business module (see ADR-0002). (~494 tok)

## backend/app/Support/Tenancy/

- `BelongsToCompany.php` — Applied to every tenant-owned model (see ADR-0006). (~508 tok)
- `CompanyContext.php` — Holds the active company for the current request / job (see ADR-0006). (~442 tok)
- `TenantContextMissingException.php` — Thrown when tenant-scoped code runs without an active company bound. (~147 tok)

## backend/bootstrap/

- `app.php` (~448 tok)
- `providers.php` (~269 tok)

## backend/config/

- `app.php` (~1266 tok)
- `cors.php` (~223 tok)
- `nexora.php` (~274 tok)

## backend/database/factories/

- `UserFactory.php` — UserFactory: definition, unverified (~297 tok)

## backend/modules/Companies/Concerns/

- `HasCompanyMemberships.php` — Company membership + authorization surface for the User model. (~673 tok)

## backend/modules/Companies/Database/Factories/

- `CompanyFactory.php` — CompanyFactory: definition, suspended (~195 tok)
- `CompanyInvitationFactory.php` — CompanyInvitationFactory: definition, expired, accepted (~359 tok)

## backend/modules/Companies/Database/Migrations/

- `2026_09_10_000001_create_companies_table.php` — Migration: create companies table (~179 tok)
- `2026_09_10_000002_create_roles_table.php` — Migration: create roles table (~190 tok)
- `2026_09_10_000003_create_permissions_table.php` — Migration: create permissions table (~162 tok)
- `2026_09_10_000004_create_role_permission_table.php` — Migration: create role_permission table (~171 tok)
- `2026_09_10_000005_create_company_user_table.php` — Migration: create company_user table (~215 tok)
- `2026_09_10_000006_add_current_company_id_to_users_table.php` — Migration: alter users table (~188 tok)
- `2026_09_10_000007_create_company_invitations_table.php` — Migration: create company_invitations table (~261 tok)
- `2026_09_10_000008_seed_roles_and_permissions.php` — Roles and permissions are reference data — effectively part of the schema. (~186 tok)
- `2026_09_13_000001_reseed_roles_and_permissions.php` — Phase 2 added product/warehouse/inventory permissions and extended the role (~191 tok)

## backend/modules/Companies/Database/Seeders/

- `RolesAndPermissionsSeeder.php` — Idempotent. Runs on every `migrate --seed`; safe to re-run. (~908 tok)

## backend/modules/Companies/Http/Controllers/

- `ActiveCompanyController.php` — update (~230 tok)
- `CompanyController.php` — The caller's companies. Neither endpoint requires an active company — this is (~451 tok)
- `CurrentCompanyController.php` — Read / update the active company (bound by SetActiveCompany). The company is (~253 tok)
- `InvitationAcceptanceController.php` — Accept an invitation. Runs with no active company: trust comes from the (~244 tok)
- `InvitationController.php` — Invitations for the active company. CompanyInvitation is tenant-scoped (~489 tok)
- `MemberController.php` — Members of the active company. Every query is scoped to CompanyContext::id(); (~634 tok)
- `RoleCatalogController.php` — The system role catalogue (Phase 1 has no per-company custom roles). Used by (~157 tok)

## backend/modules/Companies/Http/Middleware/

- `EnsurePermission.php` — Route middleware: `permission:member.invite`. (~192 tok)
- `SetActiveCompany.php` — Binds the caller's active company into CompanyContext (see ADR-0006). (~445 tok)

## backend/modules/Companies/Http/Requests/

- `InviteMemberRequest.php` — InviteMemberRequest: authorize, rules (~207 tok)
- `StoreCompanyRequest.php` — StoreCompanyRequest: authorize, rules (~121 tok)
- `UpdateCompanyRequest.php` — UpdateCompanyRequest: authorize, rules (~131 tok)
- `UpdateMemberRoleRequest.php` — UpdateMemberRoleRequest: authorize, rules (~192 tok)

## backend/modules/Companies/Http/Resources/

- `CompanyResource.php` — CompanyResource: toArray (~167 tok)
- `InvitationResource.php` — InvitationResource: toArray (~238 tok)
- `MemberResource.php` — One member of the active company (a company_user row with user + role loaded). (~243 tok)
- `MembershipResource.php` — A company as seen from one user's membership — the company fields plus the (~266 tok)
- `RoleResource.php` — RoleResource: toArray (~138 tok)

## backend/modules/Companies/Models/

- `Company.php` — A tenant. Users belong to many companies through `company_user`. (~518 tok)
- `CompanyInvitation.php` — A pending invitation for an email address to join a company with a role. (~576 tok)
- `CompanyUser.php` — Membership row: one user's role in one company. (~299 tok)
- `Permission.php` — Model — 3 fields, 1 rels (~153 tok)
- `Role.php` — A system-defined role (Phase 1 has no per-company custom roles). (~357 tok)

## backend/modules/Companies/Notifications/

- `CompanyInvitationNotification.php` — CompanyInvitationNotification: via, toMail (~327 tok)

## backend/modules/Companies/Providers/

- `CompaniesServiceProvider.php` — CompaniesServiceProvider: register, boot (~472 tok)

## backend/modules/Companies/Routes/

- `api.php` (~914 tok)

## backend/modules/Companies/Services/

- `CompanyInvitationService.php` — Invitations to join the active company. Creation and revocation run inside a (~1330 tok)
- `CompanyMembershipService.php` — Membership lifecycle within a single company: switching the active company, (~914 tok)
- `CompanyProvisioner.php` — Creates a company and its founding Owner membership as one atomic unit. (~443 tok)

## backend/modules/Companies/Tests/Feature/

- `ActiveCompanyTest.php` (~364 tok)
- `CompanyManagementTest.php` (~944 tok)
- `InvitationTest.php` — Persist an invitation directly (bypassing the tenant scope, as the service (~1537 tok)
- `MemberManagementTest.php` (~888 tok)
- `TenantIsolationTest.php` — The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006). A user who (~1133 tok)

## backend/modules/Companies/Tests/Unit/

- `BelongsToCompanyScopeTest.php` — Exercises the BelongsToCompany mechanism (ADR-0006) through CompanyInvitation, (~783 tok)

## backend/modules/Identity/Actions/

- `RegisterUser.php` — Registration: create the user, their first company, and the Owner membership (~348 tok)

## backend/modules/Identity/Http/Controllers/

- `AuthenticatedSessionController.php` — store, destroy (~264 tok)
- `EmailVerificationNotificationController.php` — store (~170 tok)
- `NewPasswordController.php` — store (~336 tok)
- `PasswordResetLinkController.php` — store (~175 tok)
- `RegisteredUserController.php` — store (~231 tok)
- `SessionUserController.php` — __invoke (~130 tok)
- `VerifyEmailController.php` — Handles the signed link from the verification email. The link is opened (~281 tok)

## backend/modules/Identity/Http/Requests/

- `ForgotPasswordRequest.php` — ForgotPasswordRequest: authorize, rules (~116 tok)
- `LoginRequest.php` — LoginRequest: authorize, rules, authenticate, throttleKey (~534 tok)
- `RegisterRequest.php` — RegisterRequest: authorize, rules (~191 tok)
- `ResetPasswordRequest.php` — ResetPasswordRequest: authorize, rules (~162 tok)

## backend/modules/Identity/Http/Resources/

- `UserResource.php` — UserResource: toArray (~151 tok)

## backend/modules/Identity/Providers/

- `IdentityServiceProvider.php` — The verification email is opened in a browser; the password-reset link is (~404 tok)

## backend/modules/Identity/Routes/

- `api.php` (~585 tok)

## backend/modules/Identity/Support/

- `SessionPayload.php` — Assembles the "who am I" payload returned by register / login / GET me: (~367 tok)

## backend/modules/Identity/Tests/Feature/

- `EmailVerificationTest.php` (~684 tok)
- `LoginTest.php` (~385 tok)
- `LogoutTest.php` (~218 tok)
- `PasswordResetTest.php` (~600 tok)
- `RegistrationTest.php` (~830 tok)
- `SessionTest.php` (~470 tok)

## backend/modules/Inventory/Console/Commands/

- `ReconcileInventoryCommand.php` — Detects and repairs drift between the `stock` projection and the (~2131 tok)

## backend/modules/Inventory/Database/Factories/

- `InventoryMovementFactory.php` — InventoryMovementFactory: definition, type (~317 tok)
- `StockFactory.php` — StockFactory: definition (~202 tok)
- `WarehouseFactory.php` — WarehouseFactory: definition, default, inactive (~307 tok)

## backend/modules/Inventory/Database/Migrations/

- `2026_09_13_000001_create_warehouses_table.php` — Migration: create warehouses table (~238 tok)
- `2026_09_14_000001_create_inventory_movements_table.php` — Migration: create inventory_movements table (~637 tok)
- `2026_09_14_000002_create_stock_table.php` — Migration: create stock table (~462 tok)

## backend/modules/Inventory/Http/Controllers/

- `AdjustmentController.php` — `POST /inventory/adjustments` — the only public writer that can set (~582 tok)
- `InventoryController.php` — Read-only for now: the stock projection and the ledger behind it (~538 tok)
- `WarehouseController.php` — Warehouses for the active company. Not paginated — a small reference list, (~490 tok)

## backend/modules/Inventory/Http/Requests/

- `StoreAdjustmentRequest.php` — `POST /inventory/adjustments` (PHASE-2-PLAN.md §5): one warehouse, one (~618 tok)
- `StoreWarehouseRequest.php` — StoreWarehouseRequest: authorize, rules (~256 tok)
- `UpdateWarehouseRequest.php` — UpdateWarehouseRequest: authorize, rules (~299 tok)

## backend/modules/Inventory/Http/Resources/

- `InventoryMovementResource.php` — InventoryMovementResource: toArray (~392 tok)
- `StockResource.php` — StockResource: toArray (~311 tok)
- `WarehouseResource.php` — WarehouseResource: toArray (~184 tok)

## backend/modules/Inventory/Models/

- `InventoryMovement.php` — One line of the append-only inventory ledger — the source of truth for (~1108 tok)
- `Stock.php` — A maintained projection of SUM(inventory_movements.quantity) for one (~490 tok)
- `Warehouse.php` — A stock location within a company. Exactly one warehouse is the company's (~503 tok)

## backend/modules/Inventory/Providers/

- `InventoryServiceProvider.php` — InventoryServiceProvider: boot (~150 tok)

## backend/modules/Inventory/Routes/

- `api.php` (~585 tok)

## backend/modules/Inventory/Services/

- `InventoryLedger.php` — The single writer of `inventory_movements` + `stock` for the active (~1479 tok)
- `WarehouseService.php` — Warehouse lifecycle for the active company. The single place that enforces (~1747 tok)

## backend/modules/Inventory/Tests/Feature/

- `AdjustmentTest.php` — Declares adjustmentFixture (~1975 tok)
- `InventoryLedgerTest.php` — Declares ledgerFixture (~2643 tok)
- `InventoryReadTest.php` (~1435 tok)
- `InventoryTenantIsolationTest.php` — The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for (~754 tok)
- `ReconcileInventoryCommandTest.php` — reconcileFixture: corruptStock (~1407 tok)
- `StockConcurrencyTest.php` — Real MySQL concurrency, deliberately NOT using RefreshDatabase: that trait (~1809 tok)
- `WarehouseManagementTest.php` (~2368 tok)
- `WarehouseTenantIsolationTest.php` — The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for (~581 tok)

## backend/modules/Products/Database/Factories/

- `ProductCategoryFactory.php` — ProductCategoryFactory: definition, inactive (~212 tok)
- `ProductFactory.php` — ProductFactory: definition, inactive, withMinimumStock (~359 tok)

## backend/modules/Products/Database/Migrations/

- `2026_09_13_000001_create_product_categories_table.php` — Migration: create product_categories table (~231 tok)
- `2026_09_13_000002_create_products_table.php` — Migration: create products table (~449 tok)

## backend/modules/Products/Http/Controllers/

- `ProductCategoryController.php` — Categories for the active company. A small, flat-ish list — not paginated. (~446 tok)
- `ProductController.php` — index, store, show, update, destroy (~479 tok)

## backend/modules/Products/Http/Requests/

- `StoreProductCategoryRequest.php` — StoreProductCategoryRequest: authorize, rules (~279 tok)
- `StoreProductRequest.php` — StoreProductRequest: authorize, rules (~459 tok)
- `UpdateProductCategoryRequest.php` — UpdateProductCategoryRequest: authorize, rules, withValidator (~730 tok)
- `UpdateProductRequest.php` — UpdateProductRequest: authorize, rules (~506 tok)

## backend/modules/Products/Http/Resources/

- `ProductCategoryResource.php` — ProductCategoryResource: toArray (~176 tok)
- `ProductResource.php` — ProductResource: toArray (~280 tok)

## backend/modules/Products/Models/

- `Product.php` — Model — 11 fields, 1 rels (~691 tok)
- `ProductCategory.php` — A product category, optionally nested under a parent (no enforced depth — (~542 tok)

## backend/modules/Products/Routes/

- `api.php` (~664 tok)

## backend/modules/Products/Tests/Feature/

- `ProductCategoryTest.php` (~1462 tok)
- `ProductManagementTest.php` (~1779 tok)
- `ProductTenantIsolationTest.php` — The mandatory cross-tenant isolation suite (CLAUDE.md / ADR-0006) for the (~756 tok)

## backend/modules/Products/Tests/Unit/

- `ModelRelationsTest.php` (~248 tok)

## backend/routes/

- `api.php` (~152 tok)
- `web.php` (~168 tok)

## backend/tests/

- `Pest.php` — Create a company owned by $owner (a fresh verified user if omitted) together (~1069 tok)
- `TestCase.php` — Declares TestCase (~45 tok)

## backend/tests/Feature/

- `HealthEndpointTest.php` — Declares Pest (~197 tok)
- `MakeModuleCommandTest.php` — Declares SCRATCH_MODULE (~655 tok)
- `ModuleSystemTest.php` — Declares EXPECTED_MODULES (~575 tok)

## backend/tests/Unit/

- `ApiResponseTest.php` (~310 tok)

## docker/mysql/init/

- `01-create-test-database.sql` — Runs once on first MySQL container start (empty data dir). (~116 tok)

## docker/nginx/

- `default.conf` (~276 tok)

## docker/php/

- `Dockerfile` — Docker container definition (~512 tok)
- `entrypoint.sh` (~168 tok)
- `php.ini` (~117 tok)
- `xdebug.ini` (~78 tok)

## docs/

- `API.md` — Nexora — API Specification (~2862 tok)
- `ARCHITECTURE.md` — Nexora — Architecture (~1110 tok)
- `DATABASE.md` — Nexora — Database Design (~2241 tok)
- `DEVELOPMENT.md` — Nexora — Development Guide (~963 tok)
- `PHASE-0.md` — Phase 0 — Foundation (completed 2026-09-09) (~898 tok)
- `PHASE-1.md` — Phase 1 — Identity & Multi-Tenancy (~1388 tok)
- `PHASE-2-PLAN.md` — Phase 2 — Products & Inventory — PLAN (~2879 tok)
- `ROADMAP.md` — Nexora — Development Roadmap (~1073 tok)
- `SECURITY.md` — Nexora — Security Requirements (~1615 tok)
- `TESTING.md` — Nexora — Testing Strategy (~680 tok)

## docs/adr/

- `0001-record-architecture-decisions.md` — ADR-0001: Record architecture decisions (~255 tok)
- `0002-modular-monolith-layout.md` — ADR-0002: Modular monolith layout with hand-rolled PSR-4 modules (~628 tok)
- `0003-docker-dev-environment.md` — ADR-0003: Full Docker development environment (~416 tok)
- `0004-authentication-transport.md` — ADR-0004: Authentication transport — Sanctum SPA cookie session (~504 tok)
- `0005-test-database-mysql.md` — ADR-0005: Run the test suite against MySQL, not SQLite (~586 tok)
- `0006-tenancy-mechanism.md` — ADR-0006: Multi-tenancy mechanism (~674 tok)
- `0007-stock-projection.md` — ADR-0007: Stock as a maintained projection, and how it stays consistent under concurrency (~1201 tok)

## frontend/

- `.gitignore` — Git ignore rules (~77 tok)
- `.prettierignore` (~16 tok)
- `.prettierrc.json` (~31 tok)
- `components.json` (~122 tok)
- `eslint.config.js` (~370 tok)
- `index.html` — Nexora (~96 tok)
- `package.json` — Node.js package manifest (~440 tok)
- `README.md` — Project documentation (~415 tok)
- `tsconfig.app.json` (~266 tok)
- `tsconfig.node.json` (~171 tok)
- `vite.config.ts` — /*.{ts,tsx}'], (~256 tok)

## frontend/src/

- `App.test.tsx` — session (~609 tok)
- `App.tsx` — App (~660 tok)
- `index.css` — Styles: 1 rules (~51 tok)
- `main.tsx` — queryClient (~201 tok)
- `vite-env.d.ts` — / <reference types="vite/client" /> (~45 tok)

## frontend/src/components/

- `AppHeader.tsx` — AppHeader (~543 tok)
- `ui.tsx` — Field (~642 tok)

## frontend/src/features/auth/

- `api.test.ts` — Declares session (~654 tok)
- `api.ts` — GET the current session, or `null` when the caller is not authenticated. (~390 tok)
- `AuthShell.tsx` — AuthShell (~232 tok)
- `ForgotPasswordPage.tsx` — ForgotPasswordPage — renders form (~410 tok)
- `LoginPage.test.tsx` — guest (~579 tok)
- `LoginPage.tsx` — LoginPage — renders form (~694 tok)
- `PasswordPages.test.tsx` — user (~740 tok)
- `RegisterPage.test.tsx` — guest (~606 tok)
- `RegisterPage.tsx` — RegisterPage — renders form (~899 tok)
- `RequireAuth.tsx` — RequireAuth (~172 tok)
- `ResetPasswordPage.tsx` — ResetPasswordPage — renders form (~720 tok)
- `session.ts` — Exports sessionKey, useSession, usePermissions, useLogin + 5 more (~473 tok)
- `types.ts` — Exports AuthUser, Role, CompanySummary, Session + 3 more (~228 tok)
- `VerifyEmailBanner.test.tsx` — session (~396 tok)
- `VerifyEmailBanner.tsx` — VerifyEmailBanner (~181 tok)

## frontend/src/features/companies/

- `AcceptInvitationPage.test.tsx` — session (~546 tok)
- `AcceptInvitationPage.tsx` — AcceptInvitationPage (~517 tok)
- `api.ts` — Exports Member, Invitation, RoleOption, listRoles + 10 more (~544 tok)
- `CompanySwitcher.test.tsx` — role (~591 tok)
- `CompanySwitcher.tsx` — CompanySwitcher (~318 tok)
- `CreateCompanyCard.test.tsx` — emptySession (~613 tok)
- `CreateCompanyCard.tsx` — CreateCompanyCard — renders form (~358 tok)
- `hooks.ts` — Exports useRoles, useMembers, useInvitations, useSwitchCompany + 5 more (~699 tok)
- `MembersPanel.test.tsx` — role (~1042 tok)
- `MembersPanel.tsx` — MembersPanel — renders form (~1644 tok)

## frontend/src/features/health/

- `HealthCard.test.tsx` — fetchMock (~345 tok)
- `HealthCard.tsx` — Indicator (~459 tok)
- `useHealth.ts` — Exports HealthStatus, useHealth (~104 tok)

## frontend/src/features/inventory/

- `api.ts` — Exports listStock, listMovements, createAdjustment (~281 tok)
- `hooks.ts` — Exports useStock, useMovements, useCreateAdjustment (~278 tok)
- `InventoryPage.test.tsx` — role (~2675 tok)
- `InventoryPage.tsx` — MOVEMENT_TYPES — renders form (~3256 tok)
- `types.ts` — Exports MovementType, ProductSummary, WarehouseSummary, Stock + 6 more (~428 tok)

## frontend/src/features/products/

- `api.test.ts` — Declares product (~728 tok)
- `api.ts` — Exports listProducts, getProduct, createProduct, updateProduct + 5 more (~440 tok)
- `CategoriesPage.test.tsx` — role (~1283 tok)
- `CategoriesPage.tsx` — CategoriesPage — renders form (~1312 tok)
- `hooks.ts` — Exports useProducts, useCategories, useCreateProduct, useUpdateProduct + 4 more (~648 tok)
- `ProductsPage.test.tsx` — role (~2107 tok)
- `ProductsPage.tsx` — ProductsPage — renders form (~3525 tok)
- `types.ts` — Exports ProductStatus, ProductCategory, Product, ProductInput + 2 more (~360 tok)

## frontend/src/features/warehouses/

- `api.ts` — Exports listWarehouses, createWarehouse, updateWarehouse, deleteWarehouse (~160 tok)
- `hooks.ts` — Exports useWarehouses, useCreateWarehouse, useUpdateWarehouse, useDeleteWarehouse (~335 tok)
- `types.ts` — Exports WarehouseStatus, Warehouse, WarehouseInput (~109 tok)
- `WarehousesPage.test.tsx` — role (~2318 tok)
- `WarehousesPage.tsx` — WarehousesPage — renders form (~2002 tok)

## frontend/src/lib/

- `api.test.ts` — API routes: GET, POST, DELETE, PATCH (8 endpoints) (~705 tok)
- `api.ts` — Minimal typed API client for the Nexora backend. (~1060 tok)
- `forms.test.ts` — Declares error (~319 tok)
- `forms.ts` — Flatten an ApiError's `errors` map to the first message per field. (~211 tok)
- `utils.test.ts` — Declares names (~85 tok)
- `utils.ts` — Merge conditional class names, de-duplicating Tailwind utilities. (~71 tok)

## frontend/src/pages/

- `DashboardPage.test.tsx` — role (~838 tok)
- `DashboardPage.tsx` — DashboardPage (~289 tok)

## frontend/src/store/

- `ui.test.ts` — Declares raw (~206 tok)
- `ui.ts` — Guarded storage: `localStorage` can be unavailable (private mode, disabled (~353 tok)

## frontend/src/test/

- `fetchStub.ts` — substring match against the request URL (~552 tok)
- `setup.ts` (~46 tok)
- `utils.tsx` — Build a minimal JSON `Response` for stubbing `fetch` in tests. (~474 tok)
