import { useCallback, useEffect, useRef, useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import type { Qr } from "./useQRCodes";
import type { QrFormData, QrLinks } from "../createQr/types";

interface UseEditQrCodeParams {
  qr: Qr | null;
  onUpdated?: () => void;
}

interface UseEditQrCodeReturn {
  open: boolean;
  setOpen: (v: boolean) => void;
  formData: QrFormData;
  updateField: <K extends keyof QrFormData>(
    key: K,
    value: QrFormData[K]
  ) => void;
  loading: boolean;
  links: QrLinks;
  previewUrl: string | null;
  copied: boolean;
  handleSubmit: (e?: React.FormEvent) => Promise<void>;
  handleClose: () => void;
  copyRedirect: () => Promise<void>;
  targetInputRef: React.RefObject<HTMLInputElement>;
}

const asLinks = (resData: unknown): QrLinks => {
  const isObj = (v: unknown): v is Record<string, unknown> =>
    typeof v === "object" && v !== null;
  let maybeLinks: unknown = null;
  if (isObj(resData) && isObj(resData.links)) maybeLinks = resData.links;
  else if (isObj(resData) && isObj(resData.data) && isObj(resData.data.links))
    maybeLinks = resData.data.links;
  const l = maybeLinks as Record<string, unknown> | null;
  return {
    png: (l?.png as string) ?? null,
    svg: (l?.svg as string) ?? null,
    redirect: (l?.redirect as string) ?? null,
  };
};

export function useEditQrCode({
  qr,
  onUpdated,
}: UseEditQrCodeParams): UseEditQrCodeReturn {
  const [open, setOpen] = useState<boolean>(false);
  const [formData, setFormData] = useState<QrFormData>({
    target_url: "",
    name: "",
    foreground: "#000000",
    background: "#ffffff",
  });
  const [loading, setLoading] = useState(false);
  const [links, setLinks] = useState<QrLinks>({});
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);
  const targetInputRef = useRef<HTMLInputElement | null>(null);

  useEffect(() => {
    if (qr) {
      setFormData({
        target_url: qr.target_url ?? "",
        name: qr.name ?? "",
        foreground: "#000000",
        background: "#ffffff",
      });
      // Try to fetch QR details (including links) from backend so we show exact preview
      (async () => {
        const token = qr.token;
        // optimistic fallback (may be overwritten by backend response)
        if (token) {
          const png = `/tmp/qrcodes/${token}.png`;
          const svg = `/tmp/qrcodes/${token}.svg`;
          const base = window.location.origin;
          const redirect = `${base}/r/${token}`;
          setLinks({ png, svg, redirect });
          setPreviewUrl(png || svg || null);
        } else {
          setLinks({});
          setPreviewUrl(null);
        }

        try {
          const tokenAuth = localStorage.getItem("token");
          const res = await axios.get(`/api/qrcodes/${qr.id}`, {
            headers: tokenAuth ? { Authorization: `Bearer ${tokenAuth}` } : {},
          });
          // backend returns { qr: ..., links: { png, svg, redirect } } or { data: { ... } }
          const maybe = res?.data ?? {};
          let remoteLinks: unknown = null;
          if (maybe.links) remoteLinks = maybe.links;
          else if (maybe.data?.links) remoteLinks = maybe.data.links;
          else if (maybe.data?.qr?.links) remoteLinks = maybe.data.qr.links;

          if (remoteLinks && typeof remoteLinks === "object") {
            const rl = remoteLinks as Record<string, unknown>;
            const png = typeof rl.png === "string" ? rl.png : null;
            const svg = typeof rl.svg === "string" ? rl.svg : null;
            const redirect =
              typeof rl.redirect === "string" ? rl.redirect : null;
            setLinks({ png, svg, redirect });
            setPreviewUrl(png || svg || null);
          }
        } catch {
          // ignore - keep optimistic/fallback links
        }
      })();
      setCopied(false);
      setOpen(true);
      setTimeout(() => {
        const el =
          targetInputRef.current || document.getElementById("target_url");
        if (el && typeof (el as HTMLInputElement).focus === "function") {
          (el as HTMLInputElement).focus();
        }
      }, 50);
    } else {
      setOpen(false);
    }
  }, [qr]);

  const updateField = useCallback(
    <K extends keyof QrFormData>(key: K, value: QrFormData[K]) => {
      setFormData((prev) => ({ ...prev, [key]: value }));
    },
    []
  );

  const copyRedirect = async () => {
    if (!links.redirect) return;
    try {
      await navigator.clipboard.writeText(links.redirect);
    } catch {
      const el = document.createElement("textarea");
      el.value = links.redirect;
      document.body.appendChild(el);
      el.select();
      document.execCommand("copy");
      document.body.removeChild(el);
    } finally {
      setCopied(true);
      setTimeout(() => setCopied(false), 1600);
    }
  };

  const handleSubmit = async (e?: React.FormEvent) => {
    e?.preventDefault?.();
    if (!qr) return;
    if (!formData.target_url) {
      toast.error("La URL destino es requerida");
      return;
    }

    setLoading(true);
    try {
      const token = localStorage.getItem("token");
      if (!token) throw new Error("No auth token found");

      const payload = {
        target_url: formData.target_url,
        name: formData.name || undefined,
        foreground: formData.foreground,
        background: formData.background,
        format: "png",
      };

      // Try regenerate endpoint (preferred). If it doesn't exist, fall back to GET to fetch updated links.
      let res;
      try {
        res = await axios.post(`/api/qrcodes/${qr.id}/regenerate`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
      } catch (errUnknown: unknown) {
        // If regenerate endpoint is not available (404/405), try GET single qr endpoint
        const maybe = errUnknown as
          | { response?: { status?: number } }
          | undefined;
        const status = maybe?.response?.status ?? null;
        if (status === 404 || status === 405 || status === 501) {
          res = await axios.get(`/api/qrcodes/${qr.id}`, {
            headers: { Authorization: `Bearer ${token}` },
          });
        } else {
          throw errUnknown;
        }
      }

      const extracted = asLinks(res?.data);
      setLinks(extracted);
      setPreviewUrl(extracted.png || extracted.svg || null);
      toast.success("QR actualizado");
      onUpdated?.();
    } catch (err: unknown) {
      let message = "Error al actualizar QR";
      if (err && typeof err === "object" && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string } } };
        message = axiosErr.response?.data?.message || message;
      } else if (err && typeof err === "object" && "message" in err) {
        message = (err as { message: string }).message;
      }
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setOpen(false);
  };

  return {
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
    targetInputRef: targetInputRef as React.RefObject<HTMLInputElement>,
  };
}
