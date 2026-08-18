import { useCallback, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

type FilterParams = Record<string, string | number | boolean | null | undefined>;

/**
 * Returns a debounced callback that pushes the given filter params to the
 * provided URL via an Inertia GET visit (preserving scroll + state).
 */
export function useDebouncedFilter(url: string, delay = 250) {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (timer.current) {
                clearTimeout(timer.current);
            }
        };
    }, []);

    return useCallback(
        (params: FilterParams) => {
            if (timer.current) {
                clearTimeout(timer.current);
            }

            timer.current = setTimeout(() => {
                router.get(
                    url,
                    params,
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }, delay);
        },
        [url, delay],
    );
}
