import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import pendingEmail from "../lib/pendingEmail";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

export default function LoginEmail() {
  const navigate = useNavigate();

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) return
    axios.get('/api/token/verify', { headers: { Authorization: `Bearer ${token}` } })
      .then(() => navigate('/qr_codes'))
      .catch(() => {})
  }, [navigate])

  
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!email) {
      setError("El email es requerido");
      return;
    }
    setLoading(true);
    try {
      await axios.post("/api/login/code", { email });
      // mark pending email so the code page knows which email is being verified
      pendingEmail.setPendingEmail(email);
      navigate("/login/code");
    } catch (err: unknown) {
      let msg: string | null = null;
      if (err && typeof err === "object" && "message" in err) {
        const maybe = err as { message?: unknown };
        if (typeof maybe.message === "string") msg = maybe.message;
      }
      setError(msg || "Error al enviar el código");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-[60vh] flex items-center justify-center px-4 bg-background">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-md space-y-6 bg-card rounded-lg p-6 shadow-xl border border-border"
      >
        <div className="space-y-2 text-center">
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Iniciar sesión</h1>
          <p className="text-sm text-muted-foreground">
            Introduce tu email. Recibirás un código para completar el acceso.
          </p>
        </div>

        <div className="space-y-2">
          <label htmlFor="email" className="text-sm font-medium text-foreground">
            Email
          </label>
          <Input
            id="email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="tu@correo.com"
            required
          />
        </div>

        {error && (
          <div className="text-sm text-destructive bg-destructive/10 border border-destructive/20 rounded-md p-3">
            {error}
          </div>
        )}

        <Button
          type="submit"
          className="w-full"
          disabled={loading}
        >
          {loading ? "Enviando..." : "Enviar código"}
        </Button>
      </form>
    </div>
  );
}
