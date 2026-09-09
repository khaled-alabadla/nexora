# Nexora — Product Requirements Document

## 1. Product Overview

Nexora is a multi-tenant Business Management and ERP SaaS platform.

Tagline:

Run your business. One platform.

Nexora allows multiple companies to manage their business operations
from a single platform.

Each company has isolated data, users, roles, permissions,
products, inventory, sales, purchases, accounting and reports.

---

## 2. Goals

Nexora should:

- Provide centralized business management.
- Support multiple companies.
- Provide strict tenant isolation.
- Manage products and inventory.
- Manage customers and suppliers.
- Manage sales and purchases.
- Provide invoices and payments.
- Provide basic double-entry accounting.
- Provide business reports.
- Provide audit logs.
- Provide REST APIs.
- Support background jobs and scheduled tasks.

---

## 3. Target Users

### Company Owner

Can manage the company and all business operations.

### Administrator

Manages users, roles and company settings.

### Accountant

Manages invoices, payments and accounting.

### Sales Manager

Manages sales operations and sales reports.

### Sales Representative

Creates quotations, orders and sales invoices.

### Inventory Manager

Manages products, warehouses and inventory.

### Purchasing Manager

Manages suppliers and purchasing.

### Employee

Has limited access based on assigned permissions.

---

## 4. Core Modules

- Identity
- Companies
- Customers
- Suppliers
- Products
- Inventory
- Sales
- Purchases
- Accounting
- Expenses
- Reports
- Notifications
- Audit Logs

---

## 5. Sales Flow

Quotation
    ↓
Sales Order
    ↓
Invoice
    ↓
Payment

Not every step must always be required.

---

## 6. Purchase Flow

Purchase Order
    ↓
Goods Received
    ↓
Purchase Invoice
    ↓
Payment

---

## 7. Inventory

Inventory must be ledger-based.

Supported movement types:

- purchase
- sale
- return
- adjustment
- transfer_in
- transfer_out
- damage

Inventory changes must be transactional.

---

## 8. Accounting

Nexora uses simplified double-entry accounting.

Every posted journal entry must satisfy:

Total Debits = Total Credits

Example:

Sale:

Debit  Accounts Receivable
Credit Sales Revenue

Payment:

Debit  Cash
Credit Accounts Receivable

---

## 9. Non-Functional Requirements

- Secure
- Multi-tenant
- Scalable
- Testable
- Maintainable
- API-first
- Responsive UI
- Auditable
- Production-ready