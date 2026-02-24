// React import not required with new JSX transform
import { TableCell, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { ExternalLink, SquarePen, ChartPie } from "lucide-react";
import { Button } from "@/components/ui/button";
import colorForId from "@/lib/colorForId";
import { toast } from "sonner";

export type Qr = {
  id: number;
  token: string;
  name?: string | null;
  target_url: string;
  updated_at: string;
  owner_user_id?: number;
  owner_name?: string | null;
  owner_email?: string | null;
};

type Props = {
  q: Qr;
  urlBaseToken?: string | null;
  onEdit?: (id: number) => void;
  onStats?: (id: number) => void;
};

export default function QrCodeRow({ q, urlBaseToken, onEdit, onStats }: Props) {
  const copy = async (
    text: string,
    success = "Copiado",
    fail = "No se pudo copiar",
  ) => {
    try {
      await navigator.clipboard.writeText(text);
      toast.success(success);
    } catch {
      toast.error(fail);
    }
  };

  const { bgClass, textClass } = colorForId(q.owner_user_id);
  const cls = `${bgClass} ${textClass}`;

  return (
    <TableRow key={q.id}>
      <TableCell className="max-w-[200px]">
        {q.name ? (
          <div className="relative inline-block group">
            <div className="block truncate max-w-[200px]" title={q.name}>
              {q.name}
            </div>
          </div>
        ) : (
          "-"
        )}
      </TableCell>
      <TableCell className="font-mono text-sm max-w-[200px]">
        <div className="flex items-center gap-2">
          <button
            title="Copiar token"
            onClick={() => copy(q.token, "Token copiado")}
            className="block text-left truncate w-full cursor-pointer"
          >
            {q.token}
          </button>

          {urlBaseToken ? (
            <a
              href={`${urlBaseToken}${q.token}`}
              target="_blank"
              rel="noreferrer"
              title="Visitar QR"
              onClick={(e) => e.stopPropagation()}
              className="inline-flex items-center p-1 rounded hover:bg-muted"
            >
              <ExternalLink className="w-4 h-4" />
            </a>
          ) : null}
        </div>
      </TableCell>
      <TableCell className="max-w-[200px]">
        <div className="flex items-center gap-2">
          <button
            title="Copiar URL"
            onClick={() => copy(q.target_url, "URL copiada")}
            className="flex-1 text-left truncate min-w-0 cursor-pointer hover:underline"
          >
            {q.target_url}
          </button>
          <a
            href={q.target_url}
            target="_blank"
            rel="noreferrer"
            aria-label="Visitar URL"
            className="inline-flex items-center p-1 rounded hover:bg-muted"
            onClick={(e) => e.stopPropagation()}
          >
            <ExternalLink className="w-4 h-4" />
          </a>
        </div>
      </TableCell>
      <TableCell>
        <Badge className={cls}>{q.owner_name ?? "Unknown"}</Badge>
      </TableCell>
      <TableCell>
        {new Date(q.updated_at.replace(" ", "T") + "Z").toLocaleString(
          "en-US",
          {
            timeZone: "America/Bogota",
          },
        )}
      </TableCell>
      <TableCell>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => onStats?.(q.id)}>
            <ChartPie />
          </Button>
          <Button
            size="sm"
            onClick={() => onEdit?.(q.id)}
            className="bg-brand-pink text-brand-pink-foreground hover:bg-brand-pink"
          >
            <SquarePen />
          </Button>
        </div>
      </TableCell>
    </TableRow>
  );
}
