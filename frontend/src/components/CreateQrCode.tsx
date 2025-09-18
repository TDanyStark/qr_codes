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

interface CreateQrCodeProps {
  onQrCreated?: () => void;
}

export default function CreateQrCode({ onQrCreated }: CreateQrCodeProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    target_url: "",
    name: "",
    foreground: "#000000",
    background: "#ffffff",
  });
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const targetInputRef = useRef<HTMLInputElement | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!formData.target_url) {
      setError("La URL destino es requerida");
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
      setError(message);
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
      setError(null);
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
      <DialogContent className="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle>Crear Código QR</DialogTitle>
          <DialogDescription>
            Genera un código QR y obtén el enlace de descarga (PNG).
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="target_url">URL destino</Label>
            <Input
              id="target_url"
              ref={targetInputRef}
              value={formData.target_url}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, target_url: e.target.value }))
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

          {/* Format selector removed: backend only returns PNG now. */}

          {error && (
            <div className="text-sm text-destructive bg-destructive/10 border border-destructive/20 rounded-md p-3">
              {error}
            </div>
          )}

          <div className="flex items-center gap-4">
            <div className="flex-1">
              <div className="text-sm text-muted-foreground">
                Previsualización
              </div>
              <div className="mt-2 p-2 aspect-square w-full h-full bg-white dark:bg-slate-800 rounded-md flex items-center justify-center">
                {/* Show generated PNG preview when available, otherwise show the target url as placeholder */}
                {previewUrl ? (
                  <img
                    src={previewUrl}
                    alt="QR preview"
                    className="w-full h-auto object-contain aspect-square"
                  />
                ) : (
                  <div className="text-xs break-all text-center">
                    {formData.target_url || "URL destino..."}
                  </div>
                )}
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => setOpen(false)}
              disabled={loading}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? "Generando..." : "Generar QR"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
