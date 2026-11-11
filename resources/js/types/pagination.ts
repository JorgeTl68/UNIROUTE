/** Enlace de paginación tal como lo serializa Laravel. */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Resultado de `->paginate()` en Laravel (LengthAwarePaginator). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: PaginationLink[];
};
