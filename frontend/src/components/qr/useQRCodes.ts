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

      // Resolve parameters: prefer opts, fall back to current state
      const pageNum = opts?.page ?? page;
      const perPageNum = opts?.perPage ?? perPage;
      const searchQuery = typeof opts?.query !== "undefined" ? opts!.query : query;

      // Build query string for API
      const params = new URLSearchParams();
      params.set("page", String(pageNum));
      params.set("per_page", String(perPageNum));
      if (searchQuery) params.set("query", searchQuery);

      const res = await fetch(`/api/qrcodes?${params.toString()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) throw new Error(await res.text());
      const data = await res.json();

      // API may return an envelope { data: { items: [...], pagination: {...}, url_base_token } }
      // or a plain array/object. Normalize to itemsArray and optional baseToken.
      let responseData = data?.data ?? data;
      let baseToken: string | null = null;

      if (responseData && typeof responseData === "object" && !Array.isArray(responseData) && responseData.items) {
        baseToken = responseData.url_base_token ?? null;
        setUrlBaseToken(baseToken);

        // items are inside the envelope
        const itemsArray = responseData.items;

        // pagination info may be in data.data.pagination or data.pagination
        const pagination = data?.data?.pagination ?? data?.pagination ?? null;
        if (pagination) {
          setTotalPages(
            pagination.total_pages ?? Math.max(1, Math.ceil((pagination.total ?? 0) / (pagination.per_page ?? perPageNum)))
          );
          setPerPage(pagination.per_page ?? perPageNum);
          setPage(pagination.page ?? pageNum);
        } else if (Array.isArray(itemsArray)) {
          setTotalPages(Math.max(1, Math.ceil((data?.total ?? itemsArray.length) / perPageNum)));
        }

        // ensure itemsArray is actually an array before setting
        if (!Array.isArray(itemsArray)) responseData = [];
        else responseData = itemsArray;
      } else {
        // No envelope, clear base token and ensure responseData is an array
        setUrlBaseToken(null);
      }

      if (!Array.isArray(responseData)) responseData = [];
      setItems(responseData as Qr[]);
    } catch (err) {
      setError(String(err));
    } finally {
      setLoading(false);
    }
  };

  // initialize from URL once
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const pageNum = parseInt(params.get("page") || String(initial?.page ?? "1"), 10) || 1;
    const searchQuery = params.get("query") || initial?.query || "";
    const perPageNum = parseInt(params.get("per_page") || String(initial?.perPage ?? "20"), 10) || 20;
    setPage(pageNum);
    setQuery(searchQuery);
    setPerPage(perPageNum);
    loadItems({ page: pageNum, perPage: perPageNum, query: searchQuery });
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
