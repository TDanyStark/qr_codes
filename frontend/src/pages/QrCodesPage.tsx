import { useEffect, useState } from "react";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import colorForId from "@/lib/colorForId";
import { Button } from "@/components/ui/button";
import { ChartPie, SquarePen, ExternalLink } from "lucide-react";
import { toast, Toaster } from "sonner";
import CreateQrCode from "@/components/CreateQrCode";

type Qr = {
  id: number;
  token: string;
  name?: string | null;
  target_url: string;
  owner_user_id?: number;
  owner_name?: string | null;
  owner_email?: string | null;
};

export default function QrCodesPage() {
  const [items, setItems] = useState<Qr[]>([]);
  const [urlBaseToken, setUrlBaseToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  // Using sonner to show small toasts. Install with:
  // npm install sonner
  // or
  // pnpm add sonner
  const loadItems = async () => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem("token");
      const res = await fetch("/api/qrcodes", {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();
      // API may return either an array of items or an object { items, url_base_token }
      let d = data?.data ?? data;
      let urlBaseToken: string | null = null;

      if (d && typeof d === "object" && !Array.isArray(d) && d.items) {
        urlBaseToken = d.url_base_token ?? null;
        setUrlBaseToken(urlBaseToken);
        d = d.items;
      } else {
        setUrlBaseToken(null);
      }

      // ensure we have an array
      if (!Array.isArray(d)) d = [];

      // if urlBaseToken present, store it in state (we'll use it in render)
      if (urlBaseToken) {
        console.log("url_base_token:", urlBaseToken);
      }

      // set items directly (no per-item augmentation)
      setItems(d as Qr[]);
    } catch (err) {
      setError(String(err));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadItems();
  }, []);

  return (
    <div className="container_section_main">
      <Toaster position="top-right" richColors />
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-4xl font-semibold">QR Codes</h1>
        <CreateQrCode onQrCreated={loadItems} />
      </div>

      <div className="bg-card text-card-foreground rounded shadow p-4">
        {loading ? (
          <p>Cargando...</p>
        ) : error ? (
          <p className="text-destructive">Error: {error}</p>
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Token</TableHead>
                  <TableHead>Target</TableHead>
                  <TableHead>Creator</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((q) => (
                  <TableRow key={q.id}>
                    <TableCell className="max-w-[200px]">
                      {q.name ? (
                        <div className="relative inline-block group">
                          <Tooltip>
                            <TooltipTrigger className="block truncate max-w-[200px]">
                                {q.name}
                            </TooltipTrigger>
                            <TooltipContent className="font-medium">
                              {q.name}
                            </TooltipContent>
                          </Tooltip>
                        </div>
                      ) : (
                        "-"
                      )}
                    </TableCell>
                    <TableCell className="font-mono text-sm max-w-[200px]">
                        <div className="flex items-center gap-2">
                        <button
                        title="Copiar token"
                        onClick={async () => {
                          try {
                            await navigator.clipboard.writeText(q.token);
                            toast.success("Token copiado");
                          } catch {
                            toast.error("No se pudo copiar");
                          }
                        }}
                        className="block text-left truncate w-full cursor-pointer"
                      >
                        {q.token}
                      </button>
                        {/* visit button when urlBaseToken available */}
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
                          onClick={async () => {
                            try {
                              await navigator.clipboard.writeText(q.target_url);
                              toast.success("URL copiada");
                            } catch {
                              toast.error("No se pudo copiar");
                            }
                          }}
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
                      {(() => {
                        const { bgClass, textClass } = colorForId(q.owner_user_id);
                        // Compose classes: keep the badge variant neutral and augment with bg/text
                        const cls = `${bgClass} ${textClass}`;
                        return (
                          <Badge className={cls}>
                            {q.owner_name ?? "Unknown"}
                          </Badge>
                        );
                      })()}
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => console.log("stats", q.id)}
                        >
                          <ChartPie />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => console.log("edit", q.id)}
                          className="bg-brand-pink text-brand-pink-foreground hover:opacity-90"
                        >
                          <SquarePen />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </>
        )}
      </div>
    </div>
  );
}
