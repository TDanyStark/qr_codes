import { useEffect, useState } from "react";

export type Qr = {
  id: number;
  token: string;
  name?: string | null;
  target_url: string;
  owner_user_id?: number;
  owner_name?: string | null;
  owner_email?: string | null;
};

export default function useQRCodes(initial?: { page?: number; perPage?: number; query?: string }) {
  const [items, setItems] = useState<Qr[]>([]);
  const [urlBaseToken, setUrlBaseToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState<number>(initial?.page ?? 1);
  const [perPage, setPerPage] = useState<number>(initial?.perPage ?? 20);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [query, setQuery] = useState<string>(initial?.query ?? "");

  const loadItems = async (opts?: { page?: number; perPage?: number; query?: string }) => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem("token");
      const p = opts?.page ?? page;
      const pp = opts?.perPage ?? perPage;
      const q = typeof opts?.query !== "undefined" ? opts!.query : query;

      const params = new URLSearchParams();
      params.set("page", String(p));
      params.set("per_page", String(pp));
      if (q) params.set("query", q);

      const res = await fetch(`/api/qrcodes?${params.toString()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();
      let d = data?.data ?? data;
      let urlBaseToken: string | null = null;

      if (d && typeof d === "object" && !Array.isArray(d) && d.items) {
        urlBaseToken = d.url_base_token ?? null;
        setUrlBaseToken(urlBaseToken);
        d = d.items;
        const pagination = d && (data?.data?.pagination ?? data?.pagination ?? null);
        if (pagination) {
          setTotalPages(pagination.total_pages ?? Math.max(1, Math.ceil((pagination.total ?? 0) / (pagination.per_page ?? pp))));
          setPerPage(pagination.per_page ?? pp);
          setPage(pagination.page ?? p);
        } else if (Array.isArray(d)) {
          setTotalPages(Math.max(1, Math.ceil((data?.total ?? d.length) / pp)));
        }
      } else {
        setUrlBaseToken(null);
      }

      if (!Array.isArray(d)) d = [];
      setItems(d as Qr[]);
    } catch (err) {
      setError(String(err));
    } finally {
      setLoading(false);
    }
  };

  // initialize from URL once
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const p = parseInt(params.get("page") || String(initial?.page ?? "1"), 10) || 1;
    const q = params.get("query") || initial?.query || "";
    const pp = parseInt(params.get("per_page") || String(initial?.perPage ?? "20"), 10) || 20;
    setPage(p);
    setQuery(q);
    setPerPage(pp);
    loadItems({ page: p, perPage: pp, query: q });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const pushUrl = (p: number, q: string, pp?: number) => {
    const params = new URLSearchParams(window.location.search);
    params.set("page", String(p));
    if (q) params.set("query", q);
    else params.delete("query");
    params.set("per_page", String(typeof pp !== "undefined" ? pp : perPage));
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, "", newUrl);
  };

  useEffect(() => {
    pushUrl(page, query);
    loadItems({ page, perPage, query });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const updatePerPage = (n: number) => {
    // set per page and reset to first page, sync URL and reload immediately
    setPerPage(n);
    setPage(1);
    pushUrl(1, query, n);
    loadItems({ page: 1, perPage: n, query });
  };

  const updateQuery = (q: string) => {
    // update query, reset to first page, sync URL and reload
    setQuery(q);
    setPage(1);
    pushUrl(1, q, perPage);
    loadItems({ page: 1, perPage, query: q });
  };

  return {
    items,
    urlBaseToken,
    loading,
    error,
    page,
    perPage,
    totalPages,
    query,
    setPage,
    setPerPage,
    setQuery,
    loadItems,
    updatePerPage,
    updateQuery,
  } as const;
}
