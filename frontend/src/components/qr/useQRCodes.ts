import { useEffect, useState, useCallback, useRef } from "react";

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

  // derive initial values (used by the init effect). Keeping these as
  // primitives makes it obvious to ESLint what the effect depends on.
  const initialPage = initial?.page ?? 1;
  const initialPerPage = initial?.perPage ?? 20;
  const initialQuery = initial?.query ?? "";

  // Refs to hold the latest state values so memoized callbacks can read
  // them without needing to declare state vars as dependencies.
  const pageRef = useRef<number>(page);
  const perPageRef = useRef<number>(perPage);
  const queryRef = useRef<string>(query);

  const loadItems = useCallback(async (opts?: { page?: number; perPage?: number; query?: string }) => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem("token");

      // Resolve parameters: prefer opts, fall back to current refs
      const pageNum = opts?.page ?? pageRef.current;
      const perPageNum = opts?.perPage ?? perPageRef.current;
      const searchQuery = typeof opts?.query !== "undefined" ? opts!.query : queryRef.current;

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
  }, []);

  // initialize from URL (runs when initial* values change or loadItems reference changes)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const pageNum = parseInt(params.get("page") || String(initialPage), 10) || 1;
    const searchQuery = params.get("query") || initialQuery || "";
    const perPageNum = parseInt(params.get("per_page") || String(initialPerPage), 10) || 20;
    setPage(pageNum);
    setQuery(searchQuery);
    setPerPage(perPageNum);
    loadItems({ page: pageNum, perPage: perPageNum, query: searchQuery });
  }, [initialPage, initialPerPage, initialQuery, loadItems]);

  // Stable pushUrl using refs so it doesn't need to change when state changes
  const pushUrl = useCallback((p: number, q: string, pp?: number) => {
    const params = new URLSearchParams(window.location.search);
    params.set("page", String(p));
    if (q) params.set("query", q);
    else params.delete("query");
    params.set("per_page", String(typeof pp !== "undefined" ? pp : perPageRef.current));
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, "", newUrl);
  }, []);

  useEffect(() => {
    // sync URL and reload when page/perPage/query change
    pushUrl(page, query);
    loadItems({ page, perPage, query });
  }, [page, perPage, query, loadItems, pushUrl]);

  // keep refs in sync with state so memoized callbacks read latest values
  useEffect(() => {
    pageRef.current = page;
  }, [page]);
  useEffect(() => {
    perPageRef.current = perPage;
  }, [perPage]);
  useEffect(() => {
    queryRef.current = query;
  }, [query]);

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
