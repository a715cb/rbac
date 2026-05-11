export function getCssVar(variableName: string, defaultValue = ''): string {
  const value = getComputedStyle(document.documentElement).getPropertyValue(variableName)
  return value ? value.trim() : defaultValue
}
