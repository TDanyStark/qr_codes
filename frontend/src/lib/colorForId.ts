// Deterministic color picker for user ids.
// Maps any numeric id to a pair of CSS variables / Tailwind classes.
// Strategy: use a small palette of CSS custom properties defined in global styles
// (prefer using neutral/dark-friendly colors) and select one via a simple hash.

export function colorForId(id?: number | null) {
  if (id == null) return {
    bgClass: 'bg-secondary',
    textClass: 'text-secondary-foreground'
  };

  // A small palette that maps to sensible semantic tokens. These are Tailwind utility classes
  // that rely on the project's theme variables (dark-aware in global.css).
  const palette: { bgClass: string; textClass: string }[] = [
    { bgClass: 'bg-chart-1', textClass: 'text-white' },
    { bgClass: 'bg-chart-2', textClass: 'text-white' },
    { bgClass: 'bg-chart-3', textClass: 'text-black' },
    { bgClass: 'bg-chart-4', textClass: 'text-white' },
    { bgClass: 'bg-chart-5', textClass: 'text-white' },
  ];

  // Simple deterministic hash: use modular arithmetic on the id
  const index = Math.abs(Number(id)) % palette.length;
  return palette[index];
}

export default colorForId;
