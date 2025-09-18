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
import { Button } from "@/components/ui/button";
import { ChartPie, SquarePen, ExternalLink } from "lucide-react";
import { toast, Toaster } from "sonner";

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
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  // Using sonner to show small toasts. Install with:
  // npm install sonner
  // or
  // pnpm add sonner
  useEffect(() => {
    const token = localStorage.getItem("token");
    fetch("/api/qrcodes", {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
      .then(async (res) => {
        if (!res.ok) throw new Error(await res.text());
        return res.json();
      })
      .then((data) => {
        // action payload shape: { statusCode, data }
        const d = data?.data ?? data;
        setItems(d);
      })
      .catch((err) => setError(String(err)))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="container_section_main">
      <Toaster position="top-right" richColors />
      <h1 className="text-4xl font-semibold mb-4">QR Codes</h1>

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
                            <TooltipContent>
                              {q.name}
                            </TooltipContent>
                          </Tooltip>
                        </div>
                      ) : (
                        "-"
                      )}
                    </TableCell>
                    <TableCell className="font-mono text-sm max-w-[200px]">
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
                      <Badge variant="secondary">
                        {q.owner_name ?? "Unknown"}
                      </Badge>
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
