import { useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
// ...existing code... (select removed because frontend now only supports PNG)
import axios from "axios";
import { toast } from "sonner";
import { Copy, Download } from "lucide-react";

interface CreateQrCodeProps {
  onQrCreated?: () => void;
}

export default function CreateQrCode({ onQrCreated }: CreateQrCodeProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  // error state is shown via Sonner toasts instead of inline UI
  const [formData, setFormData] = useState({
    target_url: "",
    name: "",
    foreground: "#000000",
    background: "#ffffff",
  });
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [redirectLink, setRedirectLink] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);
  const [created, setCreated] = useState(false);
  const targetInputRef = useRef<HTMLInputElement | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    // clear any previous Sonner toasts is unnecessary; keep behavior

    // If a QR was already created, block further submissions until user clicks "Crear Otro"
    if (created) return;

    if (!formData.target_url) {
      toast.error("La URL destino es requerida");
      return;
    }

    setLoading(true);
    try {
      const token = localStorage.getItem("token");
      if (!token) {
        throw new Error("No auth token found");
      }

      const payload = {
        target_url: formData.target_url,
        name: formData.name || undefined,
        foreground: formData.foreground,
        background: formData.background,
        format: "png",
      };

      const res = await axios.post("/api/qrcodes", payload, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      // If success, show preview image returned by backend and keep dialog open
      // Support both res.data.links.png and res.data.data.links.png shapes
      console.debug("CreateQrCode response", res?.data);
      const pngUrl =
        res?.data?.links?.png ?? res?.data?.data?.links?.png ?? null;
      if (pngUrl) {
        setPreviewUrl(pngUrl);
      }
      // capture redirect link if backend returns it
      const redirect =
        res?.data?.links?.redirect ?? res?.data?.data?.links?.redirect ?? null;
      if (redirect) setRedirectLink(redirect);
      // mark as created so we don't allow another create until user clicks "Crear Otro"
      setCreated(true);
      // notify parent that a QR was created
      onQrCreated?.();
    } catch (err: unknown) {
      let message = "Error al crear QR";
      if (err && typeof err === "object" && "response" in err) {
        const axiosError = err as {
          response?: { data?: { message?: string } };
        };
        message = axiosError.response?.data?.message || message;
      } else if (err && typeof err === "object" && "message" in err) {
        const errorWithMessage = err as { message: string };
        message = errorWithMessage.message;
      }
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleOpenChange = (newOpen: boolean) => {
    setOpen(newOpen);
    if (!newOpen) {
      // Reset form and preview when closing
      setFormData({
        target_url: "",
        name: "",
        foreground: "#000000",
        background: "#ffffff",
      });
      setPreviewUrl(null);
      setRedirectLink(null);
      setCopied(false);
      setCreated(false);
      // errors are shown via Sonner toast; nothing to reset here
    }
    if (newOpen) {
      setTimeout(() => {
        const el =
          targetInputRef.current || document.getElementById("target_url");
        if (el && typeof (el as HTMLInputElement).focus === "function") {
          (el as HTMLInputElement).focus();
        }
      }, 50);
    }
  };

  useEffect(() => {
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.ctrlKey || e.altKey || e.metaKey) return;
      const active = document.activeElement;
      if (active) {
        const tag = active.tagName.toLowerCase();
        const isEditable =
          tag === "input" ||
          tag === "textarea" ||
          (active as HTMLElement).isContentEditable;
        if (isEditable) return;
      }
      if (e.key === "n" || e.key === "N") {
        e.preventDefault();
        setOpen(true);
      }
    };

    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, []);

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button>Crear QR</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[900px] max-h-[650px] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle>Crear Código QR</DialogTitle>
          <DialogDescription>
            Genera un código QR y obtén el enlace de descarga (PNG).
          </DialogDescription>
        </DialogHeader>

        <div className="overflow-auto flex-1 min-h-0">
          <form
            id="create-qr-form"
            onSubmit={handleSubmit}
            className="p-1 flex flex-col md:flex-row gap-6"
          >
            <div className="flex-1 flex flex-col gap-4">
              <div className="space-y-4 mb-2 ">
                <div className="space-y-2">
                  <Label htmlFor="target_url">URL destino</Label>
                  <Input
                    id="target_url"
                    ref={targetInputRef}
                    value={formData.target_url}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        target_url: e.target.value,
                      }))
                    }
                    placeholder="https://example.com"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="name">Nombre (opcional)</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) =>
                      setFormData((prev) => ({ ...prev, name: e.target.value }))
                    }
                    placeholder="Ej: QR de venta"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="foreground">Color primer plano</Label>
                    <Input
                      id="foreground"
                      type="color"
                      value={formData.foreground}
                      onChange={(e) =>
                        setFormData((prev) => ({
                          ...prev,
                          foreground: e.target.value,
                        }))
                      }
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="background">Color fondo</Label>
                    <Input
                      id="background"
                      type="color"
                      value={formData.background}
                      onChange={(e) =>
                        setFormData((prev) => ({
                          ...prev,
                          background: e.target.value,
                        }))
                      }
                    />
                  </div>
                </div>
              </div>

              <div>
                <hr />
                <p className="mt-2">result</p>
                {redirectLink && (
                  <div className="mt-3 flex items-center gap-3">
                    <input
                      readOnly
                      className="flex-1 bg-slate-100 dark:bg-slate-900 rounded-md px-3 py-2 text-sm overflow-ellipsis overflow-hidden"
                      value={redirectLink}
                      onFocus={(e) => e.currentTarget.select()}
                    />
                    <button
                      type="button"
                      aria-label="Copiar enlace"
                      className="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-[var(--brand-pink)] text-[var(--brand-pink-foreground)] shadow-sm hover:shadow-md transition-colors"
                      onClick={async () => {
                        try {
                          await navigator.clipboard.writeText(redirectLink);
                          setCopied(true);
                          setTimeout(() => setCopied(false), 1600);
                        } catch {
                          // fall back to manual selection copy
                          const el = document.createElement("textarea");
                          el.value = redirectLink;
                          document.body.appendChild(el);
                          el.select();
                          document.execCommand("copy");
                          document.body.removeChild(el);
                          setCopied(true);
                          setTimeout(() => setCopied(false), 1600);
                        }
                      }}
                    >
                      <Copy />
                      {copied ? "Copiado" : "Copiar"}
                    </button>
                  </div>
                )}
              </div>
            </div>

            <div className="flex items-center gap-4 w-[320px] max-w-full">
              <div className="flex-1">
                <div className="flex items-center justify-between">
                  <div className="text-sm text-muted-foreground px-3 py-1.5">
                    Previsualización
                  </div>
                  {previewUrl && (
                    <a
                      href={previewUrl}
                      download={previewUrl.split("/").pop()}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium bg-[var(--brand-pink)] text-[var(--brand-pink-foreground)] shadow-sm hover:shadow-md transition-colors"
                    >
                      <Download />
                    </a>
                  )}
                </div>
                <div className="mt-2 p-2 aspect-square w-full h-full bg-white dark:bg-slate-800 rounded-md flex items-center justify-center">
                  {/* Show generated PNG preview when available, otherwise show the target url as placeholder */}
                  {previewUrl && (
                    <img
                      src={previewUrl}
                      alt="QR preview"
                      className="w-full h-auto object-contain aspect-square"
                    />
                  )}
                </div>
                {/* Redirect link with copy button */}
              </div>
            </div>
          </form>
        </div>
        <DialogFooter className="flex-shrink-0 bg-transparent">
          <Button
            type="button"
            variant="outline"
            onClick={() => setOpen(false)}
            disabled={loading}
          >
            Cancelar
          </Button>

          {!created ? (
            <Button type="submit" disabled={loading} form="create-qr-form">
              {loading ? "Generando..." : "Generar QR"}
            </Button>
          ) : (
            <Button
              type="button"
              onClick={() => {
                // reset form and allow creating another QR
                setFormData({
                  target_url: "",
                  name: "",
                  foreground: "#000000",
                  background: "#ffffff",
                });
                setPreviewUrl(null);
                setRedirectLink(null);
                setCopied(false);
                setCreated(false);
                // focus the target input
                setTimeout(() => {
                  const el =
                    targetInputRef.current ||
                    document.getElementById("target_url");
                  if (
                    el &&
                    typeof (el as HTMLInputElement).focus === "function"
                  ) {
                    (el as HTMLInputElement).focus();
                  }
                }, 50);
              }}
            >
              Crear Otro
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
