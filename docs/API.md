# Nexora — API Specification

## 1. Base URL

/api/v1

---

## 2. Authentication

Authentication uses Laravel Sanctum.

Authenticated requests require:

Authorization: Bearer <token>

---

## 3. Response Format

Successful response:

{
    "data": {},
    "message": "Success"
}

Collection response:

{
    "data": [],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "total": 200
    }
}

---

## 4. Errors

Errors should use a consistent structure.

Example:

{
    "message": "Validation failed",
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}

---

## 5. Pagination

Collection endpoints support:

?page=1
&per_page=20

---

## 6. Filtering

Example:

GET /api/v1/products?status=active

---

## 7. Searching

Example:

GET /api/v1/products?search=laptop

---

## 8. Sorting

Example:

GET /api/v1/products?sort=-created_at

---

## 9. Product Endpoints

GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}

Authorization is required for every endpoint.

---

## 10. Security

The API must never trust client-provided:

- company_id
- user_id
- created_by
- invoice totals
- calculated prices
- permissions

The backend derives these values from authenticated context
and business rules.

---

## 11. Rate Limiting

Sensitive endpoints must have appropriate rate limits.

Examples:

- login
- password reset
- API authentication
- expensive reports