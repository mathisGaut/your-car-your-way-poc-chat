const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

export async function api(path, { method = 'GET', body } = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
  })

  if (!response.ok) {
    throw new Error(`Erreur API ${response.status}`)
  }

  return response.json()
}
