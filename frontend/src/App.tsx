import { Navigate, Route, Routes } from 'react-router-dom'

import { ForgotPasswordPage } from '@/features/auth/ForgotPasswordPage'
import { LoginPage } from '@/features/auth/LoginPage'
import { RegisterPage } from '@/features/auth/RegisterPage'
import { RequireAuth } from '@/features/auth/RequireAuth'
import { ResetPasswordPage } from '@/features/auth/ResetPasswordPage'
import { AcceptInvitationPage } from '@/features/companies/AcceptInvitationPage'
import { InventoryPage } from '@/features/inventory/InventoryPage'
import { CategoriesPage } from '@/features/products/CategoriesPage'
import { ProductsPage } from '@/features/products/ProductsPage'
import { WarehousesPage } from '@/features/warehouses/WarehousesPage'
import { DashboardPage } from '@/pages/DashboardPage'

export default function App() {
  return (
    <div className="min-h-dvh bg-neutral-50 dark:bg-neutral-950">
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />
        <Route
          path="/invitations/:token/accept"
          element={
            <RequireAuth>
              <AcceptInvitationPage />
            </RequireAuth>
          }
        />
        <Route
          path="/"
          element={
            <RequireAuth>
              <DashboardPage />
            </RequireAuth>
          }
        />
        <Route
          path="/products"
          element={
            <RequireAuth>
              <ProductsPage />
            </RequireAuth>
          }
        />
        <Route
          path="/categories"
          element={
            <RequireAuth>
              <CategoriesPage />
            </RequireAuth>
          }
        />
        <Route
          path="/warehouses"
          element={
            <RequireAuth>
              <WarehousesPage />
            </RequireAuth>
          }
        />
        <Route
          path="/inventory"
          element={
            <RequireAuth>
              <InventoryPage />
            </RequireAuth>
          }
        />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </div>
  )
}
