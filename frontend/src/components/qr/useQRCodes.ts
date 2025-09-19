import { useEffect, useState, useCallback, useMemo } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";

export type Qr = {
  id: number;
  token: string;
  name?: string | null;
  target_url: string;
  owner_user_id?: number;
  owner_name?: string | null;
  owner_email?: string | null;
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

export default function useQRCodes(initial?: {
  page?: number;
  perPage?: number;
  query?: string;
}) {
  // Only control params (page, perPage, query). Data comes from react-query.
  const [urlBaseToken, setUrlBaseToken] = useState<string | null>(null); // kept separately in case API omite en una respuesta
  const [page, setPage] = useState<number>(initial?.page ?? 1);
  const [perPage, setPerPage] = useState<number>(initial?.perPage ?? 10);
  const [query, setQuery] = useState<string>(initial?.query ?? "");

  const queryClient = useQueryClient();

  const fetchQRCodes = useCallback(
    async (p: number, pp: number, q: string): Promise<Normalized> => {
      const token = localStorage.getItem("token");
      const params = new URLSearchParams();
      params.set("page", String(p));
      params.set("per_page", String(pp));
      if (q) params.set("query", q);
      const res = await fetch(`/api/qrcodes?${params.toString()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) throw new Error(await res.text());
      const raw = await res.json();
      const rawObj =
        raw && typeof raw === "object" ? (raw as Record<string, unknown>) : {};
      const dataLayer = (
        rawObj && "data" in rawObj ? rawObj.data : raw
      ) as unknown;
      let itemsArr: Qr[] = [];
      let baseToken: string | null = null;
      let pagination: Pagination = null;
      if (
        dataLayer &&
        typeof dataLayer === "object" &&
        !Array.isArray(dataLayer)
      ) {
        const layerObj = dataLayer as Record<string, unknown>;
        if (Array.isArray(layerObj.items)) itemsArr = layerObj.items as Qr[];
        if (typeof layerObj.url_base_token === "string")
          baseToken = layerObj.url_base_token;
        const pagCandidate = (layerObj.pagination ??
          (rawObj.pagination as unknown)) as Pagination;
        if (pagCandidate && typeof pagCandidate === "object")
          pagination = pagCandidate;
      } else if (Array.isArray(dataLayer)) {
        itemsArr = dataLayer as Qr[];
      }
      return { items: itemsArr, pagination, urlBaseToken: baseToken, raw };
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

  const updatePerPage = (n: number) => {
    setPerPage(n);
    setPage(1);
    pushUrl(1, query, n);
  };

  const updateQuery = (q: string) => {
    setQuery(q);
    setPage(1);
    pushUrl(1, q, perPage);
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
