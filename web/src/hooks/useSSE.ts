import { useEffect, useRef, useState, useCallback } from 'react'

const API_BASE = import.meta.env.VITE_API_URL as string ?? '/api'

export function useSSE<T>(path: string | null, options?: { enabled?: boolean }) {
  const [data, setData]   = useState<T | null>(null)
  const [error, setError] = useState<string | null>(null)
  const esRef = useRef<EventSource | null>(null)

  const connect = useCallback(() => {
    if (!path || options?.enabled === false) return

    const token = localStorage.getItem('sigfa_token')
    const url   = `${API_BASE}${path}${path.includes('?') ? '&' : '?'}token=${token}`

    const es = new EventSource(url)
    esRef.current = es

    es.onmessage = (e) => {
      try {
        setData(JSON.parse(e.data))
        setError(null)
      } catch {
        setError('Parse error')
      }
    }

    es.addEventListener('error', () => {
      setError('Connection lost — reconnecting…')
      es.close()
      // Reconnect after 3 s
      setTimeout(connect, 3000)
    })

    return () => es.close()
  }, [path, options?.enabled])

  useEffect(() => {
    const cleanup = connect()
    return () => {
      cleanup?.()
      esRef.current?.close()
    }
  }, [connect])

  return { data, error }
}
