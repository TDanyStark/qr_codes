import { useEffect, useState, useCallback, useMemo } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";

export type Qr = {
  id: number;
  token: string;
  name?: string | null;
  target_url: string;
  owner_user_id?: number;
  owner_name?: string | null;
  owner_email?: string | null;
  foreground?: string | null;
  background?: string | null;
  created_at: string;
  updated_at: string;
};

type Pagination = {
  total_pages?: number;
  per_page?: number;
  page?: number;
  total?: number;
} | null;
interface Normalized {
  items: Qr[];
  pagination: Pagination;
  urlBaseToken: string | null;
  raw: unknown;
}

type ApiResponse = {
  statusCode?: number;
  data?: {
    items?: Qr[];
    pagination?: {
      page?: number;
      per_page?: number;
      total?: number;
      total_pages?: number;
    };
    url_base_token?: string;
  };
};

export default function useQRCodes(initial?: {
  page?: number;
  perPage?: number;
  query?: string;
}) {
  // Only control params (page, perPage, query). Data comes from react-query.
  const [urlBaseToken, setUrlBaseToken] = useState<string | null>(null); // kept separately in case API omite en una respuesta
  // Initialize from URL search params when available so back/forward preserves state
  const parseInitialFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    const parsedPage = Number(params.get("page"));
    const parsedPerPage = Number(params.get("per_page"));
    const parsedQuery = params.get("query") ?? "";

    const pageInit =
      typeof initial?.page === "number"
        ? initial!.page
        : Number.isFinite(parsedPage) && parsedPage > 0
        ? parsedPage
        : 1;
    const perPageInit =
      typeof initial?.perPage === "number"
        ? initial!.perPage
        : Number.isFinite(parsedPerPage) && parsedPerPage > 0
        ? parsedPerPage
        : 10;
    const queryInit = typeof initial?.query === "string" ? initial!.query : parsedQuery;

    return { pageInit, perPageInit, queryInit };
  };

  const { pageInit, perPageInit, queryInit } = parseInitialFromUrl();

  const [page, setPage] = useState<number>(pageInit);
  const [perPage, setPerPage] = useState<number>(perPageInit);
  const [query, setQuery] = useState<string>(queryInit);

  const queryClient = useQueryClient();

  const fetchQRCodes = useCallback(
    async (p: number, pp: number, q: string): Promise<Normalized> => {
      const token = localStorage.getItem("token");
      const params = new URLSearchParams({
        page: String(p),
        per_page: String(pp),
      });
      if (q) params.set("query", q);

      const res = await axios.get(`/api/qrcodes?${params.toString()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (res.status !== 200) throw new Error(`Error ${res.status}: ${res.statusText}`);

      const json: ApiResponse = res.data;
      const data = json.data ?? {};

      return {
        items: data.items ?? [],
        pagination: (data.pagination ?? null) as Pagination,
        urlBaseToken: data.url_base_token ?? null,
        raw: json,
      };
    },
    []
  );

  const {
    data,
    isLoading,
    error: queryError,
  } = useQuery<Normalized, Error>({
    queryKey: ["qrcodes", page, perPage, query],
    queryFn: () => fetchQRCodes(page, perPage, query),
    placeholderData: (prev) => prev,
  });

  // Actual items
  const items = useMemo(() => data?.items ?? [], [data]);

  // Set urlBaseToken when changes (side-effect only for backward compat if UI lee estado local)
  useEffect(() => {
    if (typeof data?.urlBaseToken !== "undefined")
      setUrlBaseToken(data.urlBaseToken);
  }, [data?.urlBaseToken]);

  // Derive total pages (no setState loop)
  const totalPages = useMemo(() => {
    if (data?.pagination) {
      const pag = data.pagination;
      if (typeof pag.total_pages === "number")
        return Math.max(1, pag.total_pages);
      const total = pag.total ?? items.length;
      const per = pag.per_page ?? perPage;
      return Math.max(1, Math.ceil(total / per));
    }
    return Math.max(1, Math.ceil(items.length / perPage));
  }, [data?.pagination, items.length, perPage]);

  const loadItems = useCallback(
    async (opts?: { page?: number; perPage?: number; query?: string }) => {
      // Solo actualiza parámetros; react-query hará el fetch automáticamente.
      if (opts) {
        if (typeof opts.perPage === "number" && opts.perPage !== perPage)
          setPerPage(opts.perPage);
        if (typeof opts.page === "number" && opts.page !== page)
          setPage(opts.page);
        if (typeof opts.query !== "undefined" && opts.query !== query)
          setQuery(opts.query);
      } else {
        // Invalidar para revalidar manualmente.
        await queryClient.invalidateQueries({ queryKey: ["qrcodes"] });
      }
    },
    [page, perPage, query, queryClient]
  );

  // pushUrl syncs location bar
  const pushUrl = useCallback(
    (p: number, q: string, pp?: number) => {
      const params = new URLSearchParams(window.location.search);
      params.set("page", String(p));
      if (q) params.set("query", q);
      else params.delete("query");
      params.set("per_page", String(typeof pp !== "undefined" ? pp : perPage));
      const newUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, "", newUrl);
    },
    [perPage]
  );

  useEffect(() => {
    pushUrl(page, query, perPage);
  }, [page, perPage, query, pushUrl]);

  // Keep state in sync when user navigates back/forward (popstate)
  useEffect(() => {
    const onPopState = () => {
      const params = new URLSearchParams(window.location.search);
      const p = Number(params.get("page"));
      const pp = Number(params.get("per_page"));
      const q = params.get("query") ?? "";

      if (Number.isFinite(p) && p > 0 && p !== page) setPage(p);
      if (Number.isFinite(pp) && pp > 0 && pp !== perPage) setPerPage(pp);
      if (q !== query) setQuery(q);
    };

    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, [page, perPage, query]);

  const updatePerPage = (n: number) => {
    setPerPage(n);
    setPage(1);
  };

  const updateQuery = (q: string) => {
    setQuery(q);
    setPage(1);
  };

  return {
    items,
    urlBaseToken,
    loading: isLoading,
    error: queryError ? String(queryError) : null,
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
