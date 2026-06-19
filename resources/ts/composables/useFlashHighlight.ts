export function useFlashHighlight(durationMs = 2000) {
  const flashKeys = ref<Set<string>>(new Set())

  const flash = (key: string) => {
    flashKeys.value = new Set([...flashKeys.value, key])
    window.setTimeout(() => {
      const next = new Set(flashKeys.value)
      next.delete(key)
      flashKeys.value = next
    }, durationMs)
  }

  const isFlashing = (key: string) => flashKeys.value.has(key)

  return { flash, isFlashing }
}
