import { useCallback, useEffect, useRef, useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { defaultFormData } from "./types";
import type { QrFormData, QrLinks } from "./types";

interface UseCreateQrCodeParams {
  onCreated?: () => void;
}

interface UseCreateQrCodeReturn {
  open: boolean;
  setOpen: (v: boolean) => void;
  formData: QrFormData;
  updateField: <K extends keyof QrFormData>(key: K, value: QrFormData[K]) => void;
  resetForm: () => void;
  loading: boolean;
  created: boolean;
  links: QrLinks;
  previewUrl: string | null;
  copied: boolean;
  handleSubmit: (e: React.FormEvent) => Promise<void>;
  handleCreateAnother: () => void;
  targetInputRef: React.RefObject<HTMLInputElement>;
  copyRedirect: () => Promise<void>;
  handleOpenChange: (open: boolean) => void;
}

/**
 * Encapsula la lógica para crear un código QR.
 * - Manejo de formulario y validación mínima.
 * - Llamada a API y extracción flexible de links.
 * - Gestión de estado de creación para deshabilitar crear múltiples veces.
 * - Reset centralizado.
 */
export function useCreateQrCode({ onCreated }: UseCreateQrCodeParams = {}): UseCreateQrCodeReturn {
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState<QrFormData>(defaultFormData);
  const [loading, setLoading] = useState(false);
  const [created, setCreated] = useState(false);
  const [links, setLinks] = useState<QrLinks>({});
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);
  const targetInputRef = useRef<HTMLInputElement | null>(null);

  const focusTarget = () => {
    setTimeout(() => {
      const el = targetInputRef.current || document.getElementById("target_url");
      if (el && typeof (el as HTMLInputElement).focus === "function") {
        (el as HTMLInputElement).focus();
      }
    }, 50);
  };

  const resetForm = useCallback(() => {
    setFormData(defaultFormData);
    setLinks({});
    setPreviewUrl(null);
    setCopied(false);
    setCreated(false);
  }, []);

  const extractLinks = (resData: unknown): QrLinks => {
    const isObj = (v: unknown): v is Record<string, unknown> => typeof v === "object" && v !== null;
    let maybeLinks: unknown = null;
    if (isObj(resData) && isObj(resData.links)) {
      maybeLinks = resData.links;
    } else if (isObj(resData) && isObj(resData.data) && isObj(resData.data.links)) {
      maybeLinks = resData.data.links;
    }
    const l = maybeLinks as Record<string, unknown> | null;
    return {
      png: (l?.png as string) ?? null,
      svg: (l?.svg as string) ?? null,
      redirect: (l?.redirect as string) ?? null,
    };
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (created) return; // bloquear múltiples envíos

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

      const res = await axios.post("/api/qrcodes", payload, {
        headers: { Authorization: `Bearer ${token}` },
      });

      const extracted = extractLinks(res?.data);
      setLinks(extracted);
      // prefer png luego svg
      setPreviewUrl(extracted.png || extracted.svg || null);
      setCreated(true);
      onCreated?.();
    } catch (err: unknown) {
      let message = "Error al crear QR";
      if (err && typeof err === "object" && "response" in err) {
        const axiosError = err as { response?: { data?: { message?: string } } };
        message = axiosError.response?.data?.message || message;
      } else if (err && typeof err === "object" && "message" in err) {
        message = (err as { message: string }).message;
      }
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateAnother = () => {
    resetForm();
    focusTarget();
  };

  const updateField = useCallback(<K extends keyof QrFormData>(key: K, value: QrFormData[K]) => {
    setFormData(prev => ({ ...prev, [key]: value }));
  }, []);

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

  const handleOpenChange = (value: boolean) => {
    setOpen(value);
    if (!value) {
      resetForm();
    } else {
      focusTarget();
    }
  };

  // hotkey n
  useEffect(() => {
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.ctrlKey || e.altKey || e.metaKey) return;
      const active = document.activeElement;
      if (active) {
        const tag = active.tagName.toLowerCase();
        const isEditable = tag === "input" || tag === "textarea" || (active as HTMLElement).isContentEditable;
        if (isEditable) return;
      }
      if (e.key === "n" || e.key === "N") {
        e.preventDefault();
        setOpen(true);
        focusTarget();
      }
    };
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, []);

  return {
    open,
    setOpen,
    formData,
    updateField,
    resetForm,
    loading,
    created,
    links,
    previewUrl,
    copied,
    handleSubmit,
    handleCreateAnother,
  targetInputRef: targetInputRef as React.RefObject<HTMLInputElement>,
    copyRedirect,
    handleOpenChange,
  };
}
