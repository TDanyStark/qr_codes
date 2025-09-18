declare module 'sonner' {
  export const toast: {
    success: (msg: string) => void
    error: (msg: string) => void
    [key: string]: unknown
  }
  export const Toaster: React.FC<Record<string, unknown>>
  export default {} as Record<string, unknown>
}
