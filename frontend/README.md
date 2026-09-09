# Nexora — Frontend

React 19 + TypeScript + Vite SPA for the Nexora ERP. Talks to the backend REST
API at `/api/v1` and authenticates with Sanctum cookie sessions (see
[`../docs/adr/0004-authentication-transport.md`](../docs/adr/0004-authentication-transport.md)).

## Stack

| Concern       | Choice                                 |
| ------------- | -------------------------------------- |
| Build         | Vite                                   |
| Language      | TypeScript (strict)                    |
| UI            | TailwindCSS v4, shadcn/ui (new-york)   |
| Server state  | TanStack Query                         |
| Client state  | Zustand (UI preferences only)          |
| Icons         | lucide-react                           |
| Tests         | Vitest + Testing Library (jsdom)       |
| Lint / format | ESLint (flat, type-checked) + Prettier |

## Scripts

```bash
npm run dev            # Vite dev server on :5173
npm run build          # production build
npm run preview        # serve the build
npm run lint           # ESLint
npm run typecheck      # tsc --noEmit (project refs)
npm run test           # Vitest (run once)
npm run test:coverage  # Vitest with v8 coverage
npm run format         # Prettier write
```

Under Docker: `make check-frontend` runs lint + typecheck + test + build.

## Layout

```
src/
├── lib/
│   ├── api.ts        # typed fetch client (credentials, CSRF, ApiError)
│   └── utils.ts      # cn() class merger
├── features/
│   └── health/       # backend health widget (useHealth + HealthCard)
├── store/
│   └── ui.ts         # Zustand UI store (theme)
├── components/ui/    # shadcn components (added as needed)
├── test/             # Vitest setup + render helpers
├── App.tsx
└── main.tsx          # QueryClientProvider bootstrap
```

## Environment

`VITE_API_URL` — backend base URL (default `http://localhost:8000`). Copy
`.env.example` to `.env`.
