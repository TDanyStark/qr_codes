import { Input } from "@/components/ui/input";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";
import { useDebouncedCallback } from "use-debounce";
import { Toaster } from "sonner";
import CreateQrCode from "@/components/CreateQrCode";
import QrCodeList from "@/components/qr/QrCodeList";
import useQRCodes from "@/components/qr/useQRCodes";

export default function QrCodesPage() {
  const {
    items,
    urlBaseToken,
    loading,
    error,
    page,
    perPage,
    totalPages,
    query,
    setPage,
    setQuery,
    loadItems,
    updatePerPage,
    updateQuery,
  } = useQRCodes({ perPage: 10 });

  const debounced = useDebouncedCallback((val: string) => {
    updateQuery(val);
  }, 450);

  return (
    <div className="container_section_main">
      <Toaster position="top-right" richColors />
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-4xl font-semibold">QR Codes</h1>
      </div>
      <div className="mb-4 flex justify-end">
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
              updatePerPage(n);
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

        <QrCodeList
          items={items}
          loading={loading}
          error={error}
          page={page}
          totalPages={totalPages}
          urlBaseToken={urlBaseToken}
          onPageChange={(p: number) => setPage(p)}
          onEdit={(id) => console.log("edit", id)}
          onStats={(id) => console.log("stats", id)}
        />
      </div>
    </div>
  );
}
