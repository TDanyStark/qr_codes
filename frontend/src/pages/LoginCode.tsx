import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import pendingEmail from "../lib/pendingEmail";
import axios from "axios";
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

export default function LoginCode() {
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) return
    axios.get('/api/token/verify', { headers: { Authorization: `Bearer ${token}` } })
      .then(() => navigate('/qr_codes'))
      .catch(() => {})
  }, [navigate])

  useEffect(() => {
    // If there's no pending email, redirect back to start
    const email = pendingEmail.getPendingEmail();
    if (!email) navigate("/");
  }, [navigate]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    if (!code) {
      setError("El código es requerido");
      return;
    }
    setLoading(true);
    try {
      const pending = pendingEmail.getPendingEmail();
      if (!pending) {
        setError("No hay email pendiente");
        setLoading(false);
        return;
      }

      const resp = await axios.post("/api/login/code/verify", {
        email: pending,
        code,
      });

      // Backend returns a wrapped response: { statusCode, data: { ok, token, message } }
      const body = resp?.data?.data ?? resp?.data ?? null;
      const ok = body?.ok;
      const token = body?.token as string | undefined;
      const message = body?.message as string | undefined;

      if (ok) {
        // store token (simple localStorage for now) and clear pending
        if (token) localStorage.setItem("token", token);
        pendingEmail.clearPending();
        navigate("/qr_codes");
      } else {
        setError(message || "Código inválido");
      }
    } catch (err: unknown) {
      let msg: string | null = null;
      if (err && typeof err === "object" && "message" in err) {
        const maybe = err as { message?: unknown };
        if (typeof maybe.message === "string") msg = maybe.message;
      }
      setError(msg || "Error al verificar el código");
    } finally {
      setLoading(false);
    }
  }

  async function handleResend() {
    const email = pendingEmail.getPendingEmail();
    if (!email) return;
    setLoading(true);
    try {
      await axios.post("/api/login/code", { email });
    } catch {
      setError("Error al reenviar código");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-[60vh] flex items-center justify-center px-4">
      <form onSubmit={handleSubmit} className="w-full max-w-md bg-card rounded-lg p-6 shadow">
        <h1 className="text-2xl font-semibold mb-2 text-card-foreground">Introduce el código</h1>
        <p className="text-sm text-muted-foreground mb-4">Revisa tu email y escribe el código que te enviamos.</p>

        <label className="block mb-2 text-sm text-card-foreground">Código</label>
        <Input
          type="text"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          placeholder="123456"
          required
        />

        {error && <div className="text-destructive mt-3">{error}</div>}

        <div className="flex gap-2 mt-4">
          <Button type="submit" className="flex-1" disabled={loading}>
            {loading ? 'Verificando...' : 'Verificar'}
          </Button>

          <Button variant="secondary" type="button" onClick={handleResend} disabled={loading}>
            Reenviar
          </Button>
        </div>
      </form>
    </div>
  );
}
