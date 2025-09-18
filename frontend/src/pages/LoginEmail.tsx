import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import pendingEmail from "../lib/pendingEmail";

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
    <div className="min-h-[60vh] flex items-center justify-center">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-md bg-gray-800 rounded-lg p-6 shadow"
      >
        <h1 className="text-2xl font-semibold mb-4">Iniciar sesión</h1>
        <p className="text-sm text-gray-300 mb-4">
          Introduce tu email. Recibirás un código para completar el acceso.
        </p>

        <label className="block mb-2 text-sm">Email</label>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full p-3 rounded bg-gray-900 border border-gray-700 focus:outline-none"
          placeholder="tu@correo.com"
          required
        />

        {error && <div className="text-red-400 mt-3">{error}</div>}

        <button
          type="submit"
          className="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded disabled:opacity-60"
          disabled={loading}
        >
          {loading ? "Enviando..." : "Enviar código"}
        </button>
      </form>
    </div>
  );
}
