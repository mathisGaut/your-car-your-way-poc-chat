import { useEffect, useRef, useState } from 'react'
import { api } from './api'
import { echo } from './echo'

const CONVERSATION_ID = 1
const USER_STORAGE_KEY = 'ycyw-current-user-id'

function formatTime(value) {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

export default function App() {
  const [users, setUsers] = useState([])
  const [currentUserId, setCurrentUserId] = useState(() => {
    return Number(localStorage.getItem(USER_STORAGE_KEY) || 1)
  })
  const [messages, setMessages] = useState([])
  const [content, setContent] = useState('')
  const [realtimeStatus, setRealtimeStatus] = useState('connexion...')
  const [error, setError] = useState('')
  const listRef = useRef(null)

  useEffect(() => {
    localStorage.setItem(USER_STORAGE_KEY, String(currentUserId))
  }, [currentUserId])

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const [userList, history] = await Promise.all([
          api('/api/users'),
          api(`/api/conversations/${CONVERSATION_ID}/messages`),
        ])

        if (!cancelled) {
          setUsers(userList)
          setMessages(history)
          setError('')
        }
      } catch (loadError) {
        if (!cancelled) {
          setError('Impossible de charger la conversation.')
          console.error(loadError)
        }
      }
    }

    load()

    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    const pusher = echo.connector.pusher

    const onConnected = () => setRealtimeStatus('connecté')
    const onDisconnected = () => setRealtimeStatus('déconnecté')
    const onError = () => setRealtimeStatus('erreur')

    pusher.connection.bind('connected', onConnected)
    pusher.connection.bind('disconnected', onDisconnected)
    pusher.connection.bind('unavailable', onDisconnected)
    pusher.connection.bind('error', onError)

    if (pusher.connection.state === 'connected') {
      setRealtimeStatus('connecté')
    }

    const channel = echo.channel(`conversation.${CONVERSATION_ID}`)
    channel.listen('.message.sent', (incoming) => {
      setMessages((current) => {
        if (current.some((message) => message.id === incoming.id)) {
          return current
        }

        return [...current, incoming]
      })
    })

    return () => {
      pusher.connection.unbind('connected', onConnected)
      pusher.connection.unbind('disconnected', onDisconnected)
      pusher.connection.unbind('unavailable', onDisconnected)
      pusher.connection.unbind('error', onError)
      echo.leave(`conversation.${CONVERSATION_ID}`)
    }
  }, [])

  useEffect(() => {
    if (listRef.current) {
      listRef.current.scrollTop = listRef.current.scrollHeight
    }
  }, [messages])

  async function handleSubmit(event) {
    event.preventDefault()

    const text = content.trim()
    if (!text) {
      return
    }

    setContent('')

    try {
      const created = await api(`/api/conversations/${CONVERSATION_ID}/messages`, {
        method: 'POST',
        body: {
          sender_id: currentUserId,
          content: text,
        },
      })

      setMessages((current) => {
        if (current.some((message) => message.id === created.id)) {
          return current
        }

        return [...current, created]
      })
      setError('')
    } catch (submitError) {
      setContent(text)
      setError("Le message n'a pas pu être envoyé.")
      console.error(submitError)
    }
  }

  return (
    <main className="page">
      <header className="header">
        <div>
          <h1>Conversation</h1>
          <p className="status">Temps réel : {realtimeStatus}</p>
        </div>
        <label className="user-switch">
          Vous êtes
          <select
            value={currentUserId}
            onChange={(event) => setCurrentUserId(Number(event.target.value))}
          >
            {users.map((user) => (
              <option key={user.id} value={user.id}>
                {user.name}
              </option>
            ))}
          </select>
        </label>
      </header>

      <section ref={listRef} className="messages">
        {messages.map((message) => (
          <p key={message.id} className="message">
            <strong>{message.sender?.name ?? `Utilisateur ${message.sender_id}`}</strong>
            {' : '}
            {message.content}
            <span className="time">{formatTime(message.sent_at)}</span>
          </p>
        ))}
      </section>

      {error ? <p className="error">{error}</p> : null}

      <form className="composer" onSubmit={handleSubmit}>
        <input
          type="text"
          value={content}
          onChange={(event) => setContent(event.target.value)}
          placeholder="Écrire un message..."
          maxLength={2000}
        />
        <button type="submit">Envoyer</button>
      </form>
    </main>
  )
}
