import { reactive } from 'vue'

const TOAST_LIMIT = 20
const TOAST_REMOVE_DELAY = 3000

let count = 0
const toastTimeouts = new Map()

function genId() {
  count = (count + 1) % Number.MAX_VALUE
  return count.toString()
}

export const state = reactive<{ toasts: any[] }>({
  toasts: [],
})

function addToRemoveQueue(id: string) {
  if (toastTimeouts.has(id)) return
  const timeout = setTimeout(() => {
    toastTimeouts.delete(id)
    remove(id)
  }, TOAST_REMOVE_DELAY)
  toastTimeouts.set(id, timeout)
}

function remove(id: string) {
  state.toasts = state.toasts.filter((t) => t.id !== id)
}

export function dismiss(id: string) {
  addToRemoveQueue(id)
  state.toasts = state.toasts.map((t) =>
    t.id === id ? { ...t, open: false } : t
  )
}

export function toast(props: {
  title?: string;
  description?: string;
  variant?: 'default' | 'destructive';
}) {
  const id = genId()
  const t = { ...props, id, open: true, variant: props.variant ?? 'default' }
  state.toasts = [t, ...state.toasts].slice(0, TOAST_LIMIT)
  addToRemoveQueue(id)
  return { id, dismiss: () => dismiss(id) }
}