import { Copy } from "lucide-react";
import { memo } from "react";

interface QrResultLinkProps {
  redirect?: string | null;
  copied: boolean;
  onCopy: () => void | Promise<void>;
}

export const QrResultLink = memo(function QrResultLink({ redirect, copied, onCopy }: QrResultLinkProps) {
  if (!redirect) return null;
  return (
    <div className="mt-3 flex items-center gap-3">
      <input
        readOnly
        className="flex-1 bg-slate-100 dark:bg-slate-900 rounded-md px-3 py-2 text-sm overflow-ellipsis overflow-hidden"
        value={redirect}
        onFocus={(e) => e.currentTarget.select()}
      />
      <button
        type="button"
        aria-label="Copiar enlace"
        className="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-[var(--brand-pink)] text-[var(--brand-pink-foreground)] shadow-sm hover:shadow-md transition-colors"
        onClick={() => { void onCopy(); }}
      >
        <Copy />
        {copied ? "Copiado" : "Copiar"}
      </button>
    </div>
  );
});
