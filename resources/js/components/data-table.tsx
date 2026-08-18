import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

export interface ColumnDef<T> {
    key: string;
    header: string;
    cell: (row: T) => React.ReactNode;
    className?: string;
    /** Hide this column below md on small screens. */
    hideOnMobile?: boolean;
}

interface DataTableProps<T> {
    columns: ColumnDef<T>[];
    rows: T[];
    rowKey: (row: T) => string;
    onRowClick?: (row: T) => void;
    empty?: React.ReactNode;
}

export function DataTable<T>({
    columns,
    rows,
    rowKey,
    onRowClick,
    empty,
}: DataTableProps<T>) {
    if (rows.length === 0 && empty) {
        return <>{empty}</>;
    }

    return (
        <div className="rounded-xl border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
                                className={column.hideOnMobile ? 'hidden md:table-cell' : undefined}
                            >
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) => (
                        <TableRow
                            key={rowKey(row)}
                            onClick={onRowClick ? () => onRowClick(row) : undefined}
                            className={onRowClick ? 'cursor-pointer' : undefined}
                        >
                            {columns.map((column) => (
                                <TableCell
                                    key={column.key}
                                    className={
                                        column.hideOnMobile
                                            ? 'hidden md:table-cell'
                                            : undefined
                                    }
                                >
                                    {column.cell(row)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
