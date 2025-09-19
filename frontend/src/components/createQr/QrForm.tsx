import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { QrFormData } from "./types";
import { memo } from "react";

interface QrFormProps {
  formData: QrFormData;
  updateField: <K extends keyof QrFormData>(key: K, value: QrFormData[K]) => void;
}

export const QrForm = memo(function QrForm({ formData, updateField }: QrFormProps) {
  return (
    <div className="space-y-4 mb-2 ">
      <div className="space-y-2">
        <Label htmlFor="target_url">URL destino</Label>
        <Input
          id="target_url"
          value={formData.target_url}
          onChange={(e) => updateField("target_url", e.target.value)}
          placeholder="https://example.com"
          required
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="name">Nombre (opcional)</Label>
        <Input
          id="name"
            value={formData.name}
            onChange={(e) => updateField("name", e.target.value)}
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
            onChange={(e) => updateField("foreground", e.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="background">Color fondo</Label>
          <Input
            id="background"
            type="color"
            value={formData.background}
            onChange={(e) => updateField("background", e.target.value)}
          />
        </div>
      </div>
    </div>
  );
});
