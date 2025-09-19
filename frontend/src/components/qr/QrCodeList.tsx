import {
  Table,
  TableBody,
  TableHeader,
  TableRow,
  TableHead,
} from "@/components/ui/table";
import Pagination from "@/components/ui/pagination";
import QrCodeRow from "./QrCodeRow";
import type { Qr } from "./QrCodeRow";

type Props = {
  items: Qr[];
  loading: boolean;
  error?: string | null;
  page: number;
  totalPages: number;
  urlBaseToken?: string | null;
  onPageChange: (p: number) => void;
  onEdit?: (id: number) => void;
  onStats?: (id: number) => void;
};

export default function QrCodeList({
  items,
  loading,
  error,
  page,
  totalPages,
  urlBaseToken,
  onPageChange,
  onEdit,
  onStats,
}: Props) {
  if (loading) return <p>Cargando...</p>;
  if (error) return <p className="text-destructive">Error: {error}</p>;

  return (
    <>
      <div className="min-h-[527px]">
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
              <QrCodeRow
                key={q.id}
                q={q}
                urlBaseToken={urlBaseToken}
                onEdit={onEdit}
                onStats={onStats}
              />
            ))}
          </TableBody>
        </Table>
      </div>
      <Pagination
        page={page}
        totalPages={totalPages}
        onPageChange={onPageChange}
      />
    </>
  );
}
