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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import axios from "axios";

interface CreateUserProps {
  onUserCreated?: () => void;
}

export default function CreateUser({ onUserCreated }: CreateUserProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    rol: "user" as "user" | "admin",
  });
  const nameInputRef = useRef<HTMLInputElement | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    
    if (!formData.name || !formData.email) {
      setError("Nombre y email son requeridos");
      return;
    }

    setLoading(true);
    try {
      const token = localStorage.getItem("token");
      if (!token) {
        throw new Error("No auth token found");
      }

      await axios.post("/api/users", formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      // Reset form
      setFormData({ name: "", email: "", rol: "user" });
      setOpen(false);
      
      // Notify parent to refresh the users list
      onUserCreated?.();
    } catch (err: unknown) {
      let message = "Error al crear usuario";
      if (err && typeof err === "object" && "response" in err) {
        const axiosError = err as { response?: { data?: { message?: string } } };
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
      // Reset form when closing
      setFormData({ name: "", email: "", rol: "user" });
      setError(null);
    }
    // If opening, focus name input (give time for dialog to render)
    if (newOpen) {
      setTimeout(() => {
        // First try the ref (if underlying Input exposes it), otherwise fallback to getElementById
        const el = nameInputRef.current || document.getElementById("name");
        if (el && typeof (el as HTMLInputElement).focus === "function") {
          (el as HTMLInputElement).focus();
          // place cursor at end
          const val = (el as HTMLInputElement).value || "";
          try {
            (el as HTMLInputElement).setSelectionRange(val.length, val.length);
          } catch {
            // ignore if not supported
          }
        }
      }, 50);
    }
  };

  // Global keyboard shortcut: press 'n' or 'N' to open the Create User dialog
  useEffect(() => {
    const onKeyDown = (e: KeyboardEvent) => {
      // Ignore when any modifier is pressed
      if (e.ctrlKey || e.altKey || e.metaKey) return;

      // Do not trigger if focus is in an editable field
      const active = document.activeElement;
      if (active) {
        const tag = active.tagName.toLowerCase();
        const isEditable = tag === "input" || tag === "textarea" || (active as HTMLElement).isContentEditable;
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
        <Button>Crear Usuario</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Crear Nuevo Usuario</DialogTitle>
          <DialogDescription>
            Completa los datos para crear un nuevo usuario en el sistema.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              ref={nameInputRef}
              value={formData.name}
              onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
              placeholder="Nombre completo"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
              placeholder="correo@ejemplo.com"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="rol">Rol</Label>
            <Select
              value={formData.rol}
              onValueChange={(value: "user" | "admin") => 
                setFormData(prev => ({ ...prev, rol: value }))
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="Selecciona un rol" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="user">Usuario</SelectItem>
                <SelectItem value="admin">Administrador</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {error && (
            <div className="text-sm text-destructive bg-destructive/10 border border-destructive/20 rounded-md p-3">
              {error}
            </div>
          )}

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
              {loading ? "Creando..." : "Crear Usuario"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}