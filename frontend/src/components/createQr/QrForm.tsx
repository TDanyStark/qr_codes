import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { QrFormData } from "./types";
import { memo, useEffect, useState } from "react";

interface QrFormProps {
  formData: QrFormData;
  updateField: <K extends keyof QrFormData>(key: K, value: QrFormData[K]) => void;
}

export const QrForm = memo(function QrForm({ formData, updateField }: QrFormProps) {
  const [foregroundHex, setForegroundHex] = useState(formData.foreground);
  const [backgroundHex, setBackgroundHex] = useState(formData.background);

  useEffect(() => {
    setForegroundHex(formData.foreground);
  }, [formData.foreground]);
  useEffect(() => {
    setBackgroundHex(formData.background);
  }, [formData.background]);

  const isValidHex = (v: string) => /^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/.test(v);

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
          <div className="flex items-center gap-2">
            <Input
              id="foreground"
              type="color"
              value={formData.foreground}
              onChange={(e) => {
                const v = e.target.value;
                setForegroundHex(v);
                updateField("foreground", v);
              }}
            />
            <Input
              id="foreground_hex"
              value={foregroundHex}
              onChange={(e) => {
                const v = e.target.value;
                setForegroundHex(v);
                if (isValidHex(v)) updateField("foreground", v);
              }}
              placeholder="#rrggbb"
              aria-label="Hex color primer plano"
            />
          </div>
        </div>

        <div className="space-y-2">
          <Label htmlFor="background">Color fondo</Label>
          <div className="flex items-center gap-2">
            <Input
              id="background"
              type="color"
              value={formData.background}
              onChange={(e) => {
                const v = e.target.value;
                setBackgroundHex(v);
                updateField("background", v);
              }}
            />
            <Input
              id="background_hex"
              value={backgroundHex}
              onChange={(e) => {
                const v = e.target.value;
                setBackgroundHex(v);
                if (isValidHex(v)) updateField("background", v);
              }}
              placeholder="#rrggbb"
              aria-label="Hex color fondo"
            />
          </div>
        </div>
      </div>
    </div>
  );
});
