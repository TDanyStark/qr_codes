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
import { useCreateQrCode } from "./createQr/useCreateQrCode";
import { QrForm } from "./createQr/QrForm";
import { QrResultLink } from "./createQr/QrResultLink";
import { QrPreview } from "./createQr/QrPreview";

interface CreateQrCodeProps {
  onQrCreated?: () => void;
}

export default function CreateQrCode({ onQrCreated }: CreateQrCodeProps) {
  const {
    open,
    handleOpenChange,
    formData,
    updateField,
    loading,
    created,
    handleSubmit,
    handleCreateAnother,
    links,
    previewUrl,
    copied,
    copyRedirect,
  } = useCreateQrCode({ onCreated: onQrCreated });
  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button>Crear QR (n)</Button>
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
              <QrForm formData={formData} updateField={updateField} />
              <div>
                <hr />
                <p className="mt-2">result</p>
                <QrResultLink
                  redirect={links.redirect}
                  copied={copied}
                  onCopy={copyRedirect}
                />
              </div>
            </div>
            <div className="flex items-center gap-4 w-[320px] max-w-full">
              <QrPreview links={links} previewUrl={previewUrl} />
            </div>
          </form>
        </div>
        <DialogFooter className="flex-shrink-0 bg-transparent">
          <Button
            type="button"
            variant="outline"
            onClick={() => handleOpenChange(false)}
            disabled={loading}
          >
            Cancelar
          </Button>
          {!created ? (
            <Button type="submit" disabled={loading} form="create-qr-form">
              {loading ? "Generando..." : "Generar QR"}
            </Button>
          ) : (
            <Button type="button" onClick={handleCreateAnother}>
              Crear Otro
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
