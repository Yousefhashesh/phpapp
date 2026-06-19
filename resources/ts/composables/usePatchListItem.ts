export function patchListItem<T extends { id: number }>(
  listRef: Ref<T[] | undefined | null>,
  id: number,
  patch: Partial<T>,
) {
  if (!listRef.value)
    return

  const index = listRef.value.findIndex(item => item.id === id)
  if (index === -1)
    return

  listRef.value[index] = { ...listRef.value[index], ...patch }
}
