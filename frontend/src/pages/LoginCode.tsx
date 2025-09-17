import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import pendingEmail from "../lib/pendingEmail";
import axios from "axios";

export default function LoginCode() {
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();

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
        navigate("/users");
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
    <div className="min-h-[60vh] flex items-center justify-center">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-md bg-gray-800 rounded-lg p-6 shadow"
      >
        <h1 className="text-2xl font-semibold mb-4">Introduce el código</h1>
        <p className="text-sm text-gray-300 mb-4">
          Revisa tu email y escribe el código que te enviamos.
        </p>

        <label className="block mb-2 text-sm">Código</label>
        <input
          type="text"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          className="w-full p-3 rounded bg-gray-900 border border-gray-700 focus:outline-none"
          placeholder="123456"
          required
        />

        {error && <div className="text-red-400 mt-3">{error}</div>}

        <div className="flex gap-2 mt-4">
          <button
            type="submit"
            className="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded disabled:opacity-60"
            disabled={loading}
          >
            {loading ? "Verificando..." : "Verificar"}
          </button>

          <button
            type="button"
            onClick={handleResend}
            className="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded disabled:opacity-60"
            disabled={loading}
          >
            Reenviar
          </button>
        </div>
      </form>
    </div>
  );
}
