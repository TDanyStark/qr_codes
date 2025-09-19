import { Download } from "lucide-react";
import { memo } from "react";
import type { QrLinks } from "./types";

interface QrPreviewProps {
  links: QrLinks;
  previewUrl: string | null;
}

export const QrPreview = memo(function QrPreview({ links, previewUrl }: QrPreviewProps) {
  return (
    <div className="flex-1">
      <div className="flex items-center justify-between">
        <div className="text-sm text-muted-foreground px-3 py-1.5">Previsualización</div>
        {(links.png || links.svg) && (
          <div className="flex gap-2">
            {links.png && (
              <a
                href={links.png}
                download={links.png.split("/").pop()}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium bg-[var(--brand-pink)] text-[var(--brand-pink-foreground)] shadow-sm hover:shadow-md transition-colors"
              >
                <Download /> PNG
              </a>
            )}
            {links.svg && (
              <a
                href={links.svg}
                download={links.svg.split("/").pop()}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium bg-[var(--brand-pink)] text-[var(--brand-pink-foreground)] shadow-sm hover:shadow-md transition-colors"
              >
                <Download /> SVG
              </a>
            )}
          </div>
        )}
      </div>
      <div className="mt-2 p-2 aspect-square w-full h-full bg-white dark:bg-slate-800 rounded-md flex items-center justify-center">
        {previewUrl && (
          <img
            src={previewUrl}
            alt="QR preview"
            className="w-full h-auto object-contain aspect-square"
          />
        )}
      </div>
    </div>
  );
});
