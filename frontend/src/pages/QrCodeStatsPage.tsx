import { useParams, useNavigate, Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { ChevronLeft } from "lucide-react";
import {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { useEffect, useMemo, useState } from "react";
import axios from "axios";
import {
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
  Legend,
} from "recharts";

const COLORS = [
  "#8884d8",
  "#82ca9d",
  "#ffc658",
  "#ff7f50",
  "#a4de6c",
  "#d0ed57",
  "#8dd1e1",
];

type DailyItem = {
  day: string; // YYYY-MM-DD
  cnt: number | string;
};

type CountryItem = {
  country: string | null;
  cnt: number | string;
};

type StatsResponse = {
  qr: Record<string, unknown>;
  daily: DailyItem[];
  countries: CountryItem[];
  cities?: { city: string | null; cnt: number | string }[];
  total: number;
};

function formatError(e: unknown): string {
  if (!e) return "Unknown error";
  if (typeof e === "string") return e;
  if (typeof e === "number" || typeof e === "boolean") return String(e);
  if (e instanceof Error) return e.message;
  try {
    const obj = e as Record<string, unknown>;
    if (obj.error) return formatError(obj.error);
    if (obj.message) return String(obj.message);
    if (obj.type && obj.description)
      return `${String(obj.type)}: ${String(obj.description)}`;
    return JSON.stringify(obj);
  } catch {
    return String(e);
  }
}

export default function QrCodeStatsPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState<StatsResponse | null>(null);

  async function handleExportCsv() {
    if (!id) return;
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get(`/api/qrcodes/${id}/stats/csv`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });

      // backend returns { downloadUrl: '/api/qrcodes/{id}/stats/csv/download/<filename>' }
      const downloadUrl =
        res?.data?.data?.downloadUrl ?? res?.data?.downloadUrl ?? null;
      if (!downloadUrl) {
        alert("No download URL returned");
        return;
      }

      // navigate to downloadUrl to trigger browser download
      // ensure absolute or root-relative URL is used
      if (downloadUrl.startsWith("/")) {
        window.location.assign(downloadUrl);
      } else {
        // fallback: open as returned
        window.location.assign(downloadUrl);
      }
    } catch (e) {
      console.error("CSV export failed", e);
      alert("Error exporting CSV");
    }
  }

  // ...existing code...

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    const token = localStorage.getItem("token");
    axios
      .get(`/api/qrcodes/${id}/stats`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })
      .then((res) => {
        // The backend wraps payload as { statusCode: ..., data: { ... } }
        const payload = res?.data?.data ?? res?.data ?? null;
        if (payload) {
          // ensure total is a number
          if (payload.total !== undefined)
            payload.total = Number(payload.total);
          setData(payload);
        } else {
          setData(null);
        }
      })
      .catch((err) => {
        // Normalize error to string to avoid rendering objects
        const payload = err?.response?.data ?? err;
        setError(formatError(payload?.error ?? payload));
      })
      .finally(() => setLoading(false));
  }, [id]);

  const dailySeries = useMemo(() => {
    if (!data?.daily) return [] as { day: string; count: number }[];
    const arr = data.daily.map((d) => ({ day: d.day, count: Number(d.cnt) }));
    // sort by day asc
    arr.sort((a, b) => (a.day < b.day ? -1 : a.day > b.day ? 1 : 0));
    return arr;
  }, [data]);

  const countrySeries = useMemo(() => {
    if (!data?.countries)
      return [] as { name: string; value: number; color: string }[];
    return data.countries.map((c, i) => ({
      name: c.country || "Unknown",
      value: Number(c.cnt),
      color: COLORS[i % COLORS.length],
    }));
  }, [data]);

  const citySeries = useMemo(() => {
    if (!data?.cities)
      return [] as { name: string; value: number; color: string }[];
    return data.cities.map((c, i) => ({
      name: c.city || "Unknown",
      value: Number(c.cnt),
      color: COLORS[i % COLORS.length],
    }));
  }, [data]);

  return (
    <div className="container_section_main dark:bg-surface-800">
      <div className="mb-4 flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate(-1)}>
          <ChevronLeft />
          Volver
        </Button>
        {/* shadcn breadcrumb */}
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink asChild>
                <Link to="/qr_codes">QR Codes</Link>
              </BreadcrumbLink>
            </BreadcrumbItem>

            <BreadcrumbSeparator />

            <BreadcrumbItem>
              <BreadcrumbPage>{id}</BreadcrumbPage>
            </BreadcrumbItem>

            <BreadcrumbSeparator />

            <BreadcrumbItem>
              <BreadcrumbPage>Stats</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
      </div>

      <h1 className="text-3xl font-semibold mb-4">Stats for QR {id}</h1>

      <div className="mb-4">
        <Button onClick={handleExportCsv} size="sm" variant="outline">
          Exportar CSV
        </Button>
      </div>

      {loading && <div>Cargando métricas...</div>}
      {error && <div className="text-red-400">Error: {error}</div>}

      {data && (
        <div className="space-y-6">
          {/* First row: full width daily chart */}
          <div className="bg-card p-4 rounded-md shadow-sm dark:bg-surface-700">
            <h2 className="text-xl font-medium mb-2">Scans (Daily)</h2>
            {dailySeries.length === 0 ? (
              <div>No hay datos diarios.</div>
            ) : (
              <div style={{ width: "100%", height: 300 }}>
                <ResponsiveContainer>
                  <LineChart data={dailySeries}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="day" tick={{ fill: "#cbd5e1" }} />
                    <YAxis tick={{ fill: "#cbd5e1" }} />
                    <Tooltip />
                    <Line
                      type="monotone"
                      dataKey="count"
                      stroke="#82ca9d"
                      strokeWidth={2}
                      dot={false}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            )}
          </div>

          {/* Second row: country and city side-by-side */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-card p-4 rounded-md shadow-sm dark:bg-surface-700">
              <h2 className="text-xl font-medium mb-2">By Country</h2>
              {countrySeries.length === 0 ? (
                <div>No hay datos por país.</div>
              ) : (
                <div style={{ width: "100%", height: 300 }}>
                  <ResponsiveContainer>
                    <PieChart>
                      <Pie
                        data={countrySeries}
                        dataKey="value"
                        nameKey="name"
                        outerRadius={90}
                        fill="#8884d8"
                      >
                        {countrySeries.map(
                          (
                            entry: {
                              name: string;
                              value: number;
                              color: string;
                            },
                            index: number
                          ) => (
                            <Cell key={`cell-${index}`} fill={entry.color} />
                          )
                        )}
                      </Pie>
                      <Legend />
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
              )}

              <div className="mt-4">
                <div className="text-sm text-muted-foreground">
                  Total scans: <strong>{data.total}</strong>
                </div>
              </div>
            </div>

            <div className="bg-card p-4 rounded-md shadow-sm dark:bg-surface-700">
              <h2 className="text-xl font-medium mb-2">By City</h2>
              {citySeries.length === 0 ? (
                <div>No hay datos por ciudad.</div>
              ) : (
                <div style={{ width: "100%", height: 300 }}>
                  <ResponsiveContainer>
                    <PieChart>
                      <Pie
                        data={citySeries}
                        dataKey="value"
                        nameKey="name"
                        outerRadius={90}
                        fill="#82ca9d"
                      >
                        {citySeries.map(
                          (
                            entry: {
                              name: string;
                              value: number;
                              color: string;
                            },
                            index: number
                          ) => (
                            <Cell
                              key={`cell-city-${index}`}
                              fill={entry.color}
                            />
                          )
                        )}
                      </Pie>
                      <Legend />
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
