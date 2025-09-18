import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export type Theme = 'light' | 'dark' | 'system'

export function getStoredTheme(): Theme | null {
  try {
    const t = localStorage.getItem('theme') as Theme | null
    return t
  } catch {
    return null
  }
}

export function setTheme(theme: Theme) {
  try {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else if (theme === 'light') {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    } else {
      // system
      localStorage.removeItem('theme')
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    }
  } catch {
    // ignore
  }
}
