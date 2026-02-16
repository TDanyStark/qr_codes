import { useMemo, useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import { toast, Toaster } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";

type ReportSettings = {
  id: number;
  schedule_type: "monthly" | "weekly";
  day_of_month?: number | null;
  day_of_week?: number | null;
  time_of_day: string;
  timezone: string;
  looker_url?: string | null;
  active: boolean;
  last_run_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
};

type FormState = {
  schedule_type: "monthly" | "weekly";
  day_of_month: string;
  day_of_week: string;
  time_of_day: string;
  timezone: string;
  looker_url: string;
  active: boolean;
};

const emptyForm: FormState = {
  schedule_type: "monthly",
  day_of_month: "1",
  day_of_week: "1",
  time_of_day: "08:00",
  timezone: "UTC",
  looker_url: "",
  active: false,
};

const weekdays = [
  { value: "1", label: "Lunes" },
  { value: "2", label: "Martes" },
  { value: "3", label: "Miercoles" },
  { value: "4", label: "Jueves" },
  { value: "5", label: "Viernes" },
  { value: "6", label: "Sabado" },
  { value: "7", label: "Domingo" },
];

async function fetchReportSettings(): Promise<ReportSettings[]> {
  const token = localStorage.getItem("token");
  if (!token) {
    throw new Error("No auth token found");
  }

  const res = await axios.get("/api/report-settings", {
    headers: { Authorization: `Bearer ${token}` },
  });

  const body = res.data as { statusCode?: number; data?: ReportSettings[] };
  return body.data ?? [];
}

function normalizeTime(value: string) {
  if (!value) return "08:00:00";
  if (value.length === 5) return `${value}:00`;
  return value;
}

function buildFormState(setting?: ReportSettings | null): FormState {
  if (!setting) return emptyForm;
  const time = setting.time_of_day?.slice(0, 5) ?? "08:00";
  return {
    schedule_type: setting.schedule_type ?? "monthly",
    day_of_month: setting.day_of_month ? String(setting.day_of_month) : "1",
    day_of_week: setting.day_of_week ? String(setting.day_of_week) : "1",
    time_of_day: time,
    timezone: setting.timezone ?? "UTC",
    looker_url: setting.looker_url ?? "",
    active: setting.active ?? false,
  };
}

export default function ReportSettingsPage() {
  const queryClient = useQueryClient();
  const { data = [], isLoading, isError, error } = useQuery({
    queryKey: ["report-settings"],
    queryFn: fetchReportSettings,
  });

  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<ReportSettings | null>(null);
  const [formData, setFormData] = useState<FormState>(emptyForm);
  const [saving, setSaving] = useState(false);

  const errorMessage = useMemo(() => {
    if (!isError) return null;
    return error?.message ?? "Error fetching report settings";
  }, [isError, error]);

  const handleOpenCreate = () => {
    setEditing(null);
    setFormData(emptyForm);
    setOpen(true);
  };

  const handleOpenEdit = (setting: ReportSettings) => {
    setEditing(setting);
    setFormData(buildFormState(setting));
    setOpen(true);
  };

  const handleActivate = async (id: number) => {
    setSaving(true);
    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No auth token found");
      await axios.post(`/api/report-settings/${id}/activate`, {}, {
        headers: { Authorization: `Bearer ${token}` },
      });
      toast.success("Configuracion activada");
      queryClient.invalidateQueries({ queryKey: ["report-settings"] });
    } catch (err: unknown) {
      const message = err && typeof err === "object" && "message" in err
        ? (err as { message: string }).message
        : "No se pudo activar la configuracion";
      toast.error(message);
    } finally {
      setSaving(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);

    const payload = {
      schedule_type: formData.schedule_type,
      day_of_month: formData.schedule_type === "monthly" ? Number(formData.day_of_month) : null,
      day_of_week: formData.schedule_type === "weekly" ? Number(formData.day_of_week) : null,
      time_of_day: normalizeTime(formData.time_of_day),
      timezone: formData.timezone,
      looker_url: formData.looker_url || null,
      active: formData.active,
    };

    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No auth token found");

      if (editing) {
        await axios.put(`/api/report-settings/${editing.id}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        toast.success("Configuracion actualizada");
      } else {
        await axios.post("/api/report-settings", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        toast.success("Configuracion creada");
      }

      setOpen(false);
      queryClient.invalidateQueries({ queryKey: ["report-settings"] });
    } catch (err: unknown) {
      const message = err && typeof err === "object" && "message" in err
        ? (err as { message: string }).message
        : "No se pudo guardar la configuracion";
      toast.error(message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="container_section_main">
      <Toaster position="top-right" richColors />
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-4xl font-semibold text-foreground">Reportes</h1>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button onClick={handleOpenCreate}>Nueva configuracion</Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[520px]">
            <DialogHeader>
              <DialogTitle>{editing ? "Editar configuracion" : "Nueva configuracion"}</DialogTitle>
              <DialogDescription>
                Define la frecuencia y el link de Looker para los reportes.
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Frecuencia</Label>
                <Select
                  value={formData.schedule_type}
                  onValueChange={(value: "monthly" | "weekly") =>
                    setFormData((prev) => ({ ...prev, schedule_type: value }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="monthly">Mensual</SelectItem>
                    <SelectItem value="weekly">Semanal</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {formData.schedule_type === "monthly" ? (
                <div className="space-y-2">
                  <Label>Dia del mes</Label>
                  <Select
                    value={formData.day_of_month}
                    onValueChange={(value) =>
                      setFormData((prev) => ({ ...prev, day_of_month: value }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {Array.from({ length: 31 }, (_, i) => i + 1).map((day) => (
                        <SelectItem key={day} value={String(day)}>
                          {day}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              ) : (
                <div className="space-y-2">
                  <Label>Dia de la semana</Label>
                  <Select
                    value={formData.day_of_week}
                    onValueChange={(value) =>
                      setFormData((prev) => ({ ...prev, day_of_week: value }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {weekdays.map((day) => (
                        <SelectItem key={day.value} value={day.value}>
                          {day.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}

              <div className="space-y-2">
                <Label>Hora</Label>
                <Input
                  type="time"
                  value={formData.time_of_day}
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, time_of_day: e.target.value }))
                  }
                  required
                />
              </div>

              <div className="space-y-2">
                <Label>Timezone</Label>
                <Input
                  value={formData.timezone}
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, timezone: e.target.value }))
                  }
                  placeholder="UTC"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label>Looker Studio URL</Label>
                <Input
                  value={formData.looker_url}
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, looker_url: e.target.value }))
                  }
                  placeholder="https://lookerstudio.google.com/..."
                />
              </div>

              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  className="h-4 w-4"
                  checked={formData.active}
                  onChange={(e) =>
                    setFormData((prev) => ({ ...prev, active: e.target.checked }))
                  }
                />
                Activar esta configuracion
              </label>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                  Cancelar
                </Button>
                <Button type="submit" disabled={saving}>
                  {saving ? "Guardando..." : "Guardar"}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {isLoading && <div className="text-muted-foreground">Cargando configuraciones...</div>}
      {isError && <div className="text-destructive">Error: {errorMessage}</div>}

      {!isLoading && !isError && (
        <div className="space-y-3">
          {data.length === 0 && (
            <div className="text-muted-foreground">No hay configuraciones.</div>
          )}
          {data.map((setting) => (
            <div
              key={setting.id}
              className="bg-card border border-border rounded-md p-4 flex flex-col gap-3"
            >
              <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                  <div className="text-lg font-semibold text-foreground">
                    Configuracion #{setting.id}
                  </div>
                  <div className="text-sm text-muted-foreground">
                    {setting.schedule_type === "monthly"
                      ? `Mensual - Dia ${setting.day_of_month}`
                      : `Semanal - ${weekdays.find((d) => d.value === String(setting.day_of_week))?.label ?? "Dia"}`}
                    {` a las ${setting.time_of_day} (${setting.timezone})`}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  {setting.active ? (
                    <Badge variant="secondary">Activa</Badge>
                  ) : (
                    <Badge variant="outline">Inactiva</Badge>
                  )}
                </div>
              </div>

              <div className="text-sm text-muted-foreground">
                Looker: {setting.looker_url ? setting.looker_url : "No definido"}
              </div>
              {setting.last_run_at && (
                <div className="text-xs text-muted-foreground">
                  Ultima ejecucion: {new Date(setting.last_run_at).toLocaleString()}
                </div>
              )}

              <div className="flex flex-wrap gap-2">
                <Button variant="outline" onClick={() => handleOpenEdit(setting)}>
                  Editar
                </Button>
                {!setting.active && (
                  <Button onClick={() => handleActivate(setting.id)} disabled={saving}>
                    Activar
                  </Button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
