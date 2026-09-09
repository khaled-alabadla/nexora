import { useQuery } from '@tanstack/react-query'

import { api } from '@/lib/api'

export interface HealthStatus {
  status: 'ok' | 'degraded'
  database: boolean
  cache: boolean
}

export function useHealth() {
  return useQuery({
    queryKey: ['health'],
    queryFn: () => api.get<HealthStatus>('/health'),
    refetchInterval: 30_000,
    retry: 1,
  })
}
