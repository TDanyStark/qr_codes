import { useEffect, useState } from "react";
import { Input } from "@/components/ui/input";
import Pagination from "@/components/ui/pagination";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";
import { useDebouncedCallback } from "use-debounce";
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
  const [page, setPage] = useState<number>(1);
  const [perPage, setPerPage] = useState<number>(10);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [query, setQuery] = useState<string>("");
  // Using sonner to show small toasts. Install with:
  // npm install sonner
  // or
  // pnpm add sonner
  const loadItems = async (opts?: { page?: number; perPage?: number; query?: string }) => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem("token");
      const p = opts?.page ?? page;
      const pp = opts?.perPage ?? perPage;
      const q = typeof opts?.query !== "undefined" ? opts!.query : query;

      const params = new URLSearchParams();
      params.set("page", String(p));
      params.set("per_page", String(pp));
      if (q) params.set("query", q);

      const res = await fetch(`/api/qrcodes?${params.toString()}`, {
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
        // pagination info
        const pagination = d && (data?.data?.pagination ?? data?.pagination ?? null);
        if (pagination) {
          setTotalPages(pagination.total_pages ?? Math.max(1, Math.ceil((pagination.total ?? 0) / (pagination.per_page ?? pp))));
          setPerPage(pagination.per_page ?? pp);
          setPage(pagination.page ?? p);
        } else if (Array.isArray(d)) {
          // fallback: if array and less than perPage, assume 1 page
          setTotalPages(Math.max(1, Math.ceil((data?.total ?? d.length) / pp)));
        }
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

  // initialize from URL
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const p = parseInt(params.get("page") || "1", 10) || 1;
    const q = params.get("query") || "";
    const pp = parseInt(params.get("per_page") || "20", 10) || 20;
    setPage(p);
    setQuery(q);
    setPerPage(pp);
    loadItems({ page: p, perPage: pp, query: q });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // update URL when page or query changes (debounced for query handled below)
  const pushUrl = (p: number, q: string, pp?: number) => {
    const params = new URLSearchParams(window.location.search);
    params.set("page", String(p));
    if (q) params.set("query", q);
    else params.delete("query");
    params.set("per_page", String(typeof pp !== "undefined" ? pp : perPage));
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, "", newUrl);
  };

  // when page changes, reload
  useEffect(() => {
    pushUrl(page, query);
    loadItems({ page, perPage, query });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  // debounced query handler
  const debounced = useDebouncedCallback((val: string) => {
    setPage(1); // reset page on new search
    pushUrl(1, val);
    loadItems({ page: 1, perPage, query: val });
  }, 450);

  return (
    <div className="container_section_main">
      <Toaster position="top-right" richColors />
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-4xl font-semibold">QR Codes</h1>
        <CreateQrCode onQrCreated={loadItems} />
      </div>

      <div className="bg-card text-card-foreground rounded shadow p-4">
        <div className="mb-4 flex flex-col sm:flex-row items-start sm:items-center gap-2">
          <Input
            placeholder="Buscar..."
            value={query}
            onChange={(e) => {
              const v = e.target.value;
              setQuery(v);
              debounced(v);
            }}
            className="max-w-md"
          />
          <div className="ml-auto flex items-center gap-2 w-full sm:w-auto">
            <label className="text-sm text-muted-foreground">Mostrar</label>
            <Select value={String(perPage)} onValueChange={(v) => {
              const n = parseInt(v, 10) || 20;
              setPerPage(n);
              setPage(1);
              // ensure URL reflects the newly selected perPage value (avoid stale closure)
              pushUrl(1, query, n);
              loadItems({ page: 1, perPage: n, query });
            }}>
              <SelectTrigger size="sm">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {[10,20,30,40,50,100].map((n) => (
                  <SelectItem key={n} value={String(n)}>{n}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
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
            <Pagination page={page} totalPages={totalPages} onPageChange={(p: number) => setPage(p)} />
          </>
        )}
      </div>
    </div>
  );
}
