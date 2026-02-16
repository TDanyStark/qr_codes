import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { QrForm } from "@/components/createQr/QrForm";
import { QrResultLink } from "@/components/createQr/QrResultLink";
import { QrPreview } from "@/components/createQr/QrPreview";
import { useEditQrCode } from "./useEditQrCode";
import type { Qr } from "./useQRCodes";
import useSubscriberUsers from "./useSubscriberUsers";

interface Props {
  qr: Qr | null;
  onClose?: () => void;
  onUpdated?: () => void;
}

export default function EditQrCode({ qr, onClose, onUpdated }: Props) {
  const {
    open,
    setOpen,
    formData,
    updateField,
    loading,
    links,
    previewUrl,
    copied,
    handleSubmit,
    handleClose,
    copyRedirect,
  } = useEditQrCode({ qr, onUpdated });
  const { users, loading: loadingUsers } = useSubscriberUsers(open);

  return (
    <Dialog 
      open={open} 
      onOpenChange={(v) => { 
        setOpen(v); 
        if (!v) onClose?.(); 
      }}
    >
      <DialogContent className="sm:max-w-[900px] max-h-[650px] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle>Editar Código QR</DialogTitle>
          <DialogDescription>Actualiza el objetivo o los colores del QR. El token permanecerá igual; las imágenes se actualizarán si hace falta.</DialogDescription>
        </DialogHeader>
        <div className="overflow-auto flex-1 min-h-0">
          <form id="edit-qr-form" onSubmit={handleSubmit} className="p-1 flex flex-col md:flex-row gap-6">
            <div className="flex-1 flex flex-col gap-4">
              <QrForm
                formData={formData}
                updateField={updateField}
                users={users}
                loadingUsers={loadingUsers}
              />
              <div>
                <hr />
                <p className="mt-2">result</p>
                <QrResultLink redirect={links.redirect} copied={copied} onCopy={copyRedirect} />
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
            onClick={() => {
              setOpen(false);
              handleClose();
              onClose?.();
            }}
            disabled={loading}
          >
            Cancelar
          </Button>
          <Button type="submit" disabled={loading} form="edit-qr-form">{loading ? 'Guardando...' : 'Guardar cambios'}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
