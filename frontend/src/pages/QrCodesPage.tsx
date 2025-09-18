import { useEffect, useState } from "react"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { ChartPie, SquarePen } from "lucide-react"

type Qr = {
  id: number
  token: string
  name?: string | null
  target_url: string
  owner_user_id?: number
  owner_name?: string | null
  owner_email?: string | null
}

export default function QrCodesPage() {
  const [items, setItems] = useState<Qr[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const token = localStorage.getItem("token")
    fetch("/api/qrcodes", {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
      .then(async (res) => {
        if (!res.ok) throw new Error(await res.text())
        return res.json()
      })
      .then((data) => {
        // action payload shape: { statusCode, data }
        const d = data?.data ?? data
        setItems(d)
      })
      .catch((err) => setError(String(err)))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="container_section_main">
      <h1 className="text-2xl font-semibold mb-4">QR Codes</h1>

      <div className="bg-card text-card-foreground rounded shadow p-4">
        {loading ? (
          <p>Cargando...</p>
        ) : error ? (
          <p className="text-destructive">Error: {error}</p>
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Token</TableHead>
                  <TableHead>Target</TableHead>
                  <TableHead>Creator</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.map((q) => (
                  <TableRow key={q.id}>
                    <TableCell className="max-w-xs truncate">{q.name ?? "-"}</TableCell>
                    <TableCell className="font-mono text-sm">{q.token}</TableCell>
                    <TableCell className="truncate max-w-md">
                      <a href={q.target_url} className="hover:underline" target="_blank" rel="noreferrer">
                        {q.target_url}
                      </a>
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">
                        {q.owner_name ?? "Unknown"}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => console.log("stats", q.id)}>
                          <ChartPie />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => console.log("edit", q.id)}
                          className="bg-brand-pink text-brand-pink-foreground hover:opacity-90"
                        >
                          <SquarePen />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </>
        )}
      </div>
    </div>
  )
}
