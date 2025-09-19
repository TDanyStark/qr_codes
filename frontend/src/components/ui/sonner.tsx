import { useTheme } from "next-themes"
import { Toaster as Sonner } from "sonner"

// Derive the props type from the Sonner Toaster component to avoid depending on a non-exported type
type LocalToasterProps = React.ComponentProps<typeof Sonner>

const Toaster = (props: LocalToasterProps) => {
  const { theme = "system" } = useTheme()

  return (
    <Sonner
      theme={theme as LocalToasterProps["theme"]}
      className="toaster group"
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
        } as React.CSSProperties
      }
      {...props}
    />
  )
}

export { Toaster }
